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
        register_rest_route( $this->namespace, '/gallery/submit', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [ $this, 'submit_selection' ],
            'permission_callback' => '__return_true'
        ]);
        register_rest_route( $this->namespace, '/gallery/download/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [ $this, 'download_image' ],
            'permission_callback' => '__return_true'
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
            'proof_id' => '#' . rand(100,999)
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

    public function submit_selection( $request ) {
        global $wpdb;
        $ids = $request['ids'];
        $album_id = (int)$request['albumId'];
        $hash = $request['hash'];

        // Security Check: Ensure the user submitting this selection has access
        $access = $this->check_access($album_id, $hash);
        if (is_wp_error($access)) return $access;

        $wpdb->update("{$wpdb->prefix}ap_gallery_images", ['is_selected'=>0], ['album_id'=>$album_id]);
        foreach($ids as $img_id) {
            // Ensure image belongs to album to prevent updating unrelated images
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}ap_gallery_images SET is_selected=1 WHERE id=%d AND album_id=%d",
                intval($img_id),
                $album_id
            ));
        }

        \AperturePro\Utils\TemplateMailer::send( 'photos_ready', get_option('admin_email'), [] );
        return new WP_REST_Response(['message'=>'Saved'], 200);
    }

    public function download_image( $request ) {
        global $wpdb;
        $id = (int)$request['id'];
        $hash = $request->get_param('hash');

        $image = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ap_gallery_images WHERE id = %d", $id));
        if (!$image) return new \WP_Error('not_found', 'Image not found', ['status'=>404]);

        $access = $this->check_access($image->album_id, $hash, true); // Strict check for download
        if (is_wp_error($access)) {
            \AperturePro\Utils\Logger::log('access_denied', 'Attempted unauthorized download', ['image_id' => $id, 'hash' => $hash]);
            return $access;
        }

        return new WP_REST_Response(['url' => $image->public_url]);
    }

    private function check_access($album_id, $hash, $strict_download = false) {
        global $wpdb;

        if (current_user_can('manage_options')) return true;

        $lead = $wpdb->get_row($wpdb->prepare(
            "SELECT l.*, c.first_name, c.email FROM {$wpdb->prefix}ap_leads l
             JOIN {$wpdb->prefix}ap_gallery_images g ON l.id = g.lead_id
             JOIN {$wpdb->prefix}ap_contacts c ON l.contact_id = c.id
             WHERE g.album_id = %d LIMIT 1",
            $album_id
        ));

        if (!$lead) {
            return new \WP_Error('forbidden', 'Access Denied: Project not found or invalid album.', ['status'=>403]);
        }

        if ($lead->project_hash !== $hash) {
             return new \WP_Error('forbidden', 'Invalid Project Hash', ['status'=>403]);
        }

        // Check Gates
        $unpaid_invoices = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ap_invoices WHERE lead_id = %d AND status != 'paid'", $lead->id));
        $unsigned_contracts = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ap_contracts WHERE lead_id = %d AND status != 'signed'", $lead->id));

        $pending = [];
        if ($unpaid_invoices > 0) $pending[] = 'Payment of open invoices';
        if ($unsigned_contracts > 0) $pending[] = 'Signing of contracts';

        if (!empty($pending)) {
            if ($strict_download) {
                \AperturePro\Utils\Automation::trigger('gate_locked', [
                    'email' => $lead->email,
                    'client_name' => $lead->first_name,
                    'pending_items' => implode(', ', $pending)
                ]);
                return new \WP_Error('gate_locked', 'Please complete pending items: ' . implode(', ', $pending), ['status'=>403]);
            } else {
                 return new \WP_Error('gate_locked', 'Access Restricted. Pending: ' . implode(', ', $pending), ['status'=>403]);
            }
        }

        return true;
    }
}
