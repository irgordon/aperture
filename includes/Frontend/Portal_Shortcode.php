<?php
namespace AperturePro\Frontend;

class Portal_Shortcode {
    public static function init() { add_shortcode( 'aperture_client_portal', [ __CLASS__, 'render' ] ); }
    public static function render( $atts ) { self::enqueue_scripts(); return '<div id="aperture-client-portal"></div>'; }
    private static function enqueue_scripts() {
        $asset = require(plugin_dir_path( __DIR__ ) . '../../assets/build/client-portal.asset.php');
        wp_enqueue_script('aperture-client-portal', plugins_url( '../../assets/build/client-portal.js', __FILE__ ), $asset['dependencies'], $asset['version'], true);
        wp_localize_script('aperture-client-portal', 'apertureProSettings', ['root' => esc_url_raw( rest_url() ), 'nonce' => wp_create_nonce( 'wp_rest' ), 'stripe_key' => get_option( 'aperture_stripe_public_key' )]);
        wp_enqueue_style('aperture-portal-css', plugins_url( '../../assets/build/client-portal.css', __FILE__ ));
    }
}
