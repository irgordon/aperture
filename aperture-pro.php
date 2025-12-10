<?php
/**
 * Plugin Name: AperturePro CRM
 * Description: Complete privacy-focused photography business management system.
 * Version: 2.4.0
 * Author: AperturePro
 * Author URI: https://aperturepro.app
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Load Composer Autoloader or Register SPL Autoloader
if ( file_exists( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
} else {
    // Simple fallback autoloader for AperturePro namespace
    spl_autoload_register( function ( $class ) {
        $prefix = 'AperturePro\\';
        $base_dir = plugin_dir_path( __FILE__ ) . 'includes/';

        $len = strlen( $prefix );
        if ( strncmp( $prefix, $class, $len ) !== 0 ) {
            return;
        }

        $relative_class = substr( $class, $len );
        $file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

        if ( file_exists( $file ) ) {
            require $file;
        }
    });

    // Add admin notice about missing dependencies
    add_action( 'admin_notices', function() {
        ?>
        <div class="notice notice-error">
            <p><?php _e( 'AperturePro CRM: Composer dependencies are missing. Please run <code>composer install</code> in the plugin directory to enable full functionality (Stripe, Google Calendar, etc.).', 'aperture-pro' ); ?></p>
        </div>
        <?php
    });
}

// 2. Activation Hook
register_activation_hook( __FILE__, [ 'AperturePro\\Database\\Installer', 'install' ] );

// 3. Initialize
function aperture_pro_init() {
    if ( is_admin() ) {
        \AperturePro\Admin\Admin_Menu::init();
    }
    
    // Frontend logic
    \AperturePro\Frontend\Shortcode::init();
    \AperturePro\Frontend\Portal_Shortcode::init();

    // Dynamic Branding
    if(!class_exists('AperturePro\\Frontend\\Branding')) {
        require_once plugin_dir_path(__FILE__) . 'includes/Frontend/Branding.php';
    }
    \AperturePro\Frontend\Branding::init();

    // Schedule Queue Processing
    if (!wp_next_scheduled('aperture_process_queue')) {
        wp_schedule_event(time(), 'every_minute', 'aperture_process_queue');
    }
}
add_action( 'plugins_loaded', 'aperture_pro_init' );

// Add custom cron interval
add_filter( 'cron_schedules', function( $schedules ) {
    $schedules['every_minute'] = [
        'interval' => 60,
        'display'  => __( 'Every Minute' )
    ];
    return $schedules;
});

// Hook Queue Processing
add_action('aperture_process_queue', function() {
    if(class_exists('\AperturePro\Utils\Queue')) {
        \AperturePro\Utils\Queue::process();
    }
});

// Clean up on deactivation
register_deactivation_hook( __FILE__, function() {
    wp_clear_scheduled_hook('aperture_process_queue');
});

// 4. API Routes
add_action( 'rest_api_init', function () {
    $controllers = ['Leads', 'Invoices', 'Settings', 'Gallery', 'Questionnaire', 'Contacts', 'Portal', 'Export', 'Import', 'Tasks', 'Auth'];
    foreach ( $controllers as $c ) {
        $class = "\\AperturePro\\Api\\{$c}_Controller";
        if ( class_exists( $class ) ) {
            ( new $class() )->register_routes();
        }
    }
});
