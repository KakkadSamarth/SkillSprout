<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . "/../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

$t_cols = [];
$tcol_res = @mysqli_query($conn, "SHOW COLUMNS FROM tasks");
if ($tcol_res) {
    while ($tc = mysqli_fetch_assoc($tcol_res)) {
        $t_cols[$tc['Field']] = true;
    }
}
$f_domain = isset($t_cols['domain']) ? 'domain' : (isset($t_cols['category']) ? 'category AS domain' : "'General' AS domain");
$f_skills = isset($t_cols['required_skills']) ? 'required_skills' : (isset($t_cols['skills']) ? 'skills AS required_skills' : "'' AS required_skills");
$f_reward = isset($t_cols['reward_wp']) ? 'reward_wp' : (isset($t_cols['reward']) ? 'reward AS reward_wp' : (isset($t_cols['points']) ? 'points AS reward_wp' : (isset($t_cols['budget']) ? 'budget AS reward_wp' : '10 AS reward_wp')));
$f_deadline = isset($t_cols['deadline']) ? 'deadline' : 'CURRENT_DATE AS deadline';
$f_status = isset($t_cols['status']) ? 'status' : "'OPEN' AS status";

$sql = "SELECT
            task_id,
            title,
            description,
            $f_domain,
            $f_skills,
            $f_reward,
            $f_deadline,
            created_at
        FROM tasks
        WHERE $f_status = 'OPEN'
        ORDER BY created_at DESC";

$result = mysqli_query($conn, $sql);

include __DIR__ . "/../includes/header2.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tasks - SkillSprout</title>
</head>
<body>

<main>

    <h1>Available Tasks</h1>

    <p>
        Browse available tasks and find work you can complete.
    </p>

    <?php if (mysqli_num_rows($result) > 0) { ?>

        <table class="tasks-table">

            <tr>
                <th>Task</th>
                <th>Domain</th>
                <th>Reward</th>
                <th>Deadline</th>
                <th>Action</th>
            </tr>

            <?php while ($task = mysqli_fetch_assoc($result)) { ?>

                <tr>

                    <td>
                        <strong><?php echo htmlspecialchars($task["title"]); ?></strong>
                        <p><?php echo htmlspecialchars($task["description"]); ?></p>
                        <?php if ($task["required_skills"] != "") { ?>
                            <small>Skills: <?php echo htmlspecialchars($task["required_skills"]); ?></small>
                        <?php } ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($task["domain"]); ?>
                    </td>

                    <td>
                        <?php echo $task["reward_wp"]; ?> WP
                    </td>

                    <td>
                        <?php echo htmlspecialchars($task["deadline"]); ?>
                    </td>

                    <td>
                        <a href="<?= BASE_URL ?>tasks/task_details.php?id=<?php echo $task["task_id"]; ?>">
                            View Details
                        </a>
                    </td>

                </tr>

            <?php } ?>

        </table>

    <?php } else { ?>

        <p>
            No tasks available right now. Please check back later.
        </p>

    <?php } ?>

</main>

<?php include __DIR__ . "/../includes/footer2.php"; ?>

</body>
</html>
