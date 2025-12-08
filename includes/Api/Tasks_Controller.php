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
        return rest_ensure_response($wpdb->get_results("SELECT * FROM {$wpdb->prefix}ap_tasks ORDER BY due_date ASC"));
    }
    public function create_item($request) {
        global $wpdb; $p = $request->get_json_params();
        $wpdb->insert("{$wpdb->prefix}ap_tasks", ['lead_id' => $p['lead_id'], 'description' => sanitize_text_field($p['description']), 'due_date' => $p['due_date'], 'is_completed' => 0]);
        return new WP_REST_Response(['id'=>$wpdb->insert_id], 201);
    }
    public function update_item($request) {
        global $wpdb; $id = $request['id']; $p = $request->get_json_params();
        $wpdb->update("{$wpdb->prefix}ap_tasks", ['is_completed' => $p['is_completed']], ['id' => $id]);
        return new WP_REST_Response(['success'=>true]);
    }
    public function delete_item($request) {
        global $wpdb; $wpdb->delete("{$wpdb->prefix}ap_tasks", ['id' => $request['id']]);
        return new WP_REST_Response(['success'=>true]);
    }
}
