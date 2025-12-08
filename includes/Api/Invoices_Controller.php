<?php
namespace AperturePro\Api;
use WP_REST_Controller, WP_REST_Server, WP_REST_Response;
use Stripe\Stripe, Stripe\PaymentIntent;

class Invoices_Controller extends WP_REST_Controller {
    protected $namespace = 'aperture/v1';

    public function register_routes() {
        register_rest_route( $this->namespace, '/invoices', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [ $this, 'create_invoice' ],
            'permission_callback' => function() { return current_user_can('manage_options'); }
        ]);
        register_rest_route( $this->namespace, '/invoices/public/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [ $this, 'get_public_invoice' ],
            'permission_callback' => '__return_true'
        ]);
    }

    public function create_invoice( $request ) {
        global $wpdb;
        $amount = (float) $request['amount'];
        $stripe_key = get_option('aperture_stripe_secret_key');
        if(!$stripe_key) return new \WP_Error('config', 'Missing Stripe Key', ['status'=>500]);

        Stripe::setApiKey($stripe_key);
        try {
            $intent = PaymentIntent::create([
                'amount' => $amount * 100, 'currency' => 'usd',
                'description' => $request['title']
            ]);
            
            $wpdb->insert("{$wpdb->prefix}ap_invoices", [
                'lead_id' => $request['leadId'],
                'stripe_intent_id' => $intent->id,
                'invoice_number' => 'INV-'.time(),
                'amount' => $amount,
                'due_date' => $request['dueDate']
            ]);
            return new WP_REST_Response(['id'=>$wpdb->insert_id], 201);
        } catch (\Exception $e) { return new \WP_Error('stripe', $e->getMessage(), ['status'=>500]); }
    }

    public function get_public_invoice( $request ) {
        global $wpdb;
        $id = (int)$request['id'];
        $invoice = $wpdb->get_row("SELECT i.*, c.first_name, c.last_name FROM {$wpdb->prefix}ap_invoices i JOIN {$wpdb->prefix}ap_leads l ON i.lead_id = l.id JOIN {$wpdb->prefix}ap_contacts c ON l.contact_id = c.id WHERE i.id = $id");
        
        if(!$invoice) return new \WP_Error('404', 'Not found', ['status'=>404]);

        $branding = [
            'company_name' => get_option('aperture_company_name'),
            'logo_url' => get_option('aperture_logo_url'),
            'address' => get_option('aperture_company_address'),
            'phone' => get_option('aperture_company_phone'),
            'support_email' => get_option('aperture_support_email')
        ];

        $stripe_key = get_option('aperture_stripe_secret_key');
        Stripe::setApiKey($stripe_key);
        $intent = PaymentIntent::retrieve($invoice->stripe_intent_id);

        return rest_ensure_response([
            'invoice' => $invoice,
            'branding' => $branding,
            'client_secret' => $intent->client_secret,
            'status' => $intent->status
        ]);
    }
}
