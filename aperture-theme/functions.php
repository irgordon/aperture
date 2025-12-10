<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function aperture_theme_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'custom-logo', [
        'height'      => 50,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
    ] );

    register_nav_menus( [
        'primary' => __( 'Primary Menu', 'aperture-theme' ),
    ] );
}
add_action( 'after_setup_theme', 'aperture_theme_setup' );

function aperture_theme_scripts() {
    wp_enqueue_style( 'aperture-theme-style', get_stylesheet_uri(), [], '1.0.0' );

    // Enqueue Google Fonts (Inter)
    wp_enqueue_style( 'aperture-theme-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap', [], null );
}
add_action( 'wp_enqueue_scripts', 'aperture_theme_scripts' );
