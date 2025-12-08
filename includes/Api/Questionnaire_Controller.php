<?php
namespace AperturePro\Api;
use WP_REST_Controller, WP_REST_Server, WP_REST_Response;

class Questionnaire_Controller extends WP_REST_Controller {
    protected $namespace = 'aperture/v1';
    protected $rest_base = 'questionnaires';
    public function register_routes() {
        register_rest_route( $this->namespace, '/' . $this->rest_base, [
            ['methods' => WP_REST_Server::READABLE, 'callback' => [ $this, 'get_items' ], 'permission_callback' => function() { return current_user_can('manage_options'); }],
            ['methods' => WP_REST_Server::CREATABLE, 'callback' => [ $this, 'create_item' ], 'permission_callback' => function() { return current_user_can('manage_options'); }]
        ]);
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/public/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE, 'callback' => [ $this, 'get_public_item' ], 'permission_callback' => '__return_true'
        ]);
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/submit', [
            'methods' => WP_REST_Server::CREATABLE, 'callback' => [ $this, 'submit_response' ], 'permission_callback' => '__return_true'
        ]);
    }
    public function create_item( $request ) {
        global $wpdb;
        $params = $request->get_json_params();
        $wpdb->insert( "{$wpdb->prefix}ap_questionnaires", [ 'title' => sanitize_text_field( $params['title'] ), 'schema_json' => json_encode( $params['questions'] ) ]);
        return new WP_REST_Response( [ 'id' => $wpdb->insert_id ], 201 );
    }
    public function get_items( $request ) {
        global $wpdb;
        $results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ap_questionnaires ORDER BY created_at DESC" );
        foreach ( $results as $row ) $row->questions = json_decode( $row->schema_json );
        return rest_ensure_response( $results );
    }
    public function get_public_item( $request ) {
        global $wpdb;
        $id = (int) $request['id'];
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ap_questionnaires WHERE id = %d", $id ) );
        if ( ! $row ) return new \WP_Error( 'not_found', 'Form not found', [ 'status' => 404 ] );
        return rest_ensure_response( [ 'id' => $row->id, 'title' => $row->title, 'questions' => json_decode( $row->schema_json ) ] );
    }
    public function submit_response( $request ) {
        global $wpdb;
        $params = $request->get_json_params();
        $wpdb->insert( "{$wpdb->prefix}ap_responses", [ 'questionnaire_id' => intval( $params['form_id'] ), 'answers_json' => json_encode( $params['answers'] ) ]);
        return new WP_REST_Response( [ 'message' => 'Thank you!' ], 201 );
    }
}
