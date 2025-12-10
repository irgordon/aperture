<?php
namespace AperturePro\Api;
use WP_REST_Controller, WP_REST_Server, WP_REST_Response;

class Export_Controller extends WP_REST_Controller {
    protected $namespace = 'aperture/v1';

    public function register_routes() {
        register_rest_route( $this->namespace, '/export/logs', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [ $this, 'export_logs' ],
            'permission_callback' => function() { return current_user_can('manage_options'); }
        ]);
        register_rest_route( $this->namespace, '/export/all', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [ $this, 'export_all' ],
            'permission_callback' => function() { return current_user_can('manage_options'); }
        ]);
    }

    public function export_logs($request) {
        global $wpdb;
        $logs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ap_logs ORDER BY created_at DESC", ARRAY_A);
        $this->serve_csv($logs, 'aperture_logs.csv');
    }

    public function export_all($request) {
        // Simple export of key tables
        global $wpdb;
        $tables = ['contacts', 'leads', 'invoices', 'contracts'];
        $data = [];

        foreach($tables as $t) {
            $data[$t] = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ap_$t", ARRAY_A);
        }

        // Export as JSON for full data backup
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="aperture_full_backup_' . date('Y-m-d') . '.json"');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    private function serve_csv($data, $filename) {
        if (empty($data)) {
            $data = [['message' => 'No data found']];
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, array_keys($data[0]));
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }
}
