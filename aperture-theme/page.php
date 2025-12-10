<?php get_header(); ?>

<div class="container">
    <main id="primary" class="site-main">

        <?php
        while ( have_posts() ) :
            the_post();

            // Special layout for Client Portal or Contact Us pages if needed
            // But for now, general page layout works well due to container styles
            ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="entry-header">
                    <h1 class="page-title"><?php the_title(); ?></h1>
                </header>

                <div class="entry-content">
                    <?php the_content(); ?>
                </div>
            </article>

        <?php endwhile; ?>

    </main>
</div>

<?php get_footer(); ?>
