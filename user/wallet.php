<?php
// ============================================================
// FILE: user/wallet.php
// PURPOSE: Displays the user's Work Points balance, recent
//          transaction history, and a link to purchase more WP.
// ============================================================

include '../includes/header2.php';

// Fetch current user balance
$user_sql  = "SELECT wp_balance FROM users WHERE user_id = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $session_user_id);
mysqli_stmt_execute($user_stmt);
$balance = mysqli_fetch_assoc(mysqli_stmt_get_result($user_stmt))["wp_balance"];

// Fetch transaction history for this user (most recent first)
$trans_sql  = "SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 20";
$trans_stmt = mysqli_prepare($conn, $trans_sql);
mysqli_stmt_bind_param($trans_stmt, "i", $session_user_id);
mysqli_stmt_execute($trans_stmt);
$transactions = mysqli_stmt_get_result($trans_stmt);
?>

<section class="section">
    <div class="container">
        <h1 class="page-title">My Wallet</h1>

        <!-- Balance Display Card -->
        <div class="wallet-balance-card">
            <div class="wallet-icon"><i class="fas fa-wallet"></i></div>
            <div class="wallet-info">
                <p class="wallet-label">Available Balance</p>
                <h2 class="wallet-amount"><?php echo number_format($balance); ?> <span>WP</span></h2>
            </div>
            <a href="<?php echo BASE_URL; ?>/user/purchase_wallet.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Top Up
            </a>
        </div>

        <!-- Transaction History Table -->
        <h2 class="section-title">Transaction History</h2>
        <?php if (mysqli_num_rows($transactions) > 0): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($tx = mysqli_fetch_assoc($transactions)): ?>
                            <tr>
                                <td><?php echo date("M d, Y H:i", strtotime($tx["created_at"])); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo ($tx["amount_wp"] >= 0) ? 'open' : 'cancelled'; ?>">
                                        <?php echo str_replace("_", " ", $tx["type"]); ?>
                                    </span>
                                </td>
                                <td class="<?php echo ($tx["amount_wp"] >= 0) ? 'text-green' : 'text-red'; ?>">
                                    <?php echo ($tx["amount_wp"] >= 0) ? '+' : ''; ?><?php echo $tx["amount_wp"]; ?> WP
                                </td>
                                <td><?php echo htmlspecialchars($tx["description"] ?? "—"); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-receipt"></i>
                <p>No transactions yet.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include '../includes/footer2.php'; ?>
