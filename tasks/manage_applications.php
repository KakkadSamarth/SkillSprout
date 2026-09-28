<?php
session_start();

include __DIR__ . "/../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: " . BASE_URL . "user/dashboard.php");
    exit();
}

$task_id = (int) $_GET["id"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $application_id = (int) $_POST["application_id"];
    $action = $_POST["action"];

    $sql = "SELECT user_id
            FROM applications
            WHERE application_id = ?
            AND task_id = ?
            AND status = 'PENDING'";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $application_id,
        $task_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $application = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if (!$application) {
        header("Location: " . BASE_URL . "tasks/manage_applications.php?id=" . $task_id);
        exit();
    }

    if ($action == "accept") {

        mysqli_begin_transaction($conn);

        try {

            $sql = "UPDATE applications
                    SET status = 'ACCEPTED'
                    WHERE application_id = ?
                    AND task_id = ?";

            $stmt = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param(
                $stmt,
                "ii",
                $application_id,
                $task_id
            );

            mysqli_stmt_execute($stmt);

            mysqli_stmt_close($stmt);

            $sql = "UPDATE applications
                    SET status = 'REJECTED'
                    WHERE task_id = ?
                    AND application_id != ?
                    AND status = 'PENDING'";

            $stmt = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param(
                $stmt,
                "ii",
                $task_id,
                $application_id
            );

            mysqli_stmt_execute($stmt);

            mysqli_stmt_close($stmt);

            $assigned_user_id = $application["user_id"];

            $sql = "UPDATE tasks
                    SET status = 'ASSIGNED',
                        assigned_user_id = ?
                    WHERE task_id = ?";

            $stmt = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param(
                $stmt,
                "ii",
                $assigned_user_id,
                $task_id
            );

            mysqli_stmt_execute($stmt);

            mysqli_stmt_close($stmt);

            mysqli_commit($conn);

        } catch (Exception $e) {

            mysqli_rollback($conn);

            echo "An error occurred: " . $e->getMessage();
            exit();
        }

        header("Location: " . BASE_URL . "tasks/manage_applications.php?id=" . $task_id);
        exit();
    }

    if ($action == "reject") {

        $sql = "UPDATE applications
                SET status = 'REJECTED'
                WHERE application_id = ?
                AND task_id = ?
                AND status = 'PENDING'";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $application_id,
            $task_id
        );

        mysqli_stmt_execute($stmt);

        mysqli_stmt_close($stmt);

        header("Location: " . BASE_URL . "tasks/manage_applications.php?id=" . $task_id);
        exit();
    }
}

$sql = "SELECT
            task_id,
            title,
            status
        FROM tasks
        WHERE task_id = ?
        AND creator_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "ii", $task_id, $user_id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$task = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$task) {
    header("Location: " . BASE_URL . "user/dashboard.php");
    exit();
}

$sql = "SELECT
            applications.application_id,
            applications.message,
            applications.status AS application_status,
            applications.created_at,
            users.user_id,
            users.name,
            users.email
        FROM applications
        INNER JOIN users
            ON applications.user_id = users.user_id
        WHERE applications.task_id = ?
        ORDER BY applications.created_at ASC";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $task_id);

mysqli_stmt_execute($stmt);

$applications = mysqli_stmt_get_result($stmt);

include __DIR__ . "/../includes/header2.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Applications - SkillSprout</title>
</head>
<body>

<main>

    <h1>
        Manage Applications
    </h1>

    <h2>
        <?php echo htmlspecialchars($task["title"]); ?>
    </h2>

    <p>
        Task Status:
        <?php echo htmlspecialchars($task["status"]); ?>
    </p>

    <?php if (mysqli_num_rows($applications) > 0) { ?>

        <table class="applications-table">

            <tr>
                <th>Applicant</th>
                <th>Email</th>
                <th>Message</th>
                <th>Status</th>
                <th>Action</th>
            </tr>

            <?php while ($application = mysqli_fetch_assoc($applications)) { ?>

                <tr>

                    <td>
                        <?php echo htmlspecialchars($application["name"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($application["email"]); ?>
                    </td>

                    <td>
                        <?php echo nl2br(
                            htmlspecialchars($application["message"])
                        ); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars(
                            $application["application_status"]
                        ); ?>
                    </td>

                    <td>
                        <?php if ($application["application_status"] == "PENDING") { ?>

                            <form action="" method="post">

                                <input type="hidden" name="application_id" value="<?php echo $application["application_id"]; ?>">
                                <button type="submit" name="action" value="accept">
                                    Accept
                                </button>
                                <button type="submit" name="action" value="reject">
                                    Reject
                                </button>
                                
                            </form>

                        <?php } else { ?>
                            No action
                        <?php } ?>

                    </td>

                </tr>

            <?php } ?>

        </table>

    <?php } else { ?>

        <p>
            No one has applied for this task yet.
        </p>

    <?php } ?>

</main>

<?php include __DIR__ . "/../includes/footer2.php"; ?>

</body>
</html>
