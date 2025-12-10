<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
    <div class="header-container container">
        <!-- Left: Social Icons -->
        <div class="header-social">
            <a href="#" aria-label="Instagram"><span class="dashicons dashicons-camera"></span></a>
            <a href="#" aria-label="Facebook"><span class="dashicons dashicons-facebook"></span></a>
            <a href="#" aria-label="Twitter"><span class="dashicons dashicons-twitter"></span></a>
        </div>

        <!-- Center: Logo -->
        <div class="site-branding">
            <?php
            if ( has_custom_logo() ) {
                the_custom_logo();
            } else {
                echo '<a href="' . esc_url( home_url( '/' ) ) . '">📸 ' . get_bloginfo( 'name' ) . '</a>';
            }
            ?>
        </div>

        <!-- Right: Hamburger Menu -->
        <div class="header-menu-toggle">
            <button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
                <span class="dashicons dashicons-menu-alt3"></span>
            </button>
        </div>
    </div>

    <!-- Slide-out/Dropdown Navigation -->
    <nav class="main-navigation">
        <?php
        wp_nav_menu( [
            'theme_location' => 'primary',
            'menu_id'        => 'primary-menu',
            'container'      => false,
        ] );
        ?>
    </nav>
</header>
