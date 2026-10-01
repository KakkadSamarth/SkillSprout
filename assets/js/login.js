// ============================================================
// FILE: assets/js/login.js
// PURPOSE: Client-side validation for the login form.
//          Checks that email and password fields are filled
//          before allowing form submission.
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    // Get the login form element by its ID
    const form = document.getElementById('loginForm');

    // Only add listener if the form exists on this page
    if (form) {
        // Listen for the form's "submit" event
        form.addEventListener('submit', function(event) {
            // Get the values from the input fields
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;

            // Check if email is empty
            if (email === '') {
                alert('Please enter your email address.');
                event.preventDefault(); // Stop form from submitting
                return;
            }

            // Basic email format check using regex
            // This pattern checks for: something@something.something
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                alert('Please enter a valid email address.');
                event.preventDefault();
                return;
            }

            // Check if password is empty
            if (password === '') {
                alert('Please enter your password.');
                event.preventDefault();
                return;
            }
        });
    }
});
