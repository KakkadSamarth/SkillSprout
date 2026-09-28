function validateRegisterForm() {

    var name = document.getElementById("name").value.trim();
    var email = document.getElementById("email").value.trim();
    var password = document.getElementById("password").value;
    var confirmPassword = document.getElementById("confirm_password").value;

    if (name == "") {
        alert("Please enter your name.");
        return false;
    }

    if (name.length < 3) {
        alert("Name must be at least 3 characters.");
        return false;
    }

    if (email == "") { 
        alert("Please enter your email."); 
        return false; 
    } 
    
    var emailPattern = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/; 
    
    if (!emailPattern.test(email)) { 
        alert("Please enter a valid email address."); 
        return false; 
    }

    if (password == "") {
        alert("Please enter your password.");
        return false;
    }

    if (password.length < 6) {
        alert("Password must be at least 6 characters.");
        return false;
    }

    if (confirmPassword == "") {
        alert("Please confirm your password.");
        return false;
    }

    if (password != confirmPassword) {
        alert("Passwords do not match.");
        return false;
    }

    return true;
}
