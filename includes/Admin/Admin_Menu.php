<?php
namespace AperturePro\Admin;

class Admin_Menu {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menu']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue']);
    }
    public static function add_menu() {
        add_menu_page('AperturePro', 'AperturePro', 'manage_options', 'aperture-pro', function(){ echo '<div id="aperture-admin"></div>'; }, 'dashicons-camera', 25);
    }
    public static function enqueue($hook) {
        if('toplevel_page_aperture-pro' !== $hook) return;
        $asset = require(plugin_dir_path(__DIR__) . '../assets/build/admin-app.asset.php');
        wp_enqueue_script('ap-admin', plugins_url('../../assets/build/admin-app.js', __FILE__), $asset['dependencies'], $asset['version'], true);
        wp_localize_script('ap-admin', 'apSettings', ['root'=>esc_url_raw(rest_url()), 'nonce'=>wp_create_nonce('wp_rest')]);
        wp_enqueue_style('ap-css', plugins_url('../../assets/build/style-admin-app.css', __FILE__));
    }
}
