<?php
namespace AperturePro\Api;
use WP_REST_Controller, WP_REST_Server, WP_REST_Response, Stripe\Stripe, Stripe\Balance;

class Settings_Controller extends WP_REST_Controller {
    protected $namespace = 'aperture/v1';
    protected $rest_base = 'settings';

    public function register_routes() {
        register_rest_route( $this->namespace, '/settings', [
            ['methods' => 'GET', 'callback' => [$this, 'get_settings'], 'permission_callback' => function(){return current_user_can('manage_options');}],
            ['methods' => 'POST', 'callback' => [$this, 'update_settings'], 'permission_callback' => function(){return current_user_can('manage_options');}]
        ]);
        register_rest_route( $this->namespace, '/settings/test-stripe', [
            ['methods' => 'POST', 'callback' => [$this, 'test_stripe'], 'permission_callback' => function(){return current_user_can('manage_options');}]
        ]);
        // NEW: Email Routes
        register_rest_route( $this->namespace, '/templates', [
            ['methods' => 'GET', 'callback' => [$this, 'get_templates'], 'permission_callback' => function(){return current_user_can('manage_options');}],
            ['methods' => 'POST', 'callback' => [$this, 'update_template'], 'permission_callback' => function(){return current_user_can('manage_options');}]
        ]);
        register_rest_route( $this->namespace, '/email/send', [
            ['methods' => 'POST', 'callback' => [$this, 'manual_send'], 'permission_callback' => function(){return current_user_can('manage_options');}]
        ]);
    }

    public function get_settings() {
        $secret = get_option('aperture_stripe_secret_key');
        return new WP_REST_Response([
            'branding' => [
                'company_name' => get_option('aperture_company_name'),
                'logo_url' => get_option('aperture_logo_url'),
                'support_email' => get_option('aperture_support_email')
            ],
            'stripe' => [
                'public_key' => get_option('aperture_stripe_public_key'),
                'secret_key' => $secret ? '••••' . substr($secret, -4) : ''
            ],
            'google' => [
                'client_id' => get_option('ap_google_client_id'),
                'client_secret' => get_option('ap_google_client_secret') ? '••••' : ''
            ],
            'system' => ['sandbox_mode' => get_option('aperture_sandbox_mode', 'yes')]
        ]);
    }

    public function update_settings( $request ) {
        $data = $request->get_json_params();
        if(isset($data['branding'])) foreach($data['branding'] as $k => $v) update_option("aperture_$k", $v);
        if(isset($data['stripe'])) {
            update_option('aperture_stripe_public_key', $data['stripe']['public_key']);
            if(strpos($data['stripe']['secret_key'], '••••') === false) update_option('aperture_stripe_secret_key', $data['stripe']['secret_key']);
        }
        if(isset($data['google'])) {
            update_option('ap_google_client_id', $data['google']['client_id']);
            if(strpos($data['google']['client_secret'], '••••') === false) update_option('ap_google_client_secret', $data['google']['client_secret']);
        }
        if(isset($data['system'])) update_option('aperture_sandbox_mode', $data['system']['sandbox_mode']);
        return new WP_REST_Response(['success'=>true], 200);
    }

    public function test_stripe($request) {
        $secret = $request['secret_key'];
        if(strpos($secret, '••••')!==false) $secret = get_option('aperture_stripe_secret_key');
        try {
            Stripe::setApiKey($secret);
            Balance::retrieve();
            return new WP_REST_Response(['message'=>'Connected!', 'mode'=>strpos($secret,'sk_test')===0?'TEST':'LIVE']);
        } catch(\Exception $e) { return new \WP_Error('fail', $e->getMessage(), ['status'=>500]); }
    }

    public function get_templates() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ap_email_templates");
    }

    public function update_template($request) {
        global $wpdb;
        $p = $request->get_json_params();
        $wpdb->update("{$wpdb->prefix}ap_email_templates", ['subject'=>$p['subject'], 'body'=>$p['body']], ['id'=>$p['id']]);
        return new WP_REST_Response(['success'=>true]);
    }

    public function manual_send($request) {
        $p = $request->get_json_params();
        $data = ['{client_name}' => $p['client_name'], '{amount}' => '$0.00', '{invoice_number}' => 'N/A'];
        $result = \AperturePro\Utils\TemplateMailer::send($p['slug'], $p['email'], $data);
        return $result ? new WP_REST_Response(['message'=>'Sent']) : new \WP_Error('fail','Failed',['status'=>500]);
    }
}
