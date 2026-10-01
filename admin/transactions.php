<?php
// ============================================================
// FILE: admin/transactions.php
// PURPOSE: Admin transaction ledger. View all WP transactions
//          across the platform — signups, purchases, payouts,
//          refunds, and admin adjustments.
// ============================================================

include '../includes/header3.php';

// Filter by type
$type_filter = isset($_GET["type"]) ? $_GET["type"] : "";

$sql = "SELECT t.*, u.name, u.email FROM transactions t JOIN users u ON t.user_id = u.user_id";
if (!empty($type_filter)) {
    $sql .= " WHERE t.type = '" . mysqli_real_escape_string($conn, $type_filter) . "'";
}
$sql .= " ORDER BY t.created_at DESC LIMIT 100";
$transactions = mysqli_query($conn, $sql);

// Total WP stats
$total_credits = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount_wp), 0) as total FROM transactions WHERE amount_wp > 0"))["total"];
$total_debits  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount_wp), 0) as total FROM transactions WHERE amount_wp < 0"))["total"];
?>

<section class="section">
    <div class="container">
        <h1 class="page-title">Transaction Ledger</h1>

        <!-- Summary Cards -->
        <div class="dashboard-stats">
            <div class="stat-card accent-green">
                <div class="stat-icon"><i class="fas fa-arrow-down"></i></div>
                <div class="stat-info">
                    <div class="stat-number">+<?php echo number_format($total_credits); ?></div>
                    <div class="stat-label">Total Credits (Rs. <?php echo number_format($total_credits); ?>)</div>
                </div>
            </div>
            <div class="stat-card accent-red">
                <div class="stat-icon"><i class="fas fa-arrow-up"></i></div>
                <div class="stat-info">
                    <div class="stat-number"><?php echo number_format($total_debits); ?></div>
                    <div class="stat-label">Total Debits (Rs. <?php echo number_format(abs($total_debits)); ?>)</div>
                </div>
            </div>
        </div>

        <!-- Type Filter -->
        <div class="tabs">
            <a href="?type=" class="tab-btn <?php echo empty($type_filter) ? 'active' : ''; ?>">All</a>
            <a href="?type=SIGNUP_BONUS" class="tab-btn <?php echo $type_filter === 'SIGNUP_BONUS' ? 'active' : ''; ?>">Signups</a>
            <a href="?type=PURCHASE" class="tab-btn <?php echo $type_filter === 'PURCHASE' ? 'active' : ''; ?>">Purchases</a>
            <a href="?type=ESCROW_LOCK" class="tab-btn <?php echo $type_filter === 'ESCROW_LOCK' ? 'active' : ''; ?>">Escrow</a>
            <a href="?type=PAYOUT" class="tab-btn <?php echo $type_filter === 'PAYOUT' ? 'active' : ''; ?>">Payouts</a>
            <a href="?type=REFUND" class="tab-btn <?php echo $type_filter === 'REFUND' ? 'active' : ''; ?>">Refunds</a>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($tx = mysqli_fetch_assoc($transactions)): ?>
                        <tr>
                            <td>#<?php echo $tx["transaction_id"]; ?></td>
                            <td><?php echo htmlspecialchars($tx["name"]); ?></td>
                            <td><span class="badge"><?php echo str_replace("_", " ", $tx["type"]); ?></span></td>
                            <td class="<?php echo ($tx["amount_wp"] >= 0) ? 'text-green' : 'text-red'; ?>">
                                <?php echo ($tx["amount_wp"] >= 0) ? '+' : ''; ?><?php echo $tx["amount_wp"]; ?> WP <span class="text-muted">(Rs. <?php echo abs($tx["amount_wp"]); ?>)</span>
                            </td>
                            <td><?php echo htmlspecialchars($tx["description"] ?? "—"); ?></td>
                            <td><?php echo date("M d, Y H:i", strtotime($tx["created_at"])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php include '../includes/footer3.php'; ?>
