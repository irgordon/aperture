<?php
namespace AperturePro\Api;
use WP_REST_Controller, WP_REST_Server, WP_REST_Response;

class Settings_Controller extends WP_REST_Controller {
    protected $namespace = 'aperture/v1';

    public function register_routes() {
        register_rest_route( $this->namespace, '/settings', [
            ['methods' => 'GET', 'callback' => [$this, 'get_settings'], 'permission_callback' => function(){return current_user_can('manage_options');}],
            ['methods' => 'POST', 'callback' => [$this, 'update_settings'], 'permission_callback' => function(){return current_user_can('manage_options');}]
        ]);
    }

    public function get_settings() {
        $secret = get_option('aperture_stripe_secret_key');
        return new WP_REST_Response([
            'branding' => [
                'company_name' => get_option('aperture_company_name'),
                'logo_url' => get_option('aperture_logo_url'),
                'address' => get_option('aperture_company_address'),
                'phone' => get_option('aperture_company_phone'),
                'support_email' => get_option('aperture_support_email')
            ],
            'stripe' => [
                'public_key' => get_option('aperture_stripe_public_key'),
                'secret_key' => $secret ? '••••' . substr($secret, -4) : ''
            ]
        ]);
    }

    public function update_settings( $request ) {
        $data = $request->get_json_params();
        if(isset($data['branding'])) {
            foreach($data['branding'] as $k => $v) update_option("aperture_$k", $v);
        }
        if(isset($data['stripe'])) {
            update_option('aperture_stripe_public_key', $data['stripe']['public_key']);
            if(strpos($data['stripe']['secret_key'], '••••') === false) {
                update_option('aperture_stripe_secret_key', $data['stripe']['secret_key']);
            }
        }
        return new WP_REST_Response(['success'=>true], 200);
    }
}
