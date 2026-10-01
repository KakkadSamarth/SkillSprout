<?php
// ============================================================
// FILE: admin/logout.php
// PURPOSE: Logs out the admin, logs the action, destroys
//          session, and redirects to admin login page.
// ============================================================

session_start();

// Log admin logout before destroying session
if (isset($_SESSION["user_id"])) {
    require_once '../config/database.php';
    $log_sql = "INSERT INTO admin_logs (admin_id, action, ip_address) VALUES (?, 'Admin Logout', ?)";
    $log_stmt = mysqli_prepare($conn, $log_sql);
    $ip = $_SERVER["REMOTE_ADDR"];
    mysqli_stmt_bind_param($log_stmt, "is", $_SESSION["user_id"], $ip);
    mysqli_stmt_execute($log_stmt);
}

// Clear and destroy session
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
header("Location: login.php");
exit();
?>
