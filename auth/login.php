<?php
// ============================================================
// FILE: auth/login.php
// PURPOSE: User login page. Displays a login form and processes
//          submitted credentials. Uses prepared statements to
//          prevent SQL injection, and password_verify() to
//          securely check the hashed password.
// ============================================================

// Start session for storing login state
session_start();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION["user_id"])) {
    // Check if the user is an admin — redirect to admin dashboard
    if (isset($_SESSION["role"]) && ($_SESSION["role"] === 'admin' || $_SESSION["role"] === 'moderator')) {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: ../user/dashboard.php");
    }
    exit();
}

// Include database connection
require_once '../config/database.php';

// Initialize error message variable
$error = "";

// --- PROCESS LOGIN FORM ---
// This block runs only when the form is submitted (POST request)
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Get the submitted email and password from the form
    $email    = trim($_POST["email"]);    // trim() removes extra whitespace
    $password = $_POST["password"];

    // --- Validate inputs ---
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        // --- Prepare SQL query ---
        // Using a prepared statement (?) prevents SQL injection attacks.
        // We select the user record matching the given email.
        $sql  = "SELECT user_id, name, email, password, role, status, status_reason FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);

        // Bind the email variable to the placeholder (?)
        // "s" means the variable is a string type
        mysqli_stmt_bind_param($stmt, "s", $email);

        // Execute the prepared statement
        mysqli_stmt_execute($stmt);

        // Get the result set
        $result = mysqli_stmt_get_result($stmt);

        // Check if a user with that email exists
        if (mysqli_num_rows($result) === 1) {
            // Fetch the user record as an associative array
            $user = mysqli_fetch_assoc($result);

            // Check if account is banned or suspended
            if ($user["status"] === "banned") {
                $reason_msg = !empty($user["status_reason"]) ? " Reason: " . htmlspecialchars($user["status_reason"]) : "";
                $error = "Your account has been banned." . $reason_msg . " Please contact support.";
            } elseif ($user["status"] === "suspended") {
                $reason_msg = !empty($user["status_reason"]) ? " Reason: " . htmlspecialchars($user["status_reason"]) : "";
                $error = "Your account is temporarily suspended." . $reason_msg . " Please contact support.";
            }
            // --- Verify password ---
            // password_verify() compares the plain-text password with the stored hash
            elseif (password_verify($password, $user["password"])) {
                // Password matches! Set session variables
                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["name"]    = $user["name"];
                $_SESSION["email"]   = $user["email"];
                $_SESSION["role"]    = $user["role"];

                // Redirect based on role
                if ($user["role"] === 'admin' || $user["role"] === 'moderator') {
                    header("Location: ../admin/dashboard.php");
                } else {
                    header("Location: ../user/dashboard.php");
                }
                exit();
            } else {
                // Password does not match
                $error = "Invalid email or password.";
            }
        } else {
            // No user found with that email
            $error = "Invalid email or password.";
        }

        // Close the prepared statement
        mysqli_stmt_close($stmt);
    }
}
?>

<?php include '../includes/header1.php'; ?>

<section class="section">
    <div class="container">
        <div class="form-container">
            <h1 class="form-title">Welcome Back</h1>
            <p class="form-subtitle">Login to your Skill Sprout account</p>

            <!-- Display error message if login failed -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form action="" method="POST" id="loginForm">
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" id="email" name="email" placeholder="you@example.com" 
                           value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <p class="form-footer">
                Don't have an account? <a href="<?php echo BASE_URL; ?>/auth/register.php">Register here</a>
            </p>
        </div>
    </div>
</section>

<?php include '../includes/footer1.php'; ?>
