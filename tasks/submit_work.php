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

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: " . BASE_URL . "user/my_work.php");
    exit();
}

$task_id = (int) $_GET["id"];

$t_cols = [];
$tcol_res = @mysqli_query($conn, "SHOW COLUMNS FROM tasks");
if ($tcol_res) {
    while ($tc = mysqli_fetch_assoc($tcol_res)) {
        $t_cols[$tc['Field']] = true;
    }
}
$t_reward = isset($t_cols['reward_wp']) ? 'reward_wp' : (isset($t_cols['reward']) ? 'reward AS reward_wp' : (isset($t_cols['points']) ? 'points AS reward_wp' : '10 AS reward_wp'));
$t_assigned = isset($t_cols['assigned_user_id']) ? 'assigned_user_id' : 'task_id';

$sub_cols = [];
$subcol_res = @mysqli_query($conn, "SHOW COLUMNS FROM submissions");
if ($subcol_res) {
    while ($sc = mysqli_fetch_assoc($subcol_res)) {
        $sub_cols[$sc['Field']] = true;
    }
}
$s_user_col = isset($sub_cols['user_id']) ? 'user_id' : (
    isset($sub_cols['worker_id']) ? 'worker_id' : (
    isset($sub_cols['submitter_id']) ? 'submitter_id' : 'user_id'));
$s_text_col = isset($sub_cols['submission_text']) ? 'submission_text' : (
    isset($sub_cols['text']) ? 'text' : (
    isset($sub_cols['content']) ? 'content' : (
    isset($sub_cols['description']) ? 'description' : 'submission_text')));
$s_task_col = isset($sub_cols['task_id']) ? 'task_id' : 'task_id';

$sql = "SELECT
            task_id,
            title,
            description,
            $t_reward,
            deadline,
            status
        FROM tasks
        WHERE task_id = ?
        AND $t_assigned = ?
        AND status = 'ASSIGNED'";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $task_id,
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$task = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$task) {
    header("Location: " . BASE_URL . "user/my_work.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $submission_text = trim($_POST["submission_text"]);

    if ($submission_text == "") {

        $message = "Please describe the work you completed.";

    } else {

        $sql = "INSERT INTO submissions
                ($s_task_col, $s_user_col, $s_text_col)
                VALUES (?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "iis",
            $task_id,
            $user_id,
            $submission_text
        );

        if (mysqli_stmt_execute($stmt)) {

            $sql = "UPDATE tasks
                    SET status = 'SUBMITTED'
                    WHERE task_id = ?
                    AND assigned_user_id = ?";

            $update_stmt = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param(
                $update_stmt,
                "ii",
                $task_id,
                $user_id
            );

            mysqli_stmt_execute($update_stmt);

            mysqli_stmt_close($update_stmt);

            header("Location: " . BASE_URL . "user/my_work.php");
            exit();

        } else {

            $message = "Failed to submit work.";
        }

        mysqli_stmt_close($stmt);
    }
}

include __DIR__ . "/../includes/header2.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Submit Work - SkillSprout</title>
</head>
<body>

<main>

    <table class="submit-work-layout">

        <tr>
            <td class="submit-work-box">

                <h1>Submit Work</h1>

                <h2>
                    <?php echo htmlspecialchars($task["title"]); ?>
                </h2>

                <p>
                    <strong>Reward:</strong>
                    <?php echo $task["reward_wp"]; ?> WP
                </p>

                <p>
                    <strong>Deadline:</strong>
                    <?php echo htmlspecialchars($task["deadline"]); ?>
                </p>

                <?php if ($message != "") { ?>

                    <p>
                        <?php echo htmlspecialchars($message); ?>
                    </p>

                <?php } ?>

                <form action="" method="post">

                    <p>
                        <label>
                            Describe the work you completed
                        </label>
                    </p>

                    <p>
                        <textarea
                            name="submission_text"
                            rows="10"
                            required
                            placeholder="Describe the work you completed..."
                        ></textarea>
                    </p>

                    <p>
                        <button type="submit">
                            Submit Work
                        </button>
                    </p>

                </form>

            </td>
        </tr>

    </table>

</main>

<?php include __DIR__ . "/../includes/footer2.php"; ?>

</body>
</html>
