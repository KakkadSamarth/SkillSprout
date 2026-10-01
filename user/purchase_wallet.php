<?php
// ============================================================
// FILE: user/purchase_wallet.php
// PURPOSE: Work Points purchase page where users can buy WP
//          packages. Processes the purchase by adding WP to
//          the user's balance and logging the transaction.
// ============================================================

include '../includes/header2.php';

$success = "";
$error   = "";

// Define WP packages available for purchase
$packages = [
    ["wp" => 50,   "price" => 1.99,  "label" => "Starter"],
    ["wp" => 150,  "price" => 4.99,  "label" => "Popular"],
    ["wp" => 500,  "price" => 14.99, "label" => "Pro"],
    ["wp" => 1000, "price" => 24.99, "label" => "Enterprise"],
];

// --- Process Purchase ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["package_index"])) {
    $index = intval($_POST["package_index"]);

    // Validate that the selected package exists
    if ($index >= 0 && $index < count($packages)) {
        $pkg = $packages[$index];

        mysqli_begin_transaction($conn);
        try {
            // Add WP to user balance
            $update_sql = "UPDATE users SET wp_balance = wp_balance + ? WHERE user_id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "ii", $pkg["wp"], $session_user_id);
            mysqli_stmt_execute($update_stmt);

            // Log the purchase transaction
            $desc = "Purchased " . $pkg["wp"] . " WP (" . $pkg["label"] . " package)";
            $trans_sql = "INSERT INTO transactions (user_id, type, amount_wp, description, price_paid, payment_method) VALUES (?, 'PURCHASE', ?, ?, ?, 'simulated')";
            $trans_stmt = mysqli_prepare($conn, $trans_sql);
            mysqli_stmt_bind_param($trans_stmt, "iisd", $session_user_id, $pkg["wp"], $desc, $pkg["price"]);
            mysqli_stmt_execute($trans_stmt);

            mysqli_commit($conn);
            $success = "Successfully purchased " . $pkg["wp"] . " Work Points!";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Purchase failed. Please try again.";
        }
    } else {
        $error = "Invalid package selected.";
    }
}
?>

<section class="section">
    <div class="container">
        <h1 class="page-title">Purchase Work Points</h1>
        <p class="page-subtitle">Choose a package to top up your balance</p>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="packages-grid">
            <?php foreach ($packages as $i => $pkg): ?>
                <div class="package-card <?php echo ($i === 1) ? 'package-featured' : ''; ?>">
                    <?php if ($i === 1): ?>
                        <div class="package-badge">Most Popular</div>
                    <?php endif; ?>
                    <h3 class="package-label"><?php echo $pkg["label"]; ?></h3>
                    <div class="package-wp"><?php echo $pkg["wp"]; ?> WP</div>
                    <div class="package-price">$<?php echo number_format($pkg["price"], 2); ?></div>
                    <form action="" method="POST">
                        <input type="hidden" name="package_index" value="<?php echo $i; ?>">
                        <button type="submit" class="btn btn-primary btn-block">Purchase</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include '../includes/footer2.php'; ?>
