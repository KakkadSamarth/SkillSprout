// ============================================================
// FILE: assets/js/register.js
// PURPOSE: Client-side validation for the registration form.
//          Validates name length, email format, password
//          strength, and password confirmation match.
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');

    if (form) {
        form.addEventListener('submit', function(event) {
            const name     = document.getElementById('name').value.trim();
            const email    = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirm  = document.getElementById('confirm_password').value;

            // Validate name length (minimum 3 characters)
            if (name.length < 3) {
                alert('Name must be at least 3 characters long.');
                event.preventDefault();
                return;
            }

            // Validate email format
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                alert('Please enter a valid email address.');
                event.preventDefault();
                return;
            }

            // Validate password length (minimum 6 characters)
            if (password.length < 6) {
                alert('Password must be at least 6 characters long.');
                event.preventDefault();
                return;
            }

            // Check if passwords match
            if (password !== confirm) {
                alert('Passwords do not match.');
                event.preventDefault();
                return;
            }
        });
    }
});
