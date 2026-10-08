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
        // Mobile nav toggle
        const navToggle = document.getElementById('navToggle');
        const navLinks = document.getElementById('navLinks');
        if (navToggle) {
            navToggle.addEventListener('click', () => {
                navLinks.classList.toggle('active');
            });
        }

        // ── Light/Dark mode toggle ──────────────────────────────
        (function () {
            const STORAGE_KEY = 'ss-theme';
            const body = document.body;
            const btn  = document.getElementById('themeToggle');
            const icon = document.getElementById('themeIcon');

            // Apply saved preference
            function applyTheme(theme) {
                if (theme === 'light') {
                    body.setAttribute('data-theme', 'light');
                    if (icon) { icon.classList.remove('fa-sun'); icon.classList.add('fa-moon'); }
                    if (btn)  btn.title = 'Switch to dark mode';
                } else {
                    body.removeAttribute('data-theme');
                    if (icon) { icon.classList.remove('fa-moon'); icon.classList.add('fa-sun'); }
                    if (btn)  btn.title = 'Switch to light mode';
                }
            }

            // Load saved theme
            applyTheme(localStorage.getItem(STORAGE_KEY) || 'dark');

            // Toggle on click
            if (btn) {
                btn.addEventListener('click', () => {
                    const next = body.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
                    localStorage.setItem(STORAGE_KEY, next);
                    applyTheme(next);
                });
            }
        })();
    </script>
</body>
</html>
