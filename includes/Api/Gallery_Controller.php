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
    }

    public function handle_upload( $request ) {
        $files = $request->get_file_params();
        $file = $files['file'];
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/aperture_proofs/' . $request['album_id'];
        $target_url = $upload_dir['baseurl'] . '/aperture_proofs/' . $request['album_id'];
        
        if (!file_exists($target_dir)) mkdir($target_dir, 0755, true);
        
        $filename = sanitize_file_name($request['file_name']);
        move_uploaded_file($file['tmp_name'], $target_dir . '/' . $filename);

        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}ap_gallery_images", [
            'album_id' => $request['album_id'],
            'file_name' => $filename,
            'file_path' => $target_dir . '/' . $filename,
            'public_url' => $target_url . '/' . $filename,
            'serial_number' => '#' . rand(1000,9999)
        ]);
        return new WP_REST_Response(['status'=>'saved'], 201);
    }

    public function get_images( $request ) {
        global $wpdb;
        $id = (int)$request['id'];
        $images = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ap_gallery_images WHERE album_id = $id");
        return rest_ensure_response($images);
    }

    public function submit_selection( $request ) {
        global $wpdb;
        $ids = $request['selected_ids'];
        $album_id = (int)$request['album_id'];
        
        $wpdb->update("{$wpdb->prefix}ap_gallery_images", ['is_selected'=>0], ['album_id'=>$album_id]);
        foreach($ids as $img_id) {
            $wpdb->update("{$wpdb->prefix}ap_gallery_images", ['is_selected'=>1], ['id'=>$img_id]);
        }
        
        wp_mail(get_option('admin_email'), "Proofing Complete Album #$album_id", "Client has selected " . count($ids) . " images.");
        return new WP_REST_Response(['message'=>'Saved'], 200);
    }
}
