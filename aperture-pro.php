<?php
/**
 * Plugin Name: AperturePro CRM
 * Description: Complete privacy-focused photography business management system.
 * Version: 2.1.0
 * Author: AperturePro
 * Author URI: https://aperturepro.app
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Load Composer Autoloader
if ( file_exists( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
}

// 2. Manual Fallback for Installer (Safety)
if ( ! class_exists( 'AperturePro\\Database\\Installer' ) ) {
    $installer_path = plugin_dir_path( __FILE__ ) . 'includes/Database/Installer.php';
    if ( file_exists( $installer_path ) ) require_once $installer_path;
}

if ( class_exists( 'AperturePro\\Database\\Installer' ) ) {
    register_activation_hook( __FILE__, [ 'AperturePro\\Database\\Installer', 'install' ] );
}

// 3. Initialize
function aperture_pro_init() {
    if ( is_admin() ) {
        // Manual loading for Admin Menu safety
        if(!class_exists('AperturePro\\Admin\\Admin_Menu')) {
             $p = plugin_dir_path(__FILE__) . 'includes/Admin/Admin_Menu.php';
             if(file_exists($p)) require_once $p;
        }
        if(class_exists('AperturePro\\Admin\\Admin_Menu')) \AperturePro\Admin\Admin_Menu::init();
    }
    
    // Frontend logic
    if(!class_exists('AperturePro\\Frontend\\Shortcode')) {
        $p = plugin_dir_path(__FILE__) . 'includes/Frontend/Shortcode.php';
        if(file_exists($p)) require_once $p;
    }
    if(!class_exists('AperturePro\\Frontend\\Portal_Shortcode')) {
        $p = plugin_dir_path(__FILE__) . 'includes/Frontend/Portal_Shortcode.php';
        if(file_exists($p)) require_once $p;
    }

    if(class_exists('AperturePro\\Frontend\\Shortcode')) \AperturePro\Frontend\Shortcode::init();
    if(class_exists('AperturePro\\Frontend\\Portal_Shortcode')) \AperturePro\Frontend\Portal_Shortcode::init();
}
add_action( 'plugins_loaded', 'aperture_pro_init' );

// 4. API Routes
add_action( 'rest_api_init', function () {
    $controllers = ['Leads', 'Invoices', 'Settings', 'Gallery', 'Questionnaire', 'Contacts', 'Portal', 'Export', 'Import', 'Tasks', 'Auth'];
    foreach ( $controllers as $c ) {
        $class = "\\AperturePro\\Api\\{$c}_Controller";
        // Manual fallback if autoloader fails
        if ( !class_exists( $class ) ) {
            $p = plugin_dir_path( __FILE__ ) . "includes/Api/{$c}_Controller.php";
            if(file_exists($p)) require_once $p;
        }
        if ( class_exists( $class ) ) ( new $class() )->register_routes();
    }
});
