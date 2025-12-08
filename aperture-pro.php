<?php
/**
 * Plugin Name: AperturePro CRM
 * Description: A privacy-focused photography CRM.
 * Version: 1.0.0
 * Author: Ian R. Gordon
 * Author URI: https://iangordon.app
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Autoload Composer Dependencies
if ( file_exists( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
}

// 1. Activation Hook (Database Creation)
register_activation_hook( __FILE__, [ 'AperturePro\\Database\\Installer', 'install' ] );

// 2. Initialize Admin Menu & Shortcodes
function aperture_pro_init() {
    if ( is_admin() ) {
        \AperturePro\Admin\Admin_Menu::init();
    }
    
    if ( ! is_admin() ) {
        \AperturePro\Frontend\Shortcode::init();
        \AperturePro\Frontend\Portal_Shortcode::init();
    }
}
add_action( 'plugins_loaded', 'aperture_pro_init' );

// 3. Register REST API Routes
add_action( 'rest_api_init', function () {
    (new \AperturePro\Api\Leads_Controller())->register_routes();
    (new \AperturePro\Api\Invoices_Controller())->register_routes();
    (new \AperturePro\Api\Settings_Controller())->register_routes();
    (new \AperturePro\Api\Gallery_Controller())->register_routes();
    (new \AperturePro\Api\Questionnaire_Controller())->register_routes();
    (new \AperturePro\Api\Export_Controller())->register_routes();
    (new \AperturePro\Api\Contacts_Controller())->register_routes();
});
