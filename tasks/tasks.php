<?php
// ============================================================
// FILE: tasks/tasks.php
// PURPOSE: Browse all OPEN tasks available on the platform.
//          Displays task cards with title, domain, reward,
//          deadline, and required skills. Includes search filter.
// ============================================================

include '../includes/header2.php';

// --- Search/Filter ---
$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";
$domain_filter = isset($_GET["domain"]) ? trim($_GET["domain"]) : "";

// Build query dynamically based on filters
$sql = "SELECT t.*, u.name as creator_name FROM tasks t JOIN users u ON t.creator_id = u.user_id WHERE t.status = 'OPEN'";
$params = [];
$types  = "";

if (!empty($search)) {
    $sql .= " AND (t.title LIKE ? OR t.description LIKE ? OR t.skills_required LIKE ?)";
    $search_param = "%" . $search . "%";
    $params[] = &$search_param;
    $params[] = &$search_param;
    $params[] = &$search_param;
    $types .= "sss";
}

if (!empty($domain_filter)) {
    $sql .= " AND t.domain = ?";
    $params[] = &$domain_filter;
    $types .= "s";
}

$sql .= " ORDER BY t.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);

if (!empty($types)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$tasks = mysqli_stmt_get_result($stmt);

// Fetch skill categories for filter dropdown
$cats = mysqli_query($conn, "SELECT * FROM skill_categories ORDER BY name");
?>

<section class="section">
    <div class="container">
        <h1 class="page-title">Browse Tasks</h1>
        <p class="page-subtitle">Find tasks that match your skills and start earning</p>

        <!-- Search & Filter Bar -->
        <form class="search-bar" method="GET" action="">
            <div class="search-input-group">
                <input type="text" name="search" placeholder="Search tasks..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <select name="domain">
                    <option value="">All Categories</option>
                    <?php while ($cat = mysqli_fetch_assoc($cats)): ?>
                        <option value="<?php echo htmlspecialchars($cat["name"]); ?>"
                            <?php echo ($domain_filter === $cat["name"]) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat["name"]); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
            </div>
        </form>

        <!-- Task Cards Grid -->
        <?php if (mysqli_num_rows($tasks) > 0): ?>
            <div class="task-grid">
                <?php while ($task = mysqli_fetch_assoc($tasks)): ?>
                    <div class="task-card">
                        <div class="task-card-header">
                            <h3><?php echo htmlspecialchars($task["title"]); ?></h3>
                            <span class="badge badge-open">OPEN</span>
                        </div>
                        <?php if ($task["domain"]): ?>
                            <p class="task-domain"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($task["domain"]); ?></p>
                        <?php endif; ?>
                        <p class="task-description"><?php echo htmlspecialchars(substr($task["description"], 0, 120)) . "..."; ?></p>
                        <div class="task-meta">
                            <span class="task-reward"><i class="fas fa-coins"></i> <?php echo $task["reward"]; ?> WP (Rs. <?php echo $task["reward"]; ?>)</span>
                            <?php if ($task["deadline"]): ?>
                                <span class="task-deadline"><i class="fas fa-clock"></i> <?php echo date("M d, Y", strtotime($task["deadline"])); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($task["skills_required"]): ?>
                            <div class="task-skills">
                                <?php foreach (explode(",", $task["skills_required"]) as $skill): ?>
                                    <span class="skill-tag"><?php echo htmlspecialchars(trim($skill)); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="task-card-footer">
                            <span class="task-author"><i class="fas fa-user"></i> <?php echo htmlspecialchars($task["creator_name"]); ?></span>
                            <a href="<?php echo BASE_URL; ?>/tasks/task_details.php?id=<?php echo $task["task_id"]; ?>" class="btn btn-sm btn-primary">View Details</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <p>No open tasks found. Check back later or post your own!</p>
                <a href="<?php echo BASE_URL; ?>/tasks/create_task.php" class="btn btn-primary">Post a Task</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include '../includes/footer2.php'; ?>
