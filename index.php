<?php
// ============================================================
// FILE: index.php (Landing Page)
// PURPOSE: The first page visitors see. Shows the platform
//          tagline, feature highlights, and call-to-action
//          buttons to register or login.
// ============================================================

// Include the public header (for guests)
include 'includes/header1.php';
?>

<!-- ===== HERO SECTION ===== -->
<!-- The large banner at the top of the landing page -->
<section class="hero">
    <div class="hero-content">
        <h1>Turn Your <span class="highlight">Skills</span> Into Currency</h1>
        <p class="hero-subtitle">
            Join a community where talent is the most valuable asset. 
            Post tasks, complete challenges, earn Work Points, and grow together.
        </p>
        <div class="hero-buttons">
            <a href="<?php echo BASE_URL; ?>/auth/register.php" class="btn btn-primary btn-lg">Get Started Free</a>
            <a href="<?php echo BASE_URL; ?>/about.php" class="btn btn-outline btn-lg">Learn More</a>
        </div>
    </div>
</section>

<!-- ===== HOW IT WORKS SECTION ===== -->
<section class="section">
    <div class="container">
        <h2 class="section-title">How It Works</h2>
        <p class="section-subtitle">Three simple steps to start exchanging skills</p>

        <div class="features-grid">
            <!-- Step 1: Post a Task -->
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-edit"></i>
                </div>
                <h3>Post a Task</h3>
                <p>Describe what you need done, set the skills required, and offer Work Points as a reward. Your points are securely held in escrow.</p>
            </div>

            <!-- Step 2: Apply & Deliver -->
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <h3>Apply & Deliver</h3>
                <p>Browse open tasks that match your expertise. Submit a pitch, get assigned, and deliver quality work to earn your reward.</p>
            </div>

            <!-- Step 3: Earn & Grow -->
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-coins"></i>
                </div>
                <h3>Earn & Grow</h3>
                <p>Once your work is approved, Work Points are released to you. Use them to post your own tasks or build your reputation.</p>
            </div>
        </div>
    </div>
</section>

<!-- ===== STATS SECTION ===== -->
<section class="section section-dark">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">100</div>
                <div class="stat-label">Free WP on Signup</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">10+</div>
                <div class="stat-label">Skill Categories</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">100%</div>
                <div class="stat-label">Secure Escrow</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">24/7</div>
                <div class="stat-label">Admin Support</div>
            </div>
        </div>
    </div>
</section>

<!-- ===== CTA SECTION ===== -->
<section class="section cta-section">
    <div class="container text-center">
        <h2>Ready to Sprout Your Skills?</h2>
        <p>Join our growing community of skilled professionals and learners today.</p>
        <a href="<?php echo BASE_URL; ?>/auth/register.php" class="btn btn-primary btn-lg">Create Your Account</a>
    </div>
</section>

<?php
// Include the public footer
include 'includes/footer1.php';
?>
