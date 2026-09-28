
function validateLoginForm() {

    // Get values from form
    var email = document.getElementById("email").value.trim();
    var password = document.getElementById("password").value;


    // Check email
    if (email == "") {
        alert("Please enter your email.");
        return false;
    }


    // Email pattern
    var emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;


    // Check email format
    if (!emailPattern.test(email)) {
        alert("Please enter a valid email address.");
        return false;
    }


    // Check password
    if (password == "") {
        alert("Please enter your password.");
        return false;
    }


    // Check password length
    if (password.length < 6) {
        alert("Password must be at least 6 characters.");
        return false;
    }


    // Form is valid
    return true;
}

