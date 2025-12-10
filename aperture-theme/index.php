<?php get_header(); ?>

<div class="container">
    <main id="primary" class="site-main">

        <?php
        if ( have_posts() ) :
            while ( have_posts() ) :
                the_post();
                ?>

                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <?php if ( is_singular() ) : ?>
                        <header class="entry-header">
                            <h1 class="page-title"><?php the_title(); ?></h1>
                        </header>
                    <?php endif; ?>

                    <div class="entry-content">
                        <?php
                        the_content();
                        ?>
                    </div>
                </article>

                <?php
            endwhile;
        else :
            ?>
            <p><?php _e( 'Nothing found.', 'aperture-theme' ); ?></p>
        <?php
        endif;
        ?>

    </main>
</div>

<?php get_footer(); ?>
