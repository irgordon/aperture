<?php
namespace AperturePro\Frontend;

class Shortcode {
    public static function init() {
        add_shortcode( 'aperture_contact_form', [ __CLASS__, 'render' ] );
    }

    public static function render( $atts ) {
        self::enqueue_scripts();
        return '<div id="aperture-pro-contact-form"></div>';
    }

    private static function enqueue_scripts() {
        // Path relative to includes/Frontend/
        // __DIR__ is includes/Frontend
        // plugin_dir_path(__DIR__) is includes/
        // includes/../assets is assets/
        $asset_path = plugin_dir_path( __DIR__ ) . '../assets/build/frontend-form.asset.php';

        if ( ! file_exists( $asset_path ) ) {
            return;
        }

        $asset = require( $asset_path );

        wp_enqueue_script(
            'aperture-pro-frontend',
            plugins_url( '../../assets/build/frontend-form.js', __FILE__ ),
            $asset['dependencies'],
            $asset['version'],
            true
        );

        wp_localize_script('aperture-pro-frontend', 'apertureProSettings', ['root' => esc_url_raw( rest_url() )]);

        wp_enqueue_style(
            'aperture-pro-frontend-css',
            plugins_url( '../../assets/build/frontend-form.css', __FILE__ ),
            [],
            $asset['version']
        );
    }
}
