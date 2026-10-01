<?php
// ============================================================
// FILE: auth/logout.php
// PURPOSE: Destroys the user session, clears session cookies,
//          and redirects the user to the login page.
//          This logs out both regular users and admins.
// ============================================================

// Start the session so we can access and destroy it
session_start();

// --- Clear all session variables ---
// $_SESSION is an array holding all session data.
// Setting it to an empty array removes all stored values.
$_SESSION = array();

// --- Delete the session cookie ---
// If the session uses cookies (which is default), we need to
// delete the cookie by setting its expiration to the past.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();  // Get current cookie settings
    setcookie(
        session_name(),        // The cookie name (usually PHPSESSID)
        '',                    // Empty value
        time() - 42000,        // Expiration in the past (deletes it)
        $params["path"],       // Cookie path
        $params["domain"],     // Cookie domain
        $params["secure"],     // HTTPS only?
        $params["httponly"]    // JavaScript access blocked?
    );
}

// --- Destroy the session on the server ---
// This removes the session file from the server
session_destroy();

// --- Redirect to login page ---
// We use a relative path here since BASE_URL requires database.php
// which we don't need for a simple redirect
header("Location: login.php");
exit();
?>
