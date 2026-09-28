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
$t_reward = isset($t_cols['reward_wp']) ? 'tasks.reward_wp' : (isset($t_cols['reward']) ? 'tasks.reward AS reward_wp' : (isset($t_cols['points']) ? 'tasks.points AS reward_wp' : (isset($t_cols['budget']) ? 'tasks.budget AS reward_wp' : '10 AS reward_wp')));
$t_creator = isset($t_cols['creator_id']) ? 'tasks.creator_id' : (isset($t_cols['client_id']) ? 'tasks.client_id' : (isset($t_cols['user_id']) ? 'tasks.user_id' : 'tasks.creator_id'));

$u_cols = [];
$ucol_res = @mysqli_query($conn, "SHOW COLUMNS FROM users");
if ($ucol_res) {
    while ($uc = mysqli_fetch_assoc($ucol_res)) {
        $u_cols[$uc['Field']] = true;
    }
}
$u_name = isset($u_cols['name']) ? 'users.name AS worker_name' : (isset($u_cols['username']) ? 'users.username AS worker_name' : 'users.email AS worker_name');
$u_bal_col = isset($u_cols['wp_balance']) ? 'wp_balance' : (isset($u_cols['points']) ? 'points' : 'wp_balance');

$sub_cols = [];
$subcol_res = @mysqli_query($conn, "SHOW COLUMNS FROM submissions");
if ($subcol_res) {
    while ($sc = mysqli_fetch_assoc($subcol_res)) {
        $sub_cols[$sc['Field']] = true;
    }
}
$s_id = isset($sub_cols['submission_id']) ? 'submissions.submission_id' : (isset($sub_cols['id']) ? 'submissions.id AS submission_id' : '1 AS submission_id');
$s_text = isset($sub_cols['submission_text']) ? 'submissions.submission_text' : (
    isset($sub_cols['text']) ? 'submissions.text AS submission_text' : (
    isset($sub_cols['content']) ? 'submissions.content AS submission_text' : (
    isset($sub_cols['description']) ? 'submissions.description AS submission_text' : "'' AS submission_text")));
$s_user = isset($sub_cols['user_id']) ? 'submissions.user_id' : (
    isset($sub_cols['worker_id']) ? 'submissions.worker_id' : (
    isset($sub_cols['submitter_id']) ? 'submissions.submitter_id' : 'submissions.user_id'));
$s_task = isset($sub_cols['task_id']) ? 'submissions.task_id' : 'tasks.task_id';
$s_status = isset($sub_cols['status']) ? 'submissions.status' : "'SUBMITTED'";
$s_created = isset($sub_cols['created_at']) ? 'submissions.created_at' : 'tasks.created_at';

$sql = "SELECT
            tasks.task_id,
            tasks.title,
            $t_reward,
            tasks.status,
            $t_creator,
            users.user_id AS worker_id,
            $u_name,
            $s_id,
            $s_text,
            $s_status AS submission_status,
            $s_created AS created_at
        FROM tasks

        INNER JOIN submissions
            ON tasks.task_id = $s_task

        INNER JOIN users
            ON $s_user = users.user_id

        WHERE tasks.task_id = ?
        AND $t_creator = ?
        AND $s_status = 'SUBMITTED'";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $task_id,
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$submission = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$submission) {
    header("Location: " . BASE_URL . "user/my_work.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $action = $_POST["action"] ?? "approve";
    $submission_id = $submission["submission_id"];
    $worker_id = $submission["worker_id"];
    $reward_wp = (int) $submission["reward_wp"];

    if ($action === "approve") {
        mysqli_begin_transaction($conn);

        try {
            $sql = "UPDATE submissions
                    SET status = 'APPROVED'
                    WHERE submission_id = ? AND status = 'SUBMITTED'";

            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $submission_id);
            mysqli_stmt_execute($stmt);

            if (mysqli_stmt_affected_rows($stmt) !== 1) {
                mysqli_stmt_close($stmt);
                mysqli_rollback($conn);
                header("Location: " . BASE_URL . "user/my_work.php");
                exit();
            }
            mysqli_stmt_close($stmt);

            $sql = "UPDATE users
                    SET wp_balance = wp_balance + ?
                    WHERE user_id = ?";

            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $reward_wp, $worker_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $sql = "UPDATE tasks
                    SET status = 'COMPLETED'
                    WHERE task_id = ?";

            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $task_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            mysqli_commit($conn);

            header("Location: " . BASE_URL . "user/my_work.php");
            exit();

        } catch (Exception $e) {
            mysqli_rollback($conn);
            die("Failed to approve work: " . $e->getMessage());
        }
    } elseif ($action === "reject") {
        mysqli_begin_transaction($conn);

        try {
            $sql = "UPDATE submissions
                    SET status = 'REJECTED'
                    WHERE submission_id = ? AND status = 'SUBMITTED'";

            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $submission_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $sql = "UPDATE tasks
                    SET status = 'ASSIGNED'
                    WHERE task_id = ?";

            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $task_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            mysqli_commit($conn);

            header("Location: " . BASE_URL . "user/my_work.php");
            exit();

        } catch (Exception $e) {
            mysqli_rollback($conn);
            die("Failed to reject submission: " . $e->getMessage());
        }
    }
}

include __DIR__ . "/../includes/header2.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Review Submission - SkillSprout</title>
</head>
<body>

<main>

    <table class="review-submission-layout">

        <tr>
            <td class="review-submission-box">

                <h1>Review Submission</h1>

                <h2>
                    <?php echo htmlspecialchars($submission["title"]); ?>
                </h2>

                <p>
                    <strong>Worker:</strong>
                    <?php echo htmlspecialchars($submission["worker_name"]); ?>
                </p>

                <p>
                    <strong>Reward:</strong>
                    <?php echo $submission["reward_wp"]; ?> WP
                </p>

                <p>
                    <strong>Task Status:</strong>
                    <?php echo htmlspecialchars($submission["status"]); ?>
                </p>

                <hr>

                <h3>Submitted Work</h3>

                <p>
                    <?php
                    echo nl2br(
                        htmlspecialchars($submission["submission_text"])
                    );
                    ?>
                </p>

                <hr>

                <p>
                    <strong>Submitted On:</strong>
                    <?php echo htmlspecialchars($submission["created_at"]); ?>
                </p>

                <p>

                    <form action="" method="post">

                        <button type="submit" name="action" value="approve" class="btn btn-primary mr-1">
                            Approve Work &amp; Pay <?php echo (int) $submission["reward_wp"]; ?> WP
                        </button>

                        <button type="submit" name="action" value="reject" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this submission? The task will be returned to the worker to redo.');">
                            Reject Submission
                        </button>

                    </form>

                </p>

            </td>
        </tr>

    </table>

</main>

<?php include __DIR__ . "/../includes/footer2.php"; ?>

</body>
</html>
