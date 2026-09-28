<?php
session_start();

include __DIR__ . "/../config/database.php";

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

// Get all open tasks
$sql = "SELECT
            task_id,
            title,
            description,
            domain,
            required_skills,
            reward_wp,
            deadline,
            created_at
        FROM tasks
        WHERE status = 'OPEN'
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
                        <strong>
                            <?php echo htmlspecialchars($task["title"]); ?>
                        </strong>

                        <br>

                        <?php echo htmlspecialchars($task["description"]); ?>
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
                            View Task
                        </a>
                    </td>

                </tr>

            <?php } ?>

        </table>

    <?php } else { ?>

        <p>
            No tasks are currently available.
        </p>

    <?php } ?>

</main>

<?php include __DIR__ . "/../includes/footer2.php"; ?>

</body>
</html>
