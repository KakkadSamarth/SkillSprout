function validateRegisterForm() {

    // Get values from the form
    var name = document.getElementById("name").value.trim();
    var email = document.getElementById("email").value.trim();
    var password = document.getElementById("password").value;
    var confirmPassword = document.getElementById("confirm_password").value;


    // Check name
    if (name == "") {
        alert("Please enter your name.");
        return false;
    }


    // Check name length
    if (name.length < 3) {
        alert("Name must be at least 3 characters.");
        return false;
    }


    // Check email 
    if (email == "")  { 
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


    // Check confirm password
    if (confirmPassword == "") {
        alert("Please confirm your password.");
        return false;
    }


    // Check passwords match
    if (password != confirmPassword) {
        alert("Passwords do not match.");
        return false;
    }


    // Everything is valid
    return true;
}

