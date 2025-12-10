<?php
namespace AperturePro\Api;
use WP_REST_Controller, WP_REST_Server, WP_REST_Response;

class Gallery_Controller extends WP_REST_Controller {
    protected $namespace = 'aperture/v1';

    public function register_routes() {
        register_rest_route( $this->namespace, '/gallery/upload', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [ $this, 'handle_upload' ],
            'permission_callback' => function() { return current_user_can('manage_options'); }
        ]);
        register_rest_route( $this->namespace, '/gallery/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [ $this, 'get_images' ],
            'permission_callback' => '__return_true'
        ]);
        register_rest_route( $this->namespace, '/gallery/update_status', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [ $this, 'update_image_status' ],
            'permission_callback' => '__return_true' // Hash check inside
        ]);
        register_rest_route( $this->namespace, '/gallery/download/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [ $this, 'download_image' ],
            'permission_callback' => '__return_true'
        ]);
        register_rest_route( $this->namespace, '/gallery/delivery/(?P<hash>[a-zA-Z0-9]+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [ $this, 'get_delivery_info' ],
            'permission_callback' => '__return_true'
        ]);
        register_rest_route( $this->namespace, '/gallery/generate_zip', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [ $this, 'generate_zip' ],
            'permission_callback' => function() { return current_user_can('manage_options'); }
        ]);
    }

    public function handle_upload( $request ) {
        $files = $request->get_file_params();
        if (empty($files['file'])) return new \WP_Error('no_file', 'No file uploaded', ['status'=>400]);

        $lead_id = $request->get_param('lead_id') ? intval($request->get_param('lead_id')) : 0;
        if ($lead_id === 0) {
             $lead_id = intval($request->get_param('album_id'));
        }

        $file = $files['file'];
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/aperture_proofs/' . $request['album_id'];
        $target_url = $upload_dir['baseurl'] . '/aperture_proofs/' . $request['album_id'];

        if (!file_exists($target_dir)) mkdir($target_dir, 0755, true);

        $filename = sanitize_file_name($request['file_name']);
        move_uploaded_file($file['tmp_name'], $target_dir . '/' . $filename);

        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}ap_gallery_images", [
            'album_id' => intval($request['album_id']),
            'lead_id' => $lead_id,
            'file_name' => $filename,
            'file_path' => $target_dir . '/' . $filename,
            'public_url' => $target_url . '/' . $filename,
            'serial_number' => '#' . rand(1000,9999),
            'proof_id' => '#' . rand(100,999),
            'status' => 'pending'
        ]);

        return new WP_REST_Response(['status'=>'saved'], 201);
    }

    public function get_images( $request ) {
        global $wpdb;
        $id = (int)$request['id'];
        $hash = $request->get_param('hash');

        $access = $this->check_access($id, $hash);
        if (is_wp_error($access)) return $access;

        $images = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ap_gallery_images WHERE album_id = %d", $id));
        return rest_ensure_response($images);
    }

    public function update_image_status( $request ) {
        global $wpdb;
        $id = intval($request['id']);
        $status = sanitize_text_field($request['status']); // approved, rejected
        $hash = $request['hash'];

        // Find image to get album_id
        $img = $wpdb->get_row($wpdb->prepare("SELECT album_id FROM {$wpdb->prefix}ap_gallery_images WHERE id=%d", $id));
        if(!$img) return new \WP_Error('404', 'Image not found', ['status'=>404]);

        $access = $this->check_access($img->album_id, $hash);
        if (is_wp_error($access)) return $access;

        $wpdb->update("{$wpdb->prefix}ap_gallery_images", ['status'=>$status], ['id'=>$id]);
        return new WP_REST_Response(['message'=>'Updated'], 200);
    }

    public function download_image( $request ) {
        global $wpdb;
        $id = (int)$request['id'];
        $hash = $request->get_param('hash');

        $image = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ap_gallery_images WHERE id = %d", $id));
        if (!$image) return new \WP_Error('not_found', 'Image not found', ['status'=>404]);

        $access = $this->check_access($image->album_id, $hash, true);
        if (is_wp_error($access)) return $access;

        return new WP_REST_Response(['url' => $image->public_url]);
    }

    public function get_delivery_info($request) {
        global $wpdb;
        $hash = $request['hash'];
        $lead = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ap_leads WHERE project_hash = %s", $hash));
        if(!$lead) return new \WP_Error('404', 'Project not found', ['status'=>404]);

        // Check Gates? Yes, likely.
        // Assuming album_id == lead_id logic from before.
        $access = $this->check_access($lead->id, $hash, true); // Strict check for delivery
        if (is_wp_error($access)) return $access;

        return new WP_REST_Response([
            'is_zip_ready' => (bool)$lead->is_zip_ready,
            'zip_url' => $lead->is_zip_ready ? content_url("/uploads/aperture_deliveries/" . basename($lead->zip_path)) : null,
            'delivery_notes' => $lead->delivery_notes,
            'expiry_date' => $lead->gallery_expiry
        ]);
    }

    public function generate_zip($request) {
        // Admin only trigger
        global $wpdb;
        $lead_id = intval($request['lead_id']);

        \AperturePro\Utils\Queue::push('build_zip', ['lead_id' => $lead_id]);
        return new WP_REST_Response(['message'=>'Build queued'], 200);
    }

    private function check_access($album_id, $hash, $strict_download = false) {
        global $wpdb;
        if (current_user_can('manage_options')) return true;

        // Note: album_id here is treated as lead_id/album grouping
        // Ideally we join via ap_gallery_images, but if we passed lead_id directly (like in get_delivery_info), we handle both

        // Try finding lead by ID first (if album_id matches lead_id)
        $lead = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ap_leads WHERE id = %d", $album_id));

        // If not found, try via gallery images join
        if (!$lead) {
            $lead = $wpdb->get_row($wpdb->prepare(
                "SELECT l.* FROM {$wpdb->prefix}ap_leads l
                 JOIN {$wpdb->prefix}ap_gallery_images g ON l.id = g.lead_id
                 WHERE g.album_id = %d LIMIT 1",
                $album_id
            ));
        }

        if (!$lead) return new \WP_Error('forbidden', 'Access Denied: Project not found.', ['status'=>403]);
        if ($lead->project_hash !== $hash) return new \WP_Error('forbidden', 'Invalid Project Hash', ['status'=>403]);

        // Check Gates
        $unpaid_invoices = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ap_invoices WHERE lead_id = %d AND status != 'paid'", $lead->id));
        $unsigned_contracts = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ap_contracts WHERE lead_id = %d AND status != 'signed'", $lead->id));

        $pending = [];
        if ($unpaid_invoices > 0) $pending[] = 'Payment of open invoices';
        if ($unsigned_contracts > 0) $pending[] = 'Signing of contracts';

        if (!empty($pending)) {
            // Get contact info for email trigger
             $contact = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ap_contacts WHERE id=%d", $lead->contact_id));

            if ($strict_download) {
                if($contact) {
                    \AperturePro\Utils\Automation::trigger('gate_locked', [
                        'email' => $contact->email,
                        'client_name' => $contact->first_name,
                        'pending_items' => implode(', ', $pending)
                    ]);
                }
                return new \WP_Error('gate_locked', 'Pending: ' . implode(', ', $pending), ['status'=>403]);
            } else {
                 return new \WP_Error('gate_locked', 'Restricted. Pending: ' . implode(', ', $pending), ['status'=>403]);
            }
        }

        return true;
    }
}
