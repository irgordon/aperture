<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function aperture_theme_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'custom-logo', [
        'height'      => 60,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
    ] );

    register_nav_menus( [
        'primary' => __( 'Primary Menu', 'aperture-theme' ),
        'footer'  => __( 'Footer Menu', 'aperture-theme' ),
    ] );
}
add_action( 'after_setup_theme', 'aperture_theme_setup' );

function aperture_theme_scripts() {
    wp_enqueue_style( 'aperture-theme-style', get_stylesheet_uri(), [], '1.1.0' );
    wp_enqueue_style( 'aperture-theme-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap', [], null );
    // Dashicons for social icons
    wp_enqueue_style( 'dashicons' );

    wp_enqueue_script( 'aperture-theme-js', get_template_directory_uri() . '/js/main.js', ['jquery'], '1.0.0', true );

    wp_localize_script( 'aperture-theme-js', 'apertureTheme', [
        'api_url' => rest_url( 'aperture/v1/leads/public' ),
        'nonce'   => wp_create_nonce( 'wp_rest' )
    ] );
}
add_action( 'wp_enqueue_scripts', 'aperture_theme_scripts' );
