<?php
namespace AperturePro\Api;
use WP_REST_Controller, WP_REST_Server, WP_REST_Response;

class Contacts_Controller extends WP_REST_Controller {
    protected $namespace = 'aperture/v1';
    protected $rest_base = 'contacts';

    public function register_routes() {
        register_rest_route( $this->namespace, '/' . $this->rest_base, [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [ $this, 'get_items' ], 'permission_callback' => function(){return current_user_can('manage_options');}],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [ $this, 'create_item' ], 'permission_callback' => function(){return current_user_can('manage_options');}]
        ]);
    }

    public function get_items( $request ) {
        global $wpdb;
        return rest_ensure_response( $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ap_contacts ORDER BY created_at DESC" ) );
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

        if ( ! empty( $params['id'] ) ) {
            $wpdb->update( "{$wpdb->prefix}ap_contacts", $data, [ 'id' => $params['id'] ] );
        } else {
            $wpdb->insert( "{$wpdb->prefix}ap_contacts", $data );
        }
        return new WP_REST_Response( [ 'message' => 'Saved' ], 200 );
    }
}
