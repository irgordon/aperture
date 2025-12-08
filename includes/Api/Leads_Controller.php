<?php
namespace AperturePro\Api;
use WP_REST_Controller, WP_REST_Server, WP_REST_Response;

class Leads_Controller extends WP_REST_Controller {
    protected $namespace = 'aperture/v1';
    protected $rest_base = 'leads';

    public function register_routes() {
        register_rest_route( $this->namespace, '/leads', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [ $this, 'get_items' ],
            'permission_callback' => function() { return current_user_can('manage_options'); }
        ]);
        register_rest_route( $this->namespace, '/leads/public', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [ $this, 'create_public_lead' ],
            'permission_callback' => '__return_true'
        ]);
    }

    public function get_items( $request ) {
        global $wpdb;
        $results = $wpdb->get_results("SELECT l.*, c.first_name, c.last_name, c.email FROM {$wpdb->prefix}ap_leads l JOIN {$wpdb->prefix}ap_contacts c ON l.contact_id = c.id ORDER BY l.created_at DESC LIMIT 100");
        return rest_ensure_response( $results );
    }

    public function create_public_lead( $request ) {
        global $wpdb;
        $email = sanitize_email( $request['email'] );
        if ( empty( $email ) ) return new \WP_Error( 'missing', 'Email required', ['status'=>400] );

        $contact = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}ap_contacts WHERE email = %s", $email ) );
        if ( $contact ) {
            $contact_id = $contact->id;
        } else {
            $wpdb->insert( "{$wpdb->prefix}ap_contacts", [
                'first_name' => sanitize_text_field( $request['firstName'] ),
                'last_name' => sanitize_text_field( $request['lastName'] ),
                'email' => $email,
                'phone' => sanitize_text_field( $request['phone'] )
            ]);
            $contact_id = $wpdb->insert_id;
        }

        // Generate Secure Hash for Portal Access
        $hash = md5( $email . time() . wp_rand() );

        $wpdb->insert( "{$wpdb->prefix}ap_leads", [
            'contact_id' => $contact_id,
            'project_hash' => $hash,
            'notes' => sanitize_textarea_field( $request['message'] ),
            'source' => 'web'
        ]);
        
        return new WP_REST_Response(['message'=>'Success', 'hash' => $hash], 201);
    }
}
