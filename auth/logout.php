<?php
// ============================================================
// FILE: auth/logout.php
// PURPOSE: Destroys the user session, clears session cookies,
//          and redirects the user to the login page.
//          This logs out both regular users and admins.
// ============================================================

session_start();
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();  
    setcookie(
        session_name(),        
        '',                    
        time() - 42000,       
        $params["path"],       
        $params["domain"],    
        $params["secure"],    
        $params["httponly"]    
    );
}

session_destroy();
header("Location: login.php");
exit();
?>
