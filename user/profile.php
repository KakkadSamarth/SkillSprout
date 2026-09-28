<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . "/../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

$sql = "SELECT name, email, wp_balance, created_at FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

include __DIR__ . "/../includes/header2.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Profile - SkillSprout</title>
</head>
<body>

<main>
    <div style="max-width: 600px; margin: 40px auto; padding: 25px; border: 1px solid #ccc;">
        <h1 style="text-align: center;">User Profile</h1>
        <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
            <tr>
                <td style="padding: 10px; font-weight: bold; border-bottom: 1px solid #eee;">Name:</td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo htmlspecialchars($user["name"]); ?></td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold; border-bottom: 1px solid #eee;">Email:</td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo htmlspecialchars($user["email"]); ?></td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold; border-bottom: 1px solid #eee;">WP Balance:</td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo (int) $user["wp_balance"]; ?> WP</td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold; border-bottom: 1px solid #eee;">Member Since:</td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo htmlspecialchars($user["created_at"]); ?></td>
            </tr>
        </table>
        <div style="text-align: center; margin-top: 25px;">
            <a href="<?= BASE_URL ?>auth/logout.php" style="padding: 10px 20px; background-color: #d32f2f; color: white; text-decoration: none;">Logout</a>
        </div>
    </div>
</main>

<?php include __DIR__ . "/../includes/footer2.php"; ?>

</body>
</html>
