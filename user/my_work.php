<?php
// ============================================================
// FILE: user/my_work.php
// PURPOSE: Consolidated work dashboard showing three tabs:
//          1. Tasks I Created — tasks this user posted
//          2. My Applications — tasks this user applied for
//          3. Assigned Work — tasks assigned to this user
// ============================================================

include '../includes/header2.php';

// --- Fetch tasks created by this user ---
$created_sql = "SELECT * FROM tasks WHERE creator_id = ? ORDER BY created_at DESC";
$created_stmt = mysqli_prepare($conn, $created_sql);
mysqli_stmt_bind_param($created_stmt, "i", $session_user_id);
mysqli_stmt_execute($created_stmt);
$created_tasks = mysqli_stmt_get_result($created_stmt);

// --- Fetch applications submitted by this user ---
$apps_sql = "SELECT a.*, t.title, t.reward, t.status as task_status 
             FROM applications a 
             JOIN tasks t ON a.task_id = t.task_id 
             WHERE a.user_id = ? 
             ORDER BY a.applied_at DESC";
$apps_stmt = mysqli_prepare($conn, $apps_sql);
mysqli_stmt_bind_param($apps_stmt, "i", $session_user_id);
mysqli_stmt_execute($apps_stmt);
$my_applications = mysqli_stmt_get_result($apps_stmt);

// --- Fetch tasks assigned to this user ---
$work_sql = "SELECT * FROM tasks WHERE assigned_user_id = ? AND status IN ('ASSIGNED','SUBMITTED') ORDER BY updated_at DESC";
$work_stmt = mysqli_prepare($conn, $work_sql);
mysqli_stmt_bind_param($work_stmt, "i", $session_user_id);
mysqli_stmt_execute($work_stmt);
$my_work = mysqli_stmt_get_result($work_stmt);
?>

<section class="section">
    <div class="container">
        <h1 class="page-title">My Work</h1>
        <p class="page-subtitle">Manage everything in one place</p>

        <!-- Tab Navigation -->
        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('created')">Tasks I Created</button>
            <button class="tab-btn" onclick="showTab('applications')">My Applications</button>
            <button class="tab-btn" onclick="showTab('assigned')">Assigned Work</button>
        </div>

        <!-- Tab 1: Tasks I Created -->
        <div class="tab-content active" id="tab-created">
            <?php if (mysqli_num_rows($created_tasks) > 0): ?>
                <div class="task-list">
                    <?php while ($task = mysqli_fetch_assoc($created_tasks)): ?>
                        <div class="task-card">
                            <div class="task-card-header">
                                <h3><?php echo htmlspecialchars($task["title"]); ?></h3>
                                <span class="badge badge-<?php echo strtolower($task["status"]); ?>">
                                    <?php echo $task["status"]; ?>
                                </span>
                            </div>
                            <p class="task-reward"><i class="fas fa-coins"></i> <?php echo $task["reward"]; ?> WP</p>
                            <a href="<?php echo BASE_URL; ?>/tasks/task_details.php?id=<?php echo $task["task_id"]; ?>" class="btn btn-sm btn-outline">View Details</a>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <p>You haven't created any tasks yet.</p>
                    <a href="<?php echo BASE_URL; ?>/tasks/create_task.php" class="btn btn-primary">Post Your First Task</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab 2: My Applications -->
        <div class="tab-content" id="tab-applications">
            <?php if (mysqli_num_rows($my_applications) > 0): ?>
                <div class="task-list">
                    <?php while ($app = mysqli_fetch_assoc($my_applications)): ?>
                        <div class="task-card">
                            <div class="task-card-header">
                                <h3><?php echo htmlspecialchars($app["title"]); ?></h3>
                                <span class="badge badge-<?php echo strtolower($app["status"]); ?>">
                                    <?php echo $app["status"]; ?>
                                </span>
                            </div>
                            <p class="task-reward"><i class="fas fa-coins"></i> <?php echo $app["reward"]; ?> WP</p>
                            <a href="<?php echo BASE_URL; ?>/tasks/task_details.php?id=<?php echo $app["task_id"]; ?>" class="btn btn-sm btn-outline">View Task</a>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-paper-plane"></i>
                    <p>You haven't applied for any tasks yet.</p>
                    <a href="<?php echo BASE_URL; ?>/tasks/tasks.php" class="btn btn-primary">Browse Tasks</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab 3: Assigned Work -->
        <div class="tab-content" id="tab-assigned">
            <?php if (mysqli_num_rows($my_work) > 0): ?>
                <div class="task-list">
                    <?php while ($work = mysqli_fetch_assoc($my_work)): ?>
                        <div class="task-card">
                            <div class="task-card-header">
                                <h3><?php echo htmlspecialchars($work["title"]); ?></h3>
                                <span class="badge badge-<?php echo strtolower($work["status"]); ?>">
                                    <?php echo $work["status"]; ?>
                                </span>
                            </div>
                            <p class="task-reward"><i class="fas fa-coins"></i> <?php echo $work["reward"]; ?> WP</p>
                            <div class="task-actions">
                                <a href="<?php echo BASE_URL; ?>/tasks/task_details.php?id=<?php echo $work["task_id"]; ?>" class="btn btn-sm btn-outline">View</a>
                                <?php if ($work["status"] === 'ASSIGNED'): ?>
                                    <a href="<?php echo BASE_URL; ?>/tasks/submit_work.php?id=<?php echo $work["task_id"]; ?>" class="btn btn-sm btn-primary">Submit Work</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-briefcase"></i>
                    <p>No work currently assigned to you.</p>
                    <a href="<?php echo BASE_URL; ?>/tasks/tasks.php" class="btn btn-primary">Find Work</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Tab switching JavaScript -->
<script>
    // This function switches between tabs
    function showTab(tabName) {
        // Hide all tab content panels
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
        // Deactivate all tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        // Show the selected tab content
        document.getElementById('tab-' + tabName).classList.add('active');
        // Mark the clicked button as active
        event.target.classList.add('active');
    }
</script>

<?php include '../includes/footer2.php'; ?>
