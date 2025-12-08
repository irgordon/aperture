<?php
/**
 * Plugin Name: AperturePro CRM
 * Description: Complete privacy-focused photography business management system.
 * Version: 2.0.0
 * Author: AperturePro
 * Author URI: https://aperturepro.app
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Autoload Composer Dependencies
if ( file_exists( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
}

// Database Installation Hook
register_activation_hook( __FILE__, [ 'AperturePro\\Database\\Installer', 'install' ] );

// Initialize Admin Menu & Frontend Shortcodes
function aperture_pro_init() {
    if ( is_admin() ) {
        \AperturePro\Admin\Admin_Menu::init();
    }
    
    // Always load frontend logic for portals
    \AperturePro\Frontend\Shortcode::init();
    \AperturePro\Frontend\Portal_Shortcode::init();
}
add_action( 'plugins_loaded', 'aperture_pro_init' );

// Register API Routes
add_action( 'rest_api_init', function () {
    $controllers = [
        'Leads', 
        'Invoices', 
        'Settings', 
        'Gallery', 
        'Questionnaire', 
        'Contacts', 
        'Portal', 
        'Export', 
        'Import',
        'Tasks', 
        'Auth'
    ];

    foreach ( $controllers as $c ) {
        $class = "\\AperturePro\\Api\\{$c}_Controller";
        if ( class_exists( $class ) ) {
            ( new $class() )->register_routes();
        }
    }
});
