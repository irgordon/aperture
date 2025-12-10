<?php
namespace AperturePro\Api;
use WP_REST_Controller, WP_REST_Server, WP_REST_Response;

class Tasks_Controller extends WP_REST_Controller {
    protected $namespace = 'aperture/v1';
    protected $rest_base = 'tasks';

    public function register_routes() {
        register_rest_route($this->namespace, '/tasks', [
            ['methods' => 'GET', 'callback' => [$this, 'get_items'], 'permission_callback' => function(){return current_user_can('manage_options');}],
            ['methods' => 'POST', 'callback' => [$this, 'create_item'], 'permission_callback' => function(){return current_user_can('manage_options');}]
        ]);
        register_rest_route($this->namespace, '/tasks/(?P<id>\d+)', [
            ['methods' => 'PUT', 'callback' => [$this, 'update_item'], 'permission_callback' => function(){return current_user_can('manage_options');}],
            ['methods' => 'DELETE', 'callback' => [$this, 'delete_item'], 'permission_callback' => function(){return current_user_can('manage_options');}]
        ]);
    }

    public function get_items($request) {
        global $wpdb;
        $lead_id = isset($request['lead_id']) ? intval($request['lead_id']) : 0;
        $query = "SELECT * FROM {$wpdb->prefix}ap_tasks";
        if($lead_id) $query .= $wpdb->prepare(" WHERE lead_id = %d", $lead_id);
        $query .= " ORDER BY due_date ASC";

        return rest_ensure_response($wpdb->get_results($query));
    }

    public function create_item($request) {
        global $wpdb; $p = $request->get_json_params();
        $data = [
            'lead_id' => $p['lead_id'],
            'description' => sanitize_text_field($p['description']),
            'priority' => isset($p['priority']) ? sanitize_text_field($p['priority']) : 'medium',
            'status' => isset($p['status']) ? sanitize_text_field($p['status']) : 'pending',
            'due_date' => $p['due_date'],
            'is_completed' => 0
        ];
        $wpdb->insert("{$wpdb->prefix}ap_tasks", $data);
        return new WP_REST_Response(['id'=>$wpdb->insert_id, 'description'=>$data['description'], 'status'=>$data['status'], 'priority'=>$data['priority']], 201);
    }

    public function update_item($request) {
        global $wpdb;
        $id = $request['id'];
        $p = $request->get_json_params();

        $data = [];
        if(isset($p['description'])) $data['description'] = sanitize_text_field($p['description']);
        if(isset($p['priority'])) $data['priority'] = sanitize_text_field($p['priority']);
        if(isset($p['status'])) $data['status'] = sanitize_text_field($p['status']);
        if(isset($p['is_completed'])) $data['is_completed'] = intval($p['is_completed']);

        if (!empty($data)) {
            $wpdb->update("{$wpdb->prefix}ap_tasks", $data, ['id' => $id]);
        }
        return new WP_REST_Response(['success'=>true]);
    }

    public function delete_item($request) {
        global $wpdb; $wpdb->delete("{$wpdb->prefix}ap_tasks", ['id' => $request['id']]);
        return new WP_REST_Response(['success'=>true]);
    }
}
