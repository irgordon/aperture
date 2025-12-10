<?php
namespace AperturePro\Api;
use WP_REST_Controller, WP_REST_Server, WP_REST_Response;
use Stripe\Stripe, Stripe\PaymentIntent;

class Invoices_Controller extends WP_REST_Controller {
    protected $namespace = 'aperture/v1';

    public function register_routes() {
        register_rest_route( $this->namespace, '/invoices', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [ $this, 'get_invoices' ],
            'permission_callback' => function() { return current_user_can('manage_options'); }
        ]);
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
        // Webhook endpoint for Stripe
        register_rest_route( $this->namespace, '/invoices/webhook', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [ $this, 'handle_webhook' ],
            'permission_callback' => '__return_true'
        ]);
    }

    public function get_invoices($request) {
        global $wpdb;
        $results = $wpdb->get_results("SELECT i.*, c.first_name, c.last_name FROM {$wpdb->prefix}ap_invoices i LEFT JOIN {$wpdb->prefix}ap_leads l ON i.lead_id = l.id LEFT JOIN {$wpdb->prefix}ap_contacts c ON l.contact_id = c.id ORDER BY i.created_at DESC");
        return rest_ensure_response($results);
    }

    public function create_invoice( $request ) {
        global $wpdb;
        $params = $request->get_json_params();
        $total = floatval($params['total_amount']);
        $lead_id = intval($params['lead_id']);
        $items = isset($params['items']) ? json_encode($params['items']) : '[]';
        $due_date = sanitize_text_field($params['due_date']);

        $stripe_key = get_option('aperture_stripe_secret_key');
        
        $intent_id = '';
        if($stripe_key) {
            try {
                Stripe::setApiKey($stripe_key);
                $intent = PaymentIntent::create(['amount' => round($total * 100), 'currency' => 'usd']);
                $intent_id = $intent->id;
            } catch (\Exception $e) {
                \AperturePro\Utils\Logger::log('error', 'Stripe Intent Creation Failed', ['error' => $e->getMessage()]);
            }
        }
        
        $wpdb->insert("{$wpdb->prefix}ap_invoices", [
            'lead_id' => $lead_id,
            'stripe_intent_id' => $intent_id,
            'invoice_number' => 'INV-' . time(),
            'amount' => $total,
            'total_amount' => $total,
            'items_json' => $items,
            'due_date' => $due_date
        ]);

        return new WP_REST_Response(['id'=>$wpdb->insert_id], 201);
    }

    public function get_public_invoice( $request ) {
        global $wpdb;
        $id = (int)$request['id'];
        $hash = isset($request['hash']) ? sanitize_text_field($request['hash']) : '';

        if ( current_user_can('manage_options') ) {
             // Admin
        } elseif ( empty($hash) ) {
             return new \WP_Error('forbidden', 'Project Hash required', ['status'=>403]);
        }

        $query = "SELECT i.*, c.first_name, c.last_name, c.email
                  FROM {$wpdb->prefix}ap_invoices i
                  JOIN {$wpdb->prefix}ap_leads l ON i.lead_id = l.id
                  JOIN {$wpdb->prefix}ap_contacts c ON l.contact_id = c.id
                  WHERE i.id = %d";

        $args = [$id];

        if ( ! current_user_can('manage_options') ) {
            $query .= " AND l.project_hash = %s";
            $args[] = $hash;
        }

        $invoice = $wpdb->get_row( $wpdb->prepare( $query, $args ) );

        if(!$invoice) return new \WP_Error('404', 'Invoice not found or access denied', ['status'=>404]);
        
        $stripe_key = get_option('aperture_stripe_secret_key');
        $client_secret = '';
        if ($stripe_key && $invoice->stripe_intent_id) {
            try {
                Stripe::setApiKey($stripe_key);
                $intent = PaymentIntent::retrieve($invoice->stripe_intent_id);
                $client_secret = $intent->client_secret;

                // Check if payment failed recently (simulated check here as we can't get real time events without webhook)
                if ($intent->status === 'requires_payment_method' && $intent->last_payment_error) {
                    \AperturePro\Utils\Automation::trigger('payment_failed', [
                        'email' => $invoice->email,
                        'client_name' => $invoice->first_name,
                        'invoice_number' => $invoice->invoice_number,
                        'portal_link' => home_url("/client-portal/?hash=$hash")
                    ]);
                }

            } catch(\Exception $e) {
                \AperturePro\Utils\Logger::log('error', 'Stripe Retrieval Failed', ['error' => $e->getMessage()]);
            }
        }

        return rest_ensure_response([
            'invoice' => $invoice,
            'branding' => ['company_name' => get_option('aperture_company_name'), 'logo_url' => get_option('aperture_logo_url')],
            'client_secret' => $client_secret
        ]);
    }

    public function handle_webhook($request) {
        // Placeholder for real webhook handling
        return new WP_REST_Response(['status'=>'received']);
    }
}
