    </main>
    <!-- ===== PUBLIC FOOTER ===== -->
    <!-- This footer is shown on pages accessible to guests (not logged in) -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-grid">
                <!-- Brand & description column -->
                <div class="footer-col">
                    <h3><i class="fas fa-seedling"></i> Skill Sprout</h3>
                    <p>Turn your skills into currency. A peer-to-peer platform for collaborative task fulfillment and skill exchange.</p>
                </div>

                <!-- Quick links column -->
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="<?php echo BASE_URL; ?>/">Home</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/about.php">About Us</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/auth/login.php">Login</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/auth/register.php">Register</a></li>
                    </ul>
                </div>

                <!-- Contact column -->
                <div class="footer-col">
                    <h4>Contact</h4>
                    <ul>
                        <li><i class="fas fa-envelope"></i> support@skillsprout.com</li>
                        <li><i class="fas fa-map-marker-alt"></i> Online Platform</li>
                    </ul>
                </div>
            </div>

            <!-- Copyright bar -->
            <div class="footer-bottom">
                <p>&copy; <?php echo date("Y"); ?> Skill Sprout. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Mobile navigation toggle script -->
    <script>
        // Get the hamburger button and nav links container
        const navToggle = document.getElementById('navToggle');
        const navLinks = document.getElementById('navLinks');

        // When the hamburger is clicked, toggle the 'active' class
        // which shows/hides the mobile menu
        if (navToggle) {
            navToggle.addEventListener('click', () => {
                navLinks.classList.toggle('active');
            });
        }
    </script>
</body>
</html>
