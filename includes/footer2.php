    </main>
    <!-- ===== AUTHENTICATED USER FOOTER ===== -->
    <!-- This footer is shown on pages for logged-in users -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3><i class="fas fa-seedling"></i> Skill Sprout</h3>
                    <p>Your collaborative skill exchange platform. Earn Work Points, grow your expertise.</p>
                </div>

                <div class="footer-col">
                    <h4>Navigation</h4>
                    <ul>
                        <li><a href="<?php echo BASE_URL; ?>/user/dashboard.php">Dashboard</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/tasks/tasks.php">Browse Tasks</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/tasks/create_task.php">Post a Task</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/user/my_work.php">My Work</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Account</h4>
                    <ul>
                        <li><a href="<?php echo BASE_URL; ?>/user/profile.php">My Profile</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/user/wallet.php">Wallet</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/auth/logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?php echo date("Y"); ?> Skill Sprout. All rights reserved.</p>
            </div>
        </div>
    </footer>

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

            applyTheme(localStorage.getItem(STORAGE_KEY) || 'dark');

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
