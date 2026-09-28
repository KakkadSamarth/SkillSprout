<?php
session_start();

include __DIR__ . "/../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

$sql = "SELECT name, email, wp_balance FROM users WHERE user_id = ?";
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
    <title>My Wallet - SkillSprout</title>
</head>
<body>

<main>
    <div style="max-width: 600px; margin: 40px auto; padding: 25px; border: 1px solid #ccc; text-align: center;">
        <h1>SkillSprout Wallet</h1>
        <p>Current balance for <strong><?php echo htmlspecialchars($user["name"]); ?></strong></p>
        <div style="font-size: 42px; font-weight: bold; margin: 20px 0; color: #2e7d32;">
            <?php echo (int) $user["wp_balance"]; ?> WP
        </div>
        <p>Complete tasks to earn more Work Points, or use your points to post tasks!</p>
        <p style="margin-top: 25px;">
            <a href="<?= BASE_URL ?>tasks/tasks.php" style="padding: 10px 20px; background-color: black; color: white; text-decoration: none; margin-right: 10px;">Find Tasks</a>
            <a href="<?= BASE_URL ?>tasks/create_task.php" style="padding: 10px 20px; background-color: #333; color: white; text-decoration: none;">Create Task</a>
        </p>
    </div>
</main>

<?php include __DIR__ . "/../includes/footer2.php"; ?>

</body>
</html>
