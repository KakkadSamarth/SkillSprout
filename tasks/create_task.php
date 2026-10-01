<?php
// ============================================================
// FILE: tasks/create_task.php
// PURPOSE: Task creation form. When a user posts a task, their
//          WP is atomically deducted (escrowed) using a database
//          transaction. If they don't have enough WP, the
//          transaction is rolled back and an error is shown.
// ============================================================

include '../includes/header2.php';

$error   = "";
$success = "";

// Fetch user's current balance
$bal_sql  = "SELECT wp_balance FROM users WHERE user_id = ?";
$bal_stmt = mysqli_prepare($conn, $bal_sql);
mysqli_stmt_bind_param($bal_stmt, "i", $session_user_id);
mysqli_stmt_execute($bal_stmt);
$balance = mysqli_fetch_assoc(mysqli_stmt_get_result($bal_stmt))["wp_balance"];

// Fetch skill categories for the domain dropdown
$cats = mysqli_query($conn, "SELECT * FROM skill_categories ORDER BY name");

// --- Process Task Creation ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title       = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $domain      = trim($_POST["domain"]);
    $skills      = trim($_POST["skills_required"]);
    $reward      = intval($_POST["reward"]);
    $deadline    = !empty($_POST["deadline"]) ? $_POST["deadline"] : null;

    // --- Validation ---
    if (empty($title) || empty($description) || $reward <= 0) {
        $error = "Please fill in all required fields and set a reward greater than 0.";
    } elseif ($reward > $balance) {
        $error = "Insufficient Work Points. You have " . $balance . " WP but the reward is " . $reward . " WP.";
    } elseif ($reward < 5) {
        $error = "Minimum reward is 5 WP.";
    } elseif (!empty($deadline) && strtotime($deadline) <= strtotime(date("Y-m-d"))) {
        $error = "Task deadline must be a future date (starting from tomorrow).";
    } else {
        // --- Atomic Escrow Transaction ---
        // Start a database transaction so all operations succeed or fail together
        mysqli_begin_transaction($conn);

        try {
            // Step 1: Deduct WP from user (escrow lock)
            // The WHERE clause includes "wp_balance >= ?" to prevent negative balances
            $deduct_sql = "UPDATE users SET wp_balance = wp_balance - ? WHERE user_id = ? AND wp_balance >= ?";
            $deduct_stmt = mysqli_prepare($conn, $deduct_sql);
            mysqli_stmt_bind_param($deduct_stmt, "iii", $reward, $session_user_id, $reward);
            mysqli_stmt_execute($deduct_stmt);

            // Check if the deduction actually happened
            // If wp_balance was less than reward, no rows would be affected
            if (mysqli_stmt_affected_rows($deduct_stmt) === 0) {
                throw new Exception("Insufficient balance");
            }

            // Step 2: Insert the task
            $task_sql = "INSERT INTO tasks (creator_id, title, description, domain, skills_required, reward, deadline) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $task_stmt = mysqli_prepare($conn, $task_sql);
            mysqli_stmt_bind_param($task_stmt, "issssss", $session_user_id, $title, $description, $domain, $skills, $reward, $deadline);
            mysqli_stmt_execute($task_stmt);
            $new_task_id = mysqli_insert_id($conn);

            // Step 3: Log the escrow lock transaction
            $desc = "Escrowed " . $reward . " WP for task: " . $title;
            $neg_reward = -$reward; // Negative because it's a debit
            $trans_sql = "INSERT INTO transactions (user_id, type, amount_wp, description, reference_id) VALUES (?, 'ESCROW_LOCK', ?, ?, ?)";
            $trans_stmt = mysqli_prepare($conn, $trans_sql);
            mysqli_stmt_bind_param($trans_stmt, "iisi", $session_user_id, $neg_reward, $desc, $new_task_id);
            mysqli_stmt_execute($trans_stmt);

            // All steps succeeded — commit the transaction
            mysqli_commit($conn);

            $success = "Task created successfully! " . $reward . " WP has been escrowed.";
            // Update displayed balance
            $balance -= $reward;

        } catch (Exception $e) {
            // Something went wrong — undo everything
            mysqli_rollback($conn);
            $error = "Failed to create task: " . $e->getMessage();
        }
    }
}
?>

<section class="section">
    <div class="container">
        <div class="form-container form-wide">
            <h1 class="form-title">Post a New Task</h1>
            <p class="form-subtitle">Your available balance: <strong><?php echo number_format($balance); ?> WP</strong></p>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label for="title"><i class="fas fa-heading"></i> Task Title *</label>
                    <input type="text" id="title" name="title" placeholder="e.g., Build a responsive landing page" required
                           value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="description"><i class="fas fa-align-left"></i> Description *</label>
                    <textarea id="description" name="description" rows="6" placeholder="Describe what you need done in detail..." required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="domain"><i class="fas fa-tag"></i> Category</label>
                        <select id="domain" name="domain">
                            <option value="">Select Category</option>
                            <?php
                            // Reset the result pointer to re-loop
                            mysqli_data_seek($cats, 0);
                            while ($cat = mysqli_fetch_assoc($cats)): ?>
                                <option value="<?php echo htmlspecialchars($cat["name"]); ?>"
                                    <?php echo (isset($domain) && $domain === $cat["name"]) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat["name"]); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="reward"><i class="fas fa-coins"></i> Reward (WP) *</label>
                        <input type="number" id="reward" name="reward" min="5" max="<?php echo $balance; ?>" required
                               value="<?php echo isset($reward) ? $reward : ''; ?>" placeholder="Min 5 WP">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="skills_required"><i class="fas fa-tags"></i> Skills Required</label>
                        <input type="text" id="skills_required" name="skills_required" placeholder="PHP, JavaScript, Design (comma separated)"
                               value="<?php echo isset($skills) ? htmlspecialchars($skills) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="deadline"><i class="fas fa-calendar"></i> Deadline</label>
                        <input type="date" id="deadline" name="deadline"
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                               value="<?php echo isset($deadline) ? $deadline : ''; ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-paper-plane"></i> Post Task & Escrow WP
                </button>
            </form>
        </div>
    </div>
</section>

<?php include '../includes/footer2.php'; ?>
