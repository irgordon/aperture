<?php
/**
 * Plugin Name: AperturePro CRM
 * Description: A privacy-focused photography CRM.
 * Version: 1.1.0
 * Author: AperturePro
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( file_exists( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
}

register_activation_hook( __FILE__, [ 'AperturePro\\Database\\Installer', 'install' ] );

function aperture_pro_init() {
    if ( is_admin() ) {
        \AperturePro\Admin\Admin_Menu::init();
        // Placeholder for Access_Manager if you haven't built it yet
        if(class_exists('\AperturePro\Admin\Access_Manager')) \AperturePro\Admin\Access_Manager::init(); 
    }
    
    if ( ! is_admin() ) {
        \AperturePro\Frontend\Shortcode::init();
        \AperturePro\Frontend\Portal_Shortcode::init();
        // Placeholders for SEO/Rewrite
        if(class_exists('\AperturePro\Frontend\Seo_Markup')) \AperturePro\Frontend\Seo_Markup::init();
        if(class_exists('\AperturePro\Frontend\Rewrite_Manager')) \AperturePro\Frontend\Rewrite_Manager::init();
    }
}
add_action( 'plugins_loaded', 'aperture_pro_init' );

add_action( 'rest_api_init', function () {
    (new \AperturePro\Api\Leads_Controller())->register_routes();
    (new \AperturePro\Api\Invoices_Controller())->register_routes();
    (new \AperturePro\Api\Settings_Controller())->register_routes();
    (new \AperturePro\Api\Gallery_Controller())->register_routes();
    (new \AperturePro\Api\Questionnaire_Controller())->register_routes();
    (new \AperturePro\Api\Contacts_Controller())->register_routes();
    (new \AperturePro\Api\Portal_Controller())->register_routes();
    (new \AperturePro\Api\Export_Controller())->register_routes();
    
    // Stubs for missing controllers to prevent crash if file is missing
    if(class_exists('\AperturePro\Api\Import_Controller')) (new \AperturePro\Api\Import_Controller())->register_routes();
    if(class_exists('\AperturePro\Api\Seo_Controller')) (new \AperturePro\Api\Seo_Controller())->register_routes();
});
