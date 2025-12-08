<?php
namespace AperturePro\Api;
use WP_REST_Controller, WP_REST_Server, WP_REST_Response;
use Stripe\Stripe, Stripe\PaymentIntent;

class Invoices_Controller extends WP_REST_Controller {
    protected $namespace = 'aperture/v1';
    public function register_routes() {
        register_rest_route( $this->namespace, '/invoices', [
            'methods' => WP_REST_Server::READABLE, 'callback' => [ $this, 'get_invoices' ], 'permission_callback' => function() { return current_user_can('manage_options'); }
        ]);
        register_rest_route( $this->namespace, '/invoices', [
            'methods' => WP_REST_Server::CREATABLE, 'callback' => [ $this, 'create_invoice' ], 'permission_callback' => function() { return current_user_can('manage_options'); }
        ]);
        register_rest_route( $this->namespace, '/invoices/public/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE, 'callback' => [ $this, 'get_public_invoice' ], 'permission_callback' => '__return_true'
        ]);
    }
    public function get_invoices($request) {
        global $wpdb;
        return rest_ensure_response($wpdb->get_results("SELECT i.*, c.first_name, c.last_name FROM {$wpdb->prefix}ap_invoices i LEFT JOIN {$wpdb->prefix}ap_leads l ON i.lead_id = l.id LEFT JOIN {$wpdb->prefix}ap_contacts c ON l.contact_id = c.id ORDER BY i.created_at DESC"));
    }
    public function create_invoice( $request ) {
        global $wpdb;
        $params = $request->get_json_params();
        $total = $params['total_amount'];
        $stripe_key = get_option('aperture_stripe_secret_key');
        
        $intent_id = '';
        if($stripe_key) {
            Stripe::setApiKey($stripe_key);
            try {
                $intent = PaymentIntent::create(['amount' => $total * 100, 'currency' => 'usd']);
                $intent_id = $intent->id;
            } catch (\Exception $e) {}
        }
        
        $wpdb->insert("{$wpdb->prefix}ap_invoices", [
            'lead_id' => $params['lead_id'],
            'stripe_intent_id' => $intent_id,
            'invoice_number' => 'INV-'.time(),
            'amount' => $total,
            'total_amount' => $total,
            'items_json' => json_encode($params['items']),
            'due_date' => $params['due_date']
        ]);
        return new WP_REST_Response(['id'=>$wpdb->insert_id], 201);
    }
    public function get_public_invoice( $request ) {
        global $wpdb;
        $id = (int)$request['id'];
        $invoice = $wpdb->get_row("SELECT i.*, c.first_name, c.last_name FROM {$wpdb->prefix}ap_invoices i JOIN {$wpdb->prefix}ap_leads l ON i.lead_id = l.id JOIN {$wpdb->prefix}ap_contacts c ON l.contact_id = c.id WHERE i.id = $id");
        if(!$invoice) return new \WP_Error('404', 'Not found', ['status'=>404]);
        
        $stripe_key = get_option('aperture_stripe_secret_key');
        $client_secret = '';
        if ($stripe_key && $invoice->stripe_intent_id) {
            Stripe::setApiKey($stripe_key);
            try {
                $intent = PaymentIntent::retrieve($invoice->stripe_intent_id);
                $client_secret = $intent->client_secret;
            } catch(\Exception $e) {}
        }

        return rest_ensure_response([
            'invoice' => $invoice,
            'branding' => ['company_name' => get_option('aperture_company_name'), 'logo_url' => get_option('aperture_logo_url')],
            'client_secret' => $client_secret
        ]);
    }
}
