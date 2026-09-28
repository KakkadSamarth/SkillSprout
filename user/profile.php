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
    <div class="card profile-card">
        <h1>User Profile</h1>
        <table class="profile-table">
            <tr>
                <td>Name:</td>
                <td><?php echo htmlspecialchars($user["name"]); ?></td>
            </tr>
            <tr>
                <td>Email:</td>
                <td><?php echo htmlspecialchars($user["email"]); ?></td>
            </tr>
            <tr>
                <td>WP Balance:</td>
                <td><?php echo (int) $user["wp_balance"]; ?> WP (&#8377;<?php echo number_format($user["wp_balance"]); ?>)</td>
            </tr>
            <tr>
                <td>Member Since:</td>
                <td><?php echo htmlspecialchars($user["created_at"]); ?></td>
            </tr>
        </table>
        <div class="text-center mt-2">
            <a href="<?= BASE_URL ?>auth/logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>
</main>

<?php include __DIR__ . "/../includes/footer2.php"; ?>

</body>
</html>
