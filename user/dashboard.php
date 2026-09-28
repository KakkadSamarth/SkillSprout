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
    <title>Dashboard - SkillSprout</title>
</head>
<body>

<main>

    <table class="dashboard-layout">

        <tr>

            <td colspan="3" class="welcome-box">

                <h1>
                    Welcome back, <?php echo htmlspecialchars($user["name"]); ?>!
                </h1>

                <p>
                    Find tasks, complete work and earn Work Points.
                </p>

            </td>

        </tr>

        <tr>

            <td colspan="3">

                <h2>Quick Actions</h2>

            </td>

        </tr>

        <tr>

            <td class="dashboard-card">
                <a href="<?= BASE_URL ?>tasks/tasks.php" class="dashboard-action-link">
                    <label class="action-tag">
                        <h3>Find Tasks</h3>
                        <p>Browse available tasks and apply for work.</p>
                        <span class="action-tag-badge">Explore Tasks &rarr;</span>
                    </label>
                </a>
            </td>

            <td class="dashboard-card">
                <a href="<?= BASE_URL ?>tasks/create_task.php" class="dashboard-action-link">
                    <label class="action-tag">
                        <h3>Create Task</h3>
                        <p>Post a task and offer Work Points.</p>
                        <span class="action-tag-badge">Create Task &rarr;</span>
                    </label>
                </a>
            </td>

            <td class="dashboard-card">
                <a href="<?= BASE_URL ?>user/my_work.php" class="dashboard-action-link">
                    <label class="action-tag">
                        <h3>My Work</h3>
                        <p>View your applications and assigned tasks.</p>
                        <span class="action-tag-badge">View My Work &rarr;</span>
                    </label>
                </a>
            </td>

        </tr>

        <tr>

            <td colspan="3">

                <h2>Your Overview</h2>

            </td>

        </tr>

        <tr>

            <td class="overview-card">

                <h3>Work Points</h3>

                <p>
                    <?php echo (int) $user["wp_balance"]; ?> WP (&#8377;<?php echo number_format($user["wp_balance"]); ?>)
                </p>

            </td>

            <td class="overview-card">

                <h3>Tasks Completed</h3>

                <p>0</p>

            </td>

            <td class="overview-card">

                <h3>Tasks Created</h3>

                <p>0</p>

            </td>

        </tr>

        <tr>

            <td colspan="3" class="activity-box">

                <h2>Recent Activity</h2>

                <p>No recent activity.</p>

            </td>

        </tr>

    </table>

</main>

<?php include __DIR__ . "/../includes/footer2.php"; ?>

</body>
</html>
