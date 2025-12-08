<?php
namespace AperturePro\Frontend;

class Shortcode {
    public static function init() { add_shortcode( 'aperture_contact_form', [ __CLASS__, 'render' ] ); }
    public static function render( $atts ) { self::enqueue_scripts(); return '<div id="aperture-pro-contact-form"></div>'; }
    private static function enqueue_scripts() {
        $asset = require(plugin_dir_path( __DIR__ ) . '../../assets/build/frontend-form.asset.php');
        wp_enqueue_script('aperture-pro-frontend', plugins_url( '../../assets/build/frontend-form.js', __FILE__ ), $asset['dependencies'], $asset['version'], true);
        wp_localize_script('aperture-pro-frontend', 'apertureProSettings', ['root' => esc_url_raw( rest_url() )]);
        wp_enqueue_style('aperture-pro-frontend-css', plugins_url( '../../assets/build/frontend-form.css', __FILE__ ));
    }
}
