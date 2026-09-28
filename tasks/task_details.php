<?php
session_start();

include __DIR__ . "/../config/database.php";

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

// Check if task ID exists
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: " . BASE_URL . "tasks/tasks.php");
    exit();
}

$task_id = (int) $_GET["id"];

// Get task details
$sql = "SELECT tasks.task_id, tasks.creator_id, tasks.title, tasks.description, tasks.domain, tasks.required_skills, tasks.reward_wp, 
          tasks.deadline, tasks.status, tasks.created_at, users.name 
          AS creator_name FROM tasks 
          INNER JOIN users ON tasks.creator_id = users.user_id  
          WHERE tasks.task_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $task_id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$task = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

// Task does not exist
if (!$task) {
    header("Location: " . BASE_URL . "tasks/tasks.php");
    exit();
}

include __DIR__ . "/../includes/header2.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($task["title"]); ?> - SkillSprout</title>
</head>
<body>

<main>

    <table class="task-details-layout">

        <tr>

            <td class="task-details-box">

                <h1>
                    <?php echo htmlspecialchars($task["title"]); ?>
                </h1>

                <p>
                    <strong>Domain:</strong>
                    <?php echo htmlspecialchars($task["domain"]); ?>
                </p>

                <p>
                    <strong>Reward:</strong>
                    <?php echo $task["reward_wp"]; ?> WP
                </p>

                <p>
                    <strong>Deadline:</strong>
                    <?php echo htmlspecialchars($task["deadline"]); ?>
                </p>

                <p>
                    <strong>Posted By:</strong>
                    <?php echo htmlspecialchars($task["creator_name"]); ?>
                </p>

                <hr>

                <h2>Description</h2>

                <p>
                    <?php echo nl2br(htmlspecialchars($task["description"])); ?>
                </p>

                <h2>Required Skills</h2>

                <p>
                    <?php
                    if ($task["required_skills"] != "") {
                        echo htmlspecialchars($task["required_skills"]);
                    } else {
                        echo "No specific skills mentioned.";
                    }
                    ?>
                </p>

                <h2>Task Status</h2>

                <p>
                    <?php echo htmlspecialchars($task["status"]); ?>
                </p>

<?php

if ($task["creator_id"] == $_SESSION["user_id"]) {

    if ($task["status"] == "OPEN") {
        ?>

        <p>
            <a href="<?= BASE_URL ?>tasks/manage_applications.php?id=<?php echo $task["task_id"]; ?>">
                Manage Applications
            </a>
        </p>

        <?php
    } else {
        ?>

        <p>
            This task is no longer accepting applications.
        </p>

        <?php
    }

} else {

    if ($task["status"] == "OPEN") {
        ?>

        <p>
            <a href="<?= BASE_URL ?>tasks/apply_task.php?id=<?php echo $task["task_id"]; ?>">
                Apply for Task
            </a>
        </p>

        <?php
    } elseif ($task["status"] == "ASSIGNED") {
        ?>

        <p>
            This task has already been assigned to a worker.
        </p>

        <?php
    } elseif ($task["status"] == "SUBMITTED") {
        ?>

        <p>
            This task is waiting for the creator to review the work.
        </p>

        <?php
    } elseif ($task["status"] == "COMPLETED") {
        ?>

        <p>
            This task has been completed.
        </p>

        <?php
    }

}

?>

            </td>

        </tr>

    </table>

</main>

<?php include __DIR__ . "/../includes/footer2.php"; ?>

</body>
</html>
