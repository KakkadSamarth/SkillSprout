<?php
// ============================================================
// FILE: admin/login.php
// PURPOSE: Dedicated admin login page. Authenticates against
//          the users table but only allows users with role
//          'admin' or 'moderator' to proceed.
// ============================================================

session_start();

// If already logged in as admin, go to admin dashboard
if (isset($_SESSION["user_id"]) && isset($_SESSION["role"]) &&
    ($_SESSION["role"] === 'admin' || $_SESSION["role"] === 'moderator')) {
    header("Location: dashboard.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $sql  = "SELECT user_id, name, email, password, role, status FROM users WHERE email = ? AND role IN ('admin','moderator')";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) === 1) {
            $admin = mysqli_fetch_assoc($result);

            if ($admin["status"] !== 'active') {
                $error = "This admin account is not active.";
            } elseif (password_verify($password, $admin["password"])) {
                $_SESSION["user_id"] = $admin["user_id"];
                $_SESSION["name"]    = $admin["name"];
                $_SESSION["email"]   = $admin["email"];
                $_SESSION["role"]    = $admin["role"];

                // Log admin login
                $log_sql = "INSERT INTO admin_logs (admin_id, action, ip_address) VALUES (?, 'Admin Login', ?)";
                $log_stmt = mysqli_prepare($conn, $log_sql);
                $ip = $_SERVER["REMOTE_ADDR"];
                mysqli_stmt_bind_param($log_stmt, "is", $admin["user_id"], $ip);
                mysqli_stmt_execute($log_stmt);

                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid credentials.";
            }
        } else {
            $error = "Invalid credentials or insufficient privileges.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Skill Sprout</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-body">
    <section class="section">
        <div class="container">
            <div class="form-container admin-login-form">
                <div class="admin-logo">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <h1 class="form-title">Admin Portal</h1>
                <p class="form-subtitle">Skill Sprout Administration</p>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Admin Email</label>
                        <input type="email" id="email" name="email" placeholder="admin@skillsprout.com" required
                               value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="password"><i class="fas fa-lock"></i> Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter admin password" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-sign-in-alt"></i> Access Admin Panel
                    </button>
                </form>

                <p class="form-footer">
                    <a href="<?php echo BASE_URL; ?>/"><i class="fas fa-arrow-left"></i> Back to Main Site</a>
                </p>
            </div>
        </div>
    </section>
</body>
</html>
