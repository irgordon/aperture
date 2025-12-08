<?php
namespace AperturePro\Api;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Response;

class Contacts_Controller extends WP_REST_Controller {

    protected $namespace = 'aperture/v1';
    protected $rest_base = 'contacts';

    public function register_routes() {
        // List Contacts
        register_rest_route( $this->namespace, '/' . $this->rest_base, [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_items' ],
            'permission_callback' => function() { return current_user_can('manage_options'); },
        ] );

        // Create/Update Contact
        register_rest_route( $this->namespace, '/' . $this->rest_base, [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'create_item' ],
            'permission_callback' => function() { return current_user_can('manage_options'); },
        ] );
    }

    public function get_items( $request ) {
        global $wpdb;
        $results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ap_contacts ORDER BY created_at DESC" );
        return rest_ensure_response( $results );
    }

    public function create_item( $request ) {
        global $wpdb;
        $params = $request->get_json_params();
        
        $data = [
            'first_name' => sanitize_text_field( $params['first_name'] ),
            'last_name'  => sanitize_text_field( $params['last_name'] ),
            'email'      => sanitize_email( $params['email'] ),
            'phone'      => sanitize_text_field( $params['phone'] ),
            'address'    => sanitize_textarea_field( $params['address'] ),
        ];

        // Update if ID exists, otherwise Insert
        if ( ! empty( $params['id'] ) ) {
            $wpdb->update( "{$wpdb->prefix}ap_contacts", $data, [ 'id' => $params['id'] ] );
            return new WP_REST_Response( [ 'message' => 'Contact updated' ], 200 );
        } else {
            $wpdb->insert( "{$wpdb->prefix}ap_contacts", $data );
            return new WP_REST_Response( [ 'message' => 'Contact added', 'id' => $wpdb->insert_id ], 201 );
        }
    }
}
