<?php
namespace AperturePro\Frontend;

class Branding {
    public static function init() {
        add_action('wp_head', [__CLASS__, 'inject_css']);
        add_action('admin_head', [__CLASS__, 'inject_css']);
    }

    public static function inject_css() {
        $primary = get_option('aperture_brand_primary', '#14b8a6');
        $secondary = get_option('aperture_brand_secondary', '#0f766e');
        $bg = get_option('aperture_brand_bg', '#f8fafc');

        echo "<style>
            :root {
                --teal-primary: {$primary} !important;
                --teal-dark: {$secondary} !important;
                --bg-body: {$bg} !important;
            }
        </style>";
    }
}
