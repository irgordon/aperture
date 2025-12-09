<?php
namespace AperturePro\Frontend;

class Portal_Shortcode {

    public static function init() {
        add_shortcode( 'aperture_client_portal', [ __CLASS__, 'render' ] );
    }

    public static function render( $atts ) {
        self::enqueue_scripts();
        return '<div id="aperture-client-portal"></div>';
    }

    private static function enqueue_scripts() {
        // Correct path logic: Go up from 'includes/Frontend' to root, then into 'assets/build'
        $asset_path = plugin_dir_path( __DIR__ ) . '../assets/build/client-portal.asset.php';
        $js_url     = plugins_url( '../../assets/build/client-portal.js', __FILE__ );
        $css_url    = plugins_url( '../../assets/build/client-portal.css', __FILE__ );

        // SAFETY CHECK: This prevents the Fatal Error if the build file is missing
        if ( ! file_exists( $asset_path ) ) {
            return;
        }

        $assets = require( $asset_path );

        wp_enqueue_script(
            'aperture-client-portal',
            $js_url,
            $assets['dependencies'],
            $assets['version'],
            true
        );

        wp_localize_script( 'aperture-client-portal', 'apertureProSettings', [
            'root'       => esc_url_raw( rest_url() ),
            'nonce'      => wp_create_nonce( 'wp_rest' ),
            'stripe_key' => get_option( 'aperture_stripe_public_key' )
        ] );
        
        wp_enqueue_style(
            'aperture-portal-css',
            $css_url,
            [],
            $assets['version']
        );
    }
}
