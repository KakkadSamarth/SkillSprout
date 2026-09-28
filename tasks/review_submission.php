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

$sql = "SELECT
            tasks.task_id,
            tasks.title,
            tasks.reward_wp,
            tasks.status,
            tasks.creator_id,
            users.user_id AS worker_id,
            users.name AS worker_name,
            submissions.submission_id,
            submissions.submission_text,
            submissions.status AS submission_status,
            submissions.created_at
        FROM tasks

        INNER JOIN submissions
            ON tasks.task_id = submissions.task_id

        INNER JOIN users
            ON submissions.user_id = users.user_id

        WHERE tasks.task_id = ?
        AND tasks.creator_id = ?
        AND submissions.status = 'SUBMITTED'";

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
