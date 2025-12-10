<?php
namespace AperturePro\Admin;

class Admin_Menu {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menu']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue']);
    }

    public static function add_menu() {
        // Main Menu - Dashboard
        add_menu_page(
            'AperturePro',
            'AperturePro',
            'manage_options',
            'aperture-dashboard',
            ['AperturePro\Admin\Pages\Dashboard', 'render'],
            'dashicons-camera',
            25
        );

        // Submenus
        add_submenu_page('aperture-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'aperture-dashboard', ['AperturePro\Admin\Pages\Dashboard', 'render']);

        add_submenu_page('aperture-dashboard', 'Projects', 'Projects', 'manage_options', 'aperture-projects', [__CLASS__, 'render_react_app']);

        add_submenu_page('aperture-dashboard', 'Customers', 'Customers', 'manage_options', 'aperture-customers', ['AperturePro\Admin\Pages\Customers', 'render']);

        add_submenu_page('aperture-dashboard', 'Billing', 'Billing', 'manage_options', 'aperture-billing', ['AperturePro\Admin\Pages\Billing', 'render']);

        add_submenu_page('aperture-dashboard', 'Gallery', 'Gallery', 'manage_options', 'aperture-gallery', ['AperturePro\Admin\Pages\Gallery', 'render']);

        add_submenu_page('aperture-dashboard', 'Settings', 'Settings', 'manage_options', 'aperture-settings', ['AperturePro\Admin\Pages\Settings', 'render']);
    }

    public static function render_react_app() {
        echo '<div id="aperture-admin"></div>';
    }

    public static function enqueue($hook) {
        // Enqueue React App only on Projects page
        // Hook format: aperturepro_page_aperture-projects OR aperture-dashboard_page_aperture-projects depending on top level
        // Since top level slug is 'aperture-dashboard', the hook usually contains that.
        // We'll check if the hook contains our page slug to be safe.
        if (strpos($hook, 'aperture-projects') !== false) {
            $asset_path = plugin_dir_path(__DIR__) . '../assets/build/admin-app.asset.php';
            if (file_exists($asset_path)) {
                $asset = require($asset_path);
                wp_enqueue_script('ap-admin', plugins_url('../../assets/build/admin-app.js', __FILE__), $asset['dependencies'], $asset['version'], true);
                wp_localize_script('ap-admin', 'apSettings', [
                    'root' => esc_url_raw(rest_url()),
                    'nonce' => wp_create_nonce('wp_rest')
                ]);
                wp_enqueue_style('ap-css', plugins_url('../../assets/build/style-admin-app.css', __FILE__), [], $asset['version']);

                // Hide the React Sidebar since we use WP Menu
                wp_add_inline_style('ap-css', '.ap-nav { display: none !important; } .ap-main { margin-left: 0 !important; padding: 20px !important; } .ap-layout { margin-left: 0 !important; }');
            }
        }
        
        // Enqueue styles for other pages if needed
        if (strpos($hook, 'aperture-') !== false) {
             wp_enqueue_style('ap-admin-global', plugins_url('../../assets/build/style-admin-app.css', __FILE__));
        }
    }
}
