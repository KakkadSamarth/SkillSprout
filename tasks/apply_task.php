<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . "/../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: " . BASE_URL . "tasks/tasks.php");
    exit();
}

$task_id = (int) $_GET["id"];
$user_id = $_SESSION["user_id"];

$message = "";

$sql = "SELECT task_id, creator_id, title, status
        FROM tasks
        WHERE task_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $task_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$task = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$task) {
    header("Location: " . BASE_URL . "tasks/tasks.php");
    exit();
}

if ($task["creator_id"] == $user_id) {
    header("Location: " . BASE_URL . "tasks/task_details.php?id=" . $task_id);
    exit();
}

if ($task["status"] != "OPEN") {
    header("Location: " . BASE_URL . "tasks/task_details.php?id=" . $task_id);
    exit();
}

$sql = "SELECT application_id
        FROM applications
        WHERE task_id = ?
        AND user_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "ii", $task_id, $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$already_applied = mysqli_num_rows($result) > 0;

mysqli_stmt_close($stmt);

if ($_SERVER["REQUEST_METHOD"] == "POST" && !$already_applied) {

    $application_message = trim($_POST["message"]);

    $sql = "INSERT INTO applications
            (task_id, user_id, message)
            VALUES (?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "iis",
        $task_id,
        $user_id,
        $application_message
    );

    if (mysqli_stmt_execute($stmt)) {
    
        header("Location: " . BASE_URL . "tasks/task_details.php?id=" . $task_id);
        exit();
    
    } else {
    
        $message = "Failed to submit application: " . mysqli_error($conn);
    }

    mysqli_stmt_close($stmt);
}

include __DIR__ . "/../includes/header2.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Apply for Task - SkillSprout</title>
</head>
<body>

<main>

    <table class="apply-task-layout">

        <tr>

            <td class="apply-task-box">

                <h1>Apply for Task</h1>

                <h2>
                    <?php echo htmlspecialchars($task["title"]); ?>
                </h2>

                <?php if ($already_applied) { ?>

                    <p>
                        You have already applied for this task.
                    </p>

                    <p>
                        <a href="<?= BASE_URL ?>tasks/task_details.php?id=<?php echo $task_id; ?>">
                            Back to Task
                        </a>
                    </p>

                <?php } else { ?>

                    <p>
                        Send a short message to the task creator.
                    </p>

                    <?php if ($message != "") { ?>

                        <p>
                            <?php echo htmlspecialchars($message); ?>
                        </p>

                    <?php } ?>

                    <form action="" method="post">

                        <p>

                            <label>
                                Application Message
                            </label>

                            <br>

                            <textarea
                                name="message"
                                rows="6"
                                required
                                placeholder="Tell the task creator why you are suitable for this task."
                            ></textarea>

                        </p>

                        <p>

                            <button type="submit">
                                Submit Application
                            </button>

                        </p>

                    </form>

                <?php } ?>

            </td>

        </tr>

    </table>

</main>

<?php include __DIR__ . "/../includes/footer2.php"; ?>

</body>
</html>
