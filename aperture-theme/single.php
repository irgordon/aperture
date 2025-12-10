<?php get_header(); ?>

<div id="content" class="site-content">
    <div class="container">
        <main id="primary" class="site-main">

            <?php
            while ( have_posts() ) :
                the_post();
                ?>

                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <header class="entry-header">
                        <h1 class="page-title"><?php the_title(); ?></h1>
                        <div class="entry-meta">
                            <?php echo get_the_date(); ?>
                        </div>
                    </header>

                    <div class="entry-content">
                        <?php the_content(); ?>
                    </div>
                </article>

            <?php endwhile; ?>

        </main>
    </div>
</div>

<?php get_footer(); ?>
