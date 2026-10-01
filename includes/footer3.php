    </main>
    <!-- ===== ADMIN FOOTER ===== -->
    <!-- This footer is shown on admin panel pages only -->
    <footer class="footer admin-footer">
        <div class="footer-container">
            <div class="footer-bottom">
                <p>&copy; <?php echo date("Y"); ?> Skill Sprout Admin Panel. Restricted Access.</p>
                <p class="footer-meta">Logged in as: <?php echo htmlspecialchars($admin_name); ?> (<?php echo ucfirst($admin_role); ?>)</p>
            </div>
        </div>
    </footer>

    <script>
        const navToggle = document.getElementById('navToggle');
        const navLinks = document.getElementById('navLinks');
        if (navToggle) {
            navToggle.addEventListener('click', () => {
                navLinks.classList.toggle('active');
            });
        }
    </script>
</body>
</html>
