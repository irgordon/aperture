<footer class="site-footer">
    <div class="container footer-container">
        <div class="footer-left">
            <p>&copy; <?php echo date( 'Y' ); ?> <?php bloginfo( 'name' ); ?>. All rights reserved.</p>
        </div>
        <div class="footer-right">
            <?php
            wp_nav_menu( [
                'theme_location' => 'footer',
                'menu_class'     => 'footer-menu',
                'depth'          => 1,
                'fallback_cb'    => false,
            ] );
            ?>
            <!-- Fallback text links if no menu assigned -->
            <?php if ( ! has_nav_menu( 'footer' ) ) : ?>
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
            <?php endif; ?>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
