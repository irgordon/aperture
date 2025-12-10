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
        register_rest_route( $this->namespace, '/leads', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [ $this, 'create_item' ], // Admin create/update
            'permission_callback' => function() { return current_user_can('manage_options'); }
        ]);
        register_rest_route( $this->namespace, '/leads/(?P<id>\d+)', [
            'methods' => WP_REST_Server::CREATABLE, // Using POST for update often in WP REST
            'callback' => [ $this, 'update_item' ],
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
        $results = $wpdb->get_results("SELECT l.*, c.first_name, c.last_name, c.email FROM {$wpdb->prefix}ap_leads l LEFT JOIN {$wpdb->prefix}ap_contacts c ON l.contact_id = c.id ORDER BY l.created_at DESC LIMIT 100");
        return rest_ensure_response( $results );
    }

    public function create_item( $request ) {
        global $wpdb;
        $params = $request->get_json_params();

        $contact_id = isset($params['contact_id']) ? intval($params['contact_id']) : 0;
        $title = sanitize_text_field($params['title']);

        // If contact ID is missing, we might create one or error.
        // For admin dashboard, we expect selection.

        $hash = bin2hex(random_bytes(32));

        $wpdb->insert("{$wpdb->prefix}ap_leads", [
            'contact_id' => $contact_id,
            'assigned_to' => intval($params['admin_id']),
            'project_hash' => $hash,
            'title' => $title,
            'project_value' => floatval($params['project_value']),
            'notes' => sanitize_textarea_field($params['notes']),
            'source' => 'admin'
        ]);

        return new WP_REST_Response(['id'=>$wpdb->insert_id], 201);
    }

    public function update_item( $request ) {
        global $wpdb;
        $id = intval($request['id']);
        $params = $request->get_json_params();

        $data = [];
        if(isset($params['title'])) $data['title'] = sanitize_text_field($params['title']);
        if(isset($params['admin_id'])) $data['assigned_to'] = intval($params['admin_id']);
        if(isset($params['contact_id'])) $data['contact_id'] = intval($params['contact_id']);
        if(isset($params['project_value'])) $data['project_value'] = floatval($params['project_value']);
        if(isset($params['notes'])) $data['notes'] = sanitize_textarea_field($params['notes']);
        if(isset($params['stage'])) $data['stage'] = sanitize_text_field($params['stage']);

        if(!empty($data)) {
            $wpdb->update("{$wpdb->prefix}ap_leads", $data, ['id'=>$id]);
        }

        return new WP_REST_Response(['success'=>true]);
    }

    public function create_public_lead( $request ) {
        global $wpdb;
        $email = sanitize_email( $request['email'] );
        if ( empty( $email ) || !is_email( $email ) ) return new \WP_Error( 'missing', 'Valid email required', ['status'=>400] );

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

        $hash = bin2hex(random_bytes(32));

        $wpdb->insert( "{$wpdb->prefix}ap_leads", [
            'contact_id' => $contact_id,
            'project_hash' => $hash,
            'notes' => sanitize_textarea_field( $request['message'] ),
            'source' => 'web'
        ]);

        if ( class_exists( '\AperturePro\Utils\TemplateMailer' ) ) {
            \AperturePro\Utils\TemplateMailer::send( 'new_inquiry', $email, [ '{client_name}' => sanitize_text_field( $request['firstName'] ) ] );
        }

        return new WP_REST_Response(['message'=>'Success', 'hash' => $hash], 201);
    }
}
