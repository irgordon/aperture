<?php
namespace AperturePro\Api;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Response;
use Dompdf\Dompdf;

class Portal_Controller extends WP_REST_Controller {

    protected $namespace = 'aperture/v1';
    protected $rest_base = 'portal';

    public function register_routes() {
        register_rest_route( $this->namespace, '/portal/project/(?P<hash>[a-zA-Z0-9]+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [ $this, 'get_project_data' ],
            'permission_callback' => '__return_true'
        ]);

        register_rest_route( $this->namespace, '/portal/contract/sign-internal', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [ $this, 'internal_sign_contract' ],
            'permission_callback' => '__return_true'
        ]);
        
        register_rest_route( $this->namespace, '/auth/verify', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [ $this, 'verify_account' ],
            'permission_callback' => '__return_true'
        ]);
    }

    public function get_project_data( $request ) {
        global $wpdb;
        $hash = sanitize_text_field( $request['hash'] );

        $lead = $wpdb->get_row( $wpdb->prepare("
            SELECT l.*, c.first_name, c.last_name, c.email, c.phone, c.address 
            FROM {$wpdb->prefix}ap_leads l
            JOIN {$wpdb->prefix}ap_contacts c ON l.contact_id = c.id
            WHERE l.project_hash = %s
        ", $hash) );

        if ( ! $lead ) return new \WP_Error( 'not_found', 'Project not found.', [ 'status' => 404 ] );

        $invoice = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}ap_invoices WHERE lead_id = {$lead->id}");
        $contract = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}ap_contracts WHERE lead_id = {$lead->id}");

        $branding = [
            'company_name' => get_option('aperture_company_name'),
            'logo_url' => get_option('aperture_logo_url')
        ];
        
        // Retrieve client secret from invoice if exists
        $client_secret = ''; 
        if($invoice && $invoice->stripe_intent_id) {
             // Logic to retrieve stripe secret would go here
        }

        return new WP_REST_Response([
            'lead' => $lead,
            'invoice' => $invoice,
            'contract' => $contract,
            'branding' => $branding,
            'client_secret' => $client_secret,
            'user_role' => current_user_can('manage_options') ? 'admin' : 'client'
        ]);
    }

    public function internal_sign_contract( $request ) {
        global $wpdb;
        $params = $request->get_json_params();
        $contract_id = $params['contract_id'];
        $signature_data_uri = $params['signature_image']; 
        $signer_name = sanitize_text_field($params['signer_name']);
        
        $contract = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}ap_contracts WHERE id = $contract_id");
        $lead = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}ap_leads WHERE id = {$contract->lead_id}");
        
        $ip = $_SERVER['REMOTE_ADDR'];
        $timestamp = current_time('mysql');

        // Generate PDF
        $dompdf = new Dompdf();
        $html = "
            <html>
            <head><style>body{font-family:sans-serif;}</style></head>
            <body>
                <h1>Agreement #{$lead->project_hash}</h1>
                <div>{$contract->content}</div>
                <div style='margin-top:30px; border:1px dashed #ccc; padding:10px;'>
                    <p>Signed by: {$signer_name}</p>
                    <img src='{$signature_data_uri}' width='200' />
                    <p>Date: {$timestamp}</p>
                </div>
                <div style='page-break-before:always;'>
                    <h2>Audit Trail</h2>
                    <p>IP: {$ip}</p>
                    <p>Time: {$timestamp}</p>
                </div>
            </body></html>
        ";

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdf_output = $dompdf->output();

        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/aperture_contracts';
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        
        $filename = "contract_{$contract_id}_signed.pdf";
        $file_path = $target_dir . '/' . $filename;
        $file_url = $upload_dir['baseurl'] . '/aperture_contracts/' . $filename;
        
        file_put_contents($file_path, $pdf_output);

        $wpdb->update("{$wpdb->prefix}ap_contracts", 
            ['status' => 'signed', 'signature_data' => $signer_name, 'signed_at' => $timestamp, 'pdf_path' => $file_url], 
            ['id' => $contract_id]
        );

        return new WP_REST_Response(['success' => true, 'pdf_url' => $file_url]);
    }

    public function verify_account( $request ) {
        global $wpdb;
        $p = $request->get_json_params();
        $contact = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ap_contacts WHERE email = %s AND verification_token = %s", $p['email'], $p['token']));

        if (!$contact) return new \WP_Error('invalid', 'Invalid token.', ['status'=>403]);

        $user_id = wp_create_user( $p['email'], $p['password'], $p['email'] );
        if ( is_wp_error($user_id) ) return new \WP_Error('exists', 'Account exists.', ['status'=>400]);

        $user = get_user_by('id', $user_id);
        $user->set_role('subscriber'); // Default to subscriber if custom role missing

        $wpdb->update("{$wpdb->prefix}ap_contacts", ['user_id' => $user_id, 'is_verified' => 1, 'verification_token' => ''], ['id' => $contact->id]);
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);

        $lead = $wpdb->get_row("SELECT project_hash FROM {$wpdb->prefix}ap_leads WHERE contact_id = {$contact->id} ORDER BY id DESC LIMIT 1");
        return new \WP_REST_Response(['success'=>true, 'project_hash' => $lead->project_hash], 200);
    }
}
