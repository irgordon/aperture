<?php
namespace AperturePro\Api;
use WP_REST_Controller, WP_REST_Server, WP_REST_Response;

class Auth_Controller extends WP_REST_Controller {
    protected $namespace = 'aperture/v1';
    public function register_routes() {
        register_rest_route($this->namespace, '/auth/login', [
            'methods' => 'POST', 'callback' => [$this, 'login'], 'permission_callback' => '__return_true'
        ]);
        register_rest_route($this->namespace, '/auth/user', [
            'methods' => 'GET', 'callback' => [$this, 'get_user'], 'permission_callback' => function() { return is_user_logged_in(); }
        ]);
        register_rest_route($this->namespace, '/auth/users', [
            'methods' => 'GET', 'callback' => [$this, 'get_all_users'], 'permission_callback' => function() { return current_user_can('manage_options'); }
        ]);
    }
    public function login($request) {
        $params = $request->get_json_params();
        $user = wp_signon(['user_login' => $params['email'], 'user_password' => $params['password'], 'remember' => true]);
        if (is_wp_error($user)) return new \WP_Error('invalid_auth', 'Invalid credentials', ['status' => 403]);
        return new WP_REST_Response(['success' => true, 'user' => $user->data], 200);
    }
    public function get_user() {
        $user = wp_get_current_user();
        return new WP_REST_Response(['id' => $user->ID, 'email' => $user->user_email, 'name' => $user->display_name]);
    }
    public function get_all_users() {
        $users = get_users(['role__in' => ['administrator', 'editor', 'author']]); // Adjust roles as needed
        $data = array_map(function($u) { return ['id' => $u->ID, 'name' => $u->display_name, 'email' => $u->user_email]; }, $users);
        return new WP_REST_Response($data);
    }
}
