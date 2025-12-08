<?php
/**
 * Plugin Name: AperturePro CRM
 * Description: A privacy-focused photography CRM with Leads, Invoices, Proofing, and Questionnaires.
 * Version: 1.0.0
 * Author: Ian Gordon
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Autoload Dependencies
if ( file_exists( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
}

// 2. Install Database Tables on Activation
register_activation_hook( __FILE__, [ 'AperturePro\\Database\\Installer', 'install' ] );

// 3. Initialize Plugin
function aperture_pro_init() {
    // Admin Dashboard
    if ( is_admin() ) {
        \AperturePro\Admin\Admin_Menu::init();
    }

    // Frontend Shortcodes
    if ( ! is_admin() ) {
        \AperturePro\Frontend\Shortcode::init();        // Contact Form
        \AperturePro\Frontend\Portal_Shortcode::init(); // Client Portal
        // Questionnaire Shortcode
        add_shortcode( 'aperture_questionnaire', function( $atts ) {
            $atts = shortcode_atts( [ 'id' => 0 ], $atts );
            return "<div id='ap-questionnaire-root' data-id='{$atts['id']}'></div>";
        });
    }
}
add_action( 'plugins_loaded', 'aperture_pro_init' );

// 4. Register API Routes
add_action( 'rest_api_init', function () {
    ( new \AperturePro\Api\Leads_Controller() )->register_routes();
    ( new \AperturePro\Api\Invoices_Controller() )->register_routes();
    ( new \AperturePro\Api\Settings_Controller() )->register_routes();
    ( new \AperturePro\Api\Gallery_Controller() )->register_routes();
    ( new \AperturePro\Api\Questionnaire_Controller() )->register_routes();
    ( new \AperturePro\Api\Export_Controller() )->register_routes();
    (new \AperturePro\Api\Contacts_Controller())->register_routes();
});
