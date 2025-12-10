<?php get_header(); ?>

<div id="content" class="site-content">
    <div class="container">
        <main id="primary" class="site-main">

            <section class="error-404 not-found">
                <header class="page-header">
                    <h1 class="page-title"><?php _e( 'Oops! That page can&rsquo;t be found.', 'aperture-theme' ); ?></h1>
                </header>

                <div class="page-content entry-content">
                    <p><?php _e( 'It looks like nothing was found at this location. Maybe try a search?', 'aperture-theme' ); ?></p>
                    <?php get_search_form(); ?>
                </div>
            </section>

        </main>
    </div>
</div>

<?php get_footer(); ?>
