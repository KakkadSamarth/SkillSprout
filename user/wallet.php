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

@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `transactions` (
    `transaction_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `amount_wp` INT NOT NULL,
    `price_paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `payment_method` VARCHAR(150) NOT NULL DEFAULT 'Mock Card / Test Payment',
    `status` ENUM('COMPLETED', 'PENDING', 'FAILED') NOT NULL DEFAULT 'COMPLETED',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_transactions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$col_res = @mysqli_query($conn, "SHOW COLUMNS FROM users");
$has_name = false;
$has_username = false;
if ($col_res) {
    while ($c = mysqli_fetch_assoc($col_res)) {
        if ($c['Field'] === 'name') $has_name = true;
        if ($c['Field'] === 'username') $has_username = true;
    }
}
$name_expr = $has_name ? "name" : ($has_username ? "username AS name" : "email AS name");
$sql = "SELECT $name_expr, email, wp_balance FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

$txn_sql = "SELECT transaction_id, amount_wp, price_paid, payment_method, status, created_at FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
$txn_stmt = mysqli_prepare($conn, $txn_sql);
mysqli_stmt_bind_param($txn_stmt, "i", $user_id);
mysqli_stmt_execute($txn_stmt);
$transactions = mysqli_stmt_get_result($txn_stmt);

include __DIR__ . "/../includes/header2.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Wallet - SkillSprout</title>
    <style>
        
    </style>
</head>
<body>

<main>
    <div class="wallet-card">
        <?php if (isset($_GET['success']) && $_GET['success'] === 'purchased'): ?>
            <div class="alert-success">
                &check; Payment Successful! Added +<?php echo (int)($_GET['wp'] ?? 0); ?> Work Points (&#8377;<?php echo number_format((float)($_GET['rupees'] ?? $_GET['wp'] ?? 0), 2); ?>) to your wallet.
            </div>
        <?php endif; ?>

        <div class="rate-tag">
            Exchange Rate: &#8377;1 Rupee = 1 WorkPoint (WP)
        </div>

        <h1 style="margin-top: 0;">SkillSprout Wallet</h1>
        <p>Current balance for <strong><?php echo htmlspecialchars($user["name"]); ?></strong></p>
        
        <div style="font-size: 44px; font-weight: bold; margin: 15px 0 5px; color: #2e7d32;">
            <?php echo (int) $user["wp_balance"]; ?> WP
        </div>
        <div style="font-size: 18px; color: #555; margin-bottom: 15px;">
            (Equivalent Value: <strong>&#8377;<?php echo number_format($user["wp_balance"], 2); ?></strong>)
        </div>

        <p style="color: #666; font-size: 14px;">
            Complete tasks to earn Work Points, or top-up your balance to post tasks and reward skilled contributors!
        </p>

        <div style="margin-top: 25px; display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
            <a href="<?= BASE_URL ?>user/purchase_wallet.php" style="padding: 12px 24px; background-color: #2e7d32; color: white; text-decoration: none; font-weight: bold; border-radius: 4px; display: inline-block;">+ Purchase Work Points</a>
            <a href="<?= BASE_URL ?>tasks/tasks.php" style="padding: 12px 20px; background-color: black; color: white; text-decoration: none; border-radius: 4px; display: inline-block;">Find Tasks</a>
            <a href="<?= BASE_URL ?>tasks/create_task.php" style="padding: 12px 20px; background-color: #333; color: white; text-decoration: none; border-radius: 4px; display: inline-block;">Create Task</a>
        </div>
    </div>

    <div class="wallet-history">
        <h3 style="margin-top: 0; border-bottom: 2px solid #eee; padding-bottom: 10px;">Purchase &amp; Top-up History</h3>
        <?php if ($transactions && mysqli_num_rows($transactions) > 0): ?>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Work Points</th>
                        <th>Amount Paid (&#8377;)</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($txn = mysqli_fetch_assoc($transactions)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(date('M d, Y H:i', strtotime($txn['created_at']))); ?></td>
                            <td style="color: #2e7d32; font-weight: bold;">+<?php echo (int)$txn['amount_wp']; ?> WP</td>
                            <td>&#8377;<?php echo number_format($txn['price_paid'], 2); ?></td>
                            <td><?php echo htmlspecialchars($txn['payment_method']); ?></td>
                            <td><span class="badge-success"><?php echo htmlspecialchars($txn['status']); ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color: #888; font-size: 14px; text-align: center; margin: 20px 0;">
                No purchase transactions yet. Click &ldquo;Purchase Work Points&rdquo; above to add funds.
            </p>
        <?php endif; ?>
    </div>
</main>

<?php
if ($txn_stmt) {
    mysqli_stmt_close($txn_stmt);
}
include __DIR__ . "/../includes/footer2.php";
?>

</body>
</html>
