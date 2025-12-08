<?php
namespace AperturePro\Api;
use WP_REST_Controller, WP_REST_Server, WP_REST_Response, Dompdf\Dompdf;

class Portal_Controller extends WP_REST_Controller {
    protected $namespace = 'aperture/v1';
    public function register_routes() {
        register_rest_route($this->namespace, '/portal/dashboard', [
            'methods' => 'GET', 'callback' => [$this, 'get_dashboard'], 'permission_callback' => function() { return is_user_logged_in(); }
        ]);
        register_rest_route($this->namespace, '/portal/sign', [
            'methods' => 'POST', 'callback' => [$this, 'sign_contract'], 'permission_callback' => function() { return is_user_logged_in(); }
        ]);
        register_rest_route($this->namespace, '/portal/project/(?P<hash>[a-zA-Z0-9]+)', [
            'methods' => 'GET', 'callback' => [$this, 'get_project_data'], 'permission_callback' => '__return_true'
        ]);
    }
    public function get_dashboard() {
        global $wpdb;
        $user_id = get_current_user_id();
        $contact = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}ap_contacts WHERE user_id = $user_id");
        if (!$contact) return new \WP_Error('no_profile', 'Profile not found', ['status'=>404]);
        
        $leads = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ap_leads WHERE contact_id = {$contact->id}");
        $lead_ids = array_column($leads, 'id');
        $lead_ids_str = implode(',', $lead_ids ?: [0]);
        
        $invoices = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ap_invoices WHERE lead_id IN ($lead_ids_str)");
        $contracts = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ap_contracts WHERE lead_id IN ($lead_ids_str)");
        $tasks = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ap_tasks WHERE lead_id IN ($lead_ids_str) AND is_completed = 0");
        
        return new WP_REST_Response(['profile' => $contact, 'projects' => $leads, 'invoices' => $invoices, 'contracts' => $contracts, 'tasks' => $tasks]);
    }
    public function get_project_data($request) {
        global $wpdb;
        $hash = $request['hash'];
        $lead = $wpdb->get_row($wpdb->prepare("SELECT l.*, c.first_name, c.last_name, c.email FROM {$wpdb->prefix}ap_leads l JOIN {$wpdb->prefix}ap_contacts c ON l.contact_id=c.id WHERE l.project_hash=%s", $hash));
        if(!$lead) return new \WP_Error('404', 'Project not found', ['status'=>404]);
        
        $invoices = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ap_invoices WHERE lead_id = {$lead->id}");
        $contracts = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ap_contracts WHERE lead_id = {$lead->id}");
        return new WP_REST_Response(['lead'=>$lead, 'invoices'=>$invoices, 'contracts'=>$contracts, 'branding'=>['logo_url'=>get_option('aperture_logo_url')]]);
    }
    public function sign_contract($request) {
        global $wpdb;
        $params = $request->get_json_params();
        $id = $params['id'];
        $signature = $params['signature'];
        $contract = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}ap_contracts WHERE id = $id");
        
        $dompdf = new Dompdf();
        $html = "<h1>Contract #{$id}</h1><p>{$contract->content}</p><img src='{$signature}' width='200'/><p>Signed: ".date('Y-m-d')."</p>";
        $dompdf->loadHtml($html);
        $dompdf->render();
        
        $upload = wp_upload_dir();
        $url = $upload['baseurl'] . "/aperture_contracts/contract_{$id}.pdf";
        file_put_contents($upload['basedir'] . "/aperture_contracts/contract_{$id}.pdf", $dompdf->output());
        
        $wpdb->update("{$wpdb->prefix}ap_contracts", ['status'=>'signed', 'signature_data'=>$signature, 'signed_at'=>current_time('mysql'), 'pdf_path'=>$url], ['id'=>$id]);
        return new WP_REST_Response(['pdf_url'=>$url]);
    }
}
