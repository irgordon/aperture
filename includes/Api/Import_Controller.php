<?php
namespace AperturePro\Api;
use WP_REST_Controller, WP_REST_Server, WP_REST_Response, ZipArchive;

class Import_Controller extends WP_REST_Controller {
    protected $namespace = 'aperture/v1';
    protected $rest_base = 'import';
    public function register_routes() {
        register_rest_route($this->namespace, '/import', [
            'methods' => WP_REST_Server::CREATABLE, 'callback' => [$this, 'handle_import'],
            'permission_callback' => function() { return current_user_can('manage_options'); }
        ]);
    }
    public function handle_import($request) {
        $files = $request->get_file_params();
        if(empty($files['file'])) return new \WP_Error('no_file', 'No file uploaded', ['status'=>400]);
        $zip_file = $files['file']['tmp_name'];
        $upload = wp_upload_dir();
        $extract_path = $upload['basedir'] . '/aperture_imports/' . time();
        mkdir($extract_path, 0755, true);
        $zip = new ZipArchive;
        if($zip->open($zip_file) === TRUE) {
            $zip->extractTo($extract_path);
            $zip->close();
            global $wpdb;
            $tables = ['ap_contacts', 'ap_leads', 'ap_invoices', 'ap_contracts', 'ap_tasks'];
            foreach($tables as $table) {
                $csv_file = $extract_path . "/db/{$table}.csv";
                if(file_exists($csv_file)) {
                    if (($handle = fopen($csv_file, "r")) !== FALSE) {
                        $columns = fgetcsv($handle); 
                        while (($data = fgetcsv($handle)) !== FALSE) {
                            $row = array_combine($columns, $data);
                            $wpdb->replace($wpdb->prefix . $table, $row);
                        }
                        fclose($handle);
                    }
                }
            }
            return new WP_REST_Response(['message' => 'Import Successful'], 200);
        } else {
            return new \WP_Error('zip_error', 'Failed to open ZIP', ['status'=>500]);
        }
    }
}
