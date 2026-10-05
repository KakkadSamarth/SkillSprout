<?php
// ============================================================
// FILE: auth/register.php
// PURPOSE: New user registration page. Validates input fields,
//          hashes the password securely with bcrypt, inserts the
//          new user into the database with 100 WP bonus, logs
//          the bonus transaction, and redirects to login.
// ============================================================

session_start();

// If already logged in, go to dashboard
if (isset($_SESSION["user_id"])) {
    header("Location: ../user/dashboard.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';

$error   = "";
$success = "";

// --- PROCESS REGISTRATION FORM ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Collect and sanitize form inputs
    $name     = trim($_POST["name"]);
    $email    = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm  = $_POST["confirm_password"];

    // --- Validation Checks ---
    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $error = "All fields are required.";
    } elseif (strlen($name) < 3) {
        // Name must be at least 3 characters
        $error = "Name must be at least 3 characters long.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Check if email format is valid using PHP's built-in filter
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        // Password must be at least 6 characters
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm) {
        // Passwords must match
        $error = "Passwords do not match.";
    } else {
        // --- Check if email already exists ---
        $check_sql  = "SELECT user_id FROM users WHERE email = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "s", $email);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($check_result) > 0) {
            $error = "An account with this email already exists.";
        } else {
            // --- Hash the password ---
            // password_hash() with PASSWORD_DEFAULT uses bcrypt algorithm
            // It automatically generates a random salt and embeds it in the hash
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // --- Start Transaction ---
            // We use a transaction to ensure both the user insertion AND
            // the bonus transaction log happen together (or neither does)
            mysqli_begin_transaction($conn);

            try {
                // Insert the new user with 100 WP starting balance
                $insert_sql = "INSERT INTO users (name, email, password, wp_balance) VALUES (?, ?, ?, 100)";
                $insert_stmt = mysqli_prepare($conn, $insert_sql);
                mysqli_stmt_bind_param($insert_stmt, "sss", $name, $email, $hashed_password);
                mysqli_stmt_execute($insert_stmt);

                // Get the newly created user's ID
                $new_user_id = mysqli_insert_id($conn);

                // Log the signup bonus transaction
                $trans_sql = "INSERT INTO transactions (user_id, type, amount_wp, description) VALUES (?, 'SIGNUP_BONUS', 100, 'Welcome bonus: 100 WP on registration')";
                $trans_stmt = mysqli_prepare($conn, $trans_sql);
                mysqli_stmt_bind_param($trans_stmt, "i", $new_user_id);
                mysqli_stmt_execute($trans_stmt);

                // Commit the transaction — both operations succeeded
                mysqli_commit($conn);

                $success = "Registration successful! You've received 100 Work Points. Please login.";

            } catch (Exception $e) {
                // Something went wrong — undo all changes
                mysqli_rollback($conn);
                $error = "Registration failed. Please try again.";
            }
        }

        mysqli_stmt_close($check_stmt);
    }
}
?>

<?php include __DIR__ . '/../includes/header1.php'; ?>

<section class="section">
    <div class="container">
        <div class="form-container">
            <h1 class="form-title">Create Your Account</h1>
            <p class="form-subtitle">Start your skill exchange journey with 100 free Work Points</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" id="registerForm">
                <div class="form-group">
                    <label for="name"><i class="fas fa-user"></i> Full Name</label>
                    <input type="text" id="name" name="name" placeholder="John Doe" 
                           value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required minlength="3">
                </div>

                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" id="email" name="email" placeholder="you@example.com" 
                           value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" placeholder="Minimum 6 characters" required minlength="6">
                </div>

                <div class="form-group">
                    <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>

            <p class="form-footer">
                Already have an account? <a href="<?php echo BASE_URL; ?>/auth/login.php">Login here</a>
            </p>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer1.php'; ?>
