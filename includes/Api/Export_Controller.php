<?php
namespace AperturePro\Api;
use WP_REST_Controller, WP_REST_Server, ZipArchive;

class Export_Controller extends WP_REST_Controller {
    protected $namespace = 'aperture/v1';
    protected $rest_base = 'export';
    public function register_routes() {
        register_rest_route( $this->namespace, '/' . $this->rest_base, [
            'methods' => WP_REST_Server::READABLE, 'callback' => [ $this, 'generate_export' ],
            'permission_callback' => function() { return current_user_can('manage_options'); }
        ]);
    }
    public function generate_export() {
        set_time_limit( 600 ); 
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/aperture_temp/';
        $zip_filename = 'aperture_backup_' . date( 'Y-m-d_H-i' ) . '.zip';
        $zip_path = $temp_dir . $zip_filename;
        $zip = new ZipArchive();
        if ( $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== TRUE ) return new \WP_Error( 'zip_error', 'Could not create zip.', [ 'status' => 500 ] );
        global $wpdb;
        $tables = ['ap_contacts', 'ap_leads', 'ap_invoices', 'ap_gallery_images', 'ap_contracts', 'ap_tasks'];
        foreach ( $tables as $table ) {
            $rows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}$table", ARRAY_A );
            if ( ! empty( $rows ) ) {
                $fp = fopen( 'php://temp', 'r+' );
                fputcsv( $fp, array_keys( $rows[0] ) );
                foreach ( $rows as $row ) fputcsv( $fp, $row );
                rewind( $fp );
                $zip->addFromString( "database/{$table}.csv", stream_get_contents( $fp ) );
                fclose( $fp );
            }
        }
        $contract_dir = $upload['basedir'] . '/aperture_contracts/';
        if(is_dir($contract_dir)) {
            $files = scandir($contract_dir);
            foreach($files as $f) {
                if($f !== '.' && $f !== '..') $zip->addFile($contract_dir.$f, "contracts/$f");
            }
        }
        $zip->close();
        if ( file_exists( $zip_path ) ) {
            header( 'Content-Type: application/zip' );
            header( 'Content-Disposition: attachment; filename="' . basename( $zip_path ) . '"' );
            header( 'Content-Length: ' . filesize( $zip_path ) );
            readfile( $zip_path );
            unlink( $zip_path );
            exit;
        }
    }
}
