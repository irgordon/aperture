<?php
namespace AperturePro\Api;
use WP_REST_Controller, WP_REST_Server, WP_REST_Response;
use Dompdf\Dompdf;

class Portal_Controller extends WP_REST_Controller {
    protected $namespace = 'aperture/v1';

    public function register_routes() {
        register_rest_route($this->namespace, '/portal/dashboard', [
            'methods' => 'GET',
            'callback' => [$this, 'get_dashboard'],
            'permission_callback' => function() { return is_user_logged_in(); }
        ]);
        register_rest_route($this->namespace, '/portal/sign', [
            'methods' => 'POST',
            'callback' => [$this, 'sign_contract'],
            'permission_callback' => function() { return is_user_logged_in(); }
        ]);
        register_rest_route($this->namespace, '/portal/project/(?P<hash>[a-zA-Z0-9]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_project_data'],
            'permission_callback' => '__return_true'
        ]);
    }

    public function get_dashboard() {
        global $wpdb;
        $user_id = get_current_user_id();

        $contact = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ap_contacts WHERE user_id = %d", $user_id ) );
        if (!$contact) return new \WP_Error('no_profile', 'Profile not found', ['status'=>404]);
        
        $leads = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ap_leads WHERE contact_id = %d", $contact->id ) );

        if ( empty( $leads ) ) {
            return new WP_REST_Response(['profile' => $contact, 'projects' => [], 'invoices' => [], 'contracts' => [], 'tasks' => []]);
        }

        $lead_ids = array_map(function($l) { return (int)$l->id; }, $leads);
        $lead_ids_str = implode(',', $lead_ids);
        
        $invoices = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ap_invoices WHERE lead_id IN ($lead_ids_str)");
        $contracts = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ap_contracts WHERE lead_id IN ($lead_ids_str)");
        $tasks = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ap_tasks WHERE lead_id IN ($lead_ids_str) AND is_completed = 0");
        
        // Enhance projects with progress data
        foreach ($leads as $lead) {
            $lead->progress = $this->calculate_progress($lead, $invoices, $contracts);
        }

        return new WP_REST_Response(['profile' => $contact, 'projects' => $leads, 'invoices' => $invoices, 'contracts' => $contracts, 'tasks' => $tasks]);
    }

    public function get_project_data($request) {
        global $wpdb;
        $hash = $request['hash'];
        $lead = $wpdb->get_row($wpdb->prepare("SELECT l.*, c.first_name, c.last_name, c.email FROM {$wpdb->prefix}ap_leads l JOIN {$wpdb->prefix}ap_contacts c ON l.contact_id=c.id WHERE l.project_hash=%s", $hash));

        if(!$lead) return new \WP_Error('404', 'Project not found', ['status'=>404]);
        
        $invoices = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ap_invoices WHERE lead_id = %d", $lead->id));
        $contracts = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ap_contracts WHERE lead_id = %d", $lead->id));

        $lead->progress = $this->calculate_progress($lead, $invoices, $contracts);

        return new WP_REST_Response([
            'lead'=>$lead,
            'invoices'=>$invoices,
            'contracts'=>$contracts,
            'branding'=>[
                'logo_url'=>get_option('aperture_logo_url'),
                'primary_color'=>get_option('aperture_brand_primary', '#14b8a6')
            ]
        ]);
    }

    private function calculate_progress($lead, $all_invoices, $all_contracts) {
        // Steps: Inquiry -> Proposal -> Contract -> Invoice -> Gallery
        $steps = [
            ['label' => 'Inquiry', 'status' => 'completed'],
            ['label' => 'Proposal', 'status' => 'current'],
            ['label' => 'Contract', 'status' => 'pending'],
            ['label' => 'Invoice', 'status' => 'pending'],
            ['label' => 'Gallery', 'status' => 'pending']
        ];

        // Logic to determine status
        $lead_invoices = array_filter($all_invoices, function($i) use ($lead) { return $i->lead_id == $lead->id; });
        $lead_contracts = array_filter($all_contracts, function($c) use ($lead) { return $c->lead_id == $lead->id; });

        $has_signed_contract = false;
        foreach($lead_contracts as $c) if($c->status === 'signed') $has_signed_contract = true;

        $has_paid_invoice = false;
        foreach($lead_invoices as $i) if($i->status === 'paid') $has_paid_invoice = true;

        if ($lead->stage !== 'inquiry') $steps[1]['status'] = 'completed';

        if ($has_signed_contract) {
            $steps[2]['status'] = 'completed';
        } elseif (!empty($lead_contracts)) {
            $steps[2]['status'] = 'current';
        }

        if ($has_paid_invoice) {
            $steps[3]['status'] = 'completed';
        } elseif (!empty($lead_invoices)) {
            $steps[3]['status'] = 'current';
        }

        if ($has_signed_contract && $has_paid_invoice) {
            $steps[4]['status'] = 'current';
             // If gallery exists? Need query for that. Assuming 'current' if paid/signed.
        }

        return $steps;
    }

    public function sign_contract($request) {
        global $wpdb;
        $params = $request->get_json_params();
        $id = isset($params['id']) ? (int)$params['id'] : 0;
        $signature = isset($params['signature']) ? $params['signature'] : '';

        if ( empty($id) || empty($signature) ) {
            return new \WP_Error('missing_params', 'Missing ID or Signature', ['status'=>400]);
        }

        $user_id = get_current_user_id();
        $is_owner = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ap_contracts c
             JOIN {$wpdb->prefix}ap_leads l ON c.lead_id = l.id
             JOIN {$wpdb->prefix}ap_contacts ct ON l.contact_id = ct.id
             WHERE c.id = %d AND ct.user_id = %d",
             $id, $user_id
        ) );

        if ( !$is_owner ) {
             return new \WP_Error('forbidden', 'You do not have permission to sign this contract.', ['status'=>403]);
        }
        
        $contract = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ap_contracts WHERE id = %d", $id ) );
        
        if ( !class_exists('Dompdf\Dompdf') ) {
             return new \WP_Error('dependency_missing', 'PDF generation library missing', ['status'=>500]);
        }
        
        try {
            $dompdf = new Dompdf();
            $html = "<h1>Contract #{$id}</h1>" . wp_kses_post($contract->content) . "<br/><br/><img src='" . esc_url($signature) . "' width='200'/><p>Signed: ".date('Y-m-d')."</p>";
            $dompdf->loadHtml($html);
            $dompdf->render();

            $upload = wp_upload_dir();
            $file_name = "contract_{$id}_" . md5(time()) . ".pdf";
            $file_path = $upload['basedir'] . "/aperture_contracts/" . $file_name;
            $url = $upload['baseurl'] . "/aperture_contracts/" . $file_name;

            file_put_contents($file_path, $dompdf->output());

            $wpdb->update("{$wpdb->prefix}ap_contracts", ['status'=>'signed', 'signature_data'=>$signature, 'signed_at'=>current_time('mysql'), 'pdf_path'=>$url], ['id'=>$id]);

            return new WP_REST_Response(['pdf_url'=>$url]);

        } catch (\Exception $e) {
            \AperturePro\Utils\Logger::log('error', 'Contract Signing Failed', ['error'=>$e->getMessage(), 'contract_id'=>$id]);
            return new \WP_Error('pdf_error', $e->getMessage(), ['status'=>500]);
        }
    }
}
