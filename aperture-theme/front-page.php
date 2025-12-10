<?php get_header(); ?>

<div id="content" class="site-content">

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-bg" style="background-image: url('https://picsum.photos/1920/1080?grayscale');"></div>
        <div class="hero-overlay"></div>
        <div class="container hero-content">
            <h1 class="hero-title"><?php echo get_bloginfo( 'name' ); ?> Photography</h1>
            <p class="hero-message">Capturing life's most precious moments with elegance and style.</p>
            <a href="#contact" class="btn btn-hero">Book a Photo Session</a>
        </div>
    </section>

    <!-- Lead Capture Section -->
    <section id="contact" class="lead-capture-section">
        <div class="container">
            <div class="lead-form-wrapper">
                <h2>Start Your Journey</h2>
                <p>Enter your email to get started with a proposal.</p>
                <form id="hero-lead-form" class="lead-form">
                    <input type="email" name="email" placeholder="Your Email Address" required />
                    <button type="submit" class="btn">Get Started</button>
                </form>
                <div id="form-message"></div>
            </div>
        </div>
    </section>

    <!-- Gallery Section -->
    <section class="gallery-section">
        <div class="container">
            <h2>Latest Work</h2>
            <div class="gallery-grid">
                <?php for($i=1; $i<=9; $i++): ?>
                    <div class="gallery-item">
                        <img src="https://picsum.photos/600/600?random=<?php echo $i; ?>" alt="Gallery Image <?php echo $i; ?>" loading="lazy">
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </section>

    <!-- Spacer Block -->
    <div class="spacer-block"></div>

</div><!-- #content -->

<?php get_footer(); ?>
