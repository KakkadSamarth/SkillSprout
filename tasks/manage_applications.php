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
    header("Location: " . BASE_URL . "user/dashboard.php");
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
$t_creator = isset($t_cols['creator_id']) ? 'creator_id' : (isset($t_cols['client_id']) ? 'client_id' : (isset($t_cols['user_id']) ? 'user_id' : 'creator_id'));
$t_assigned = isset($t_cols['assigned_user_id']) ? 'assigned_user_id' : (isset($t_cols['worker_id']) ? 'worker_id' : (isset($t_cols['freelancer_id']) ? 'freelancer_id' : 'assigned_user_id'));

$app_cols = [];
$appcol_res = @mysqli_query($conn, "SHOW COLUMNS FROM applications");
if ($appcol_res) {
    while ($ac = mysqli_fetch_assoc($appcol_res)) {
        $app_cols[$ac['Field']] = true;
    }
}
$a_id = isset($app_cols['application_id']) ? 'application_id' : (isset($app_cols['id']) ? 'id' : 'application_id');
$a_id_select = isset($app_cols['application_id']) ? 'applications.application_id' : (isset($app_cols['id']) ? 'applications.id AS application_id' : '1 AS application_id');
$a_msg = isset($app_cols['message']) ? 'applications.message' : (isset($app_cols['proposal']) ? 'applications.proposal AS message' : "'' AS message");
$a_status = isset($app_cols['status']) ? 'status' : 'status';
$a_created = isset($app_cols['created_at']) ? 'applications.created_at' : (isset($app_cols['applied_at']) ? 'applications.applied_at' : 'CURRENT_TIMESTAMP');
$a_user = isset($app_cols['user_id']) ? 'user_id' : (
    isset($app_cols['applicant_id']) ? 'applicant_id' : (
    isset($app_cols['worker_id']) ? 'worker_id' : 'user_id'));
$a_task = isset($app_cols['task_id']) ? 'task_id' : 'task_id';

$u_cols = [];
$ucol_res = @mysqli_query($conn, "SHOW COLUMNS FROM users");
if ($ucol_res) {
    while ($uc = mysqli_fetch_assoc($ucol_res)) {
        $u_cols[$uc['Field']] = true;
    }
}
$u_name = isset($u_cols['name']) ? 'users.name' : (isset($u_cols['username']) ? 'users.username AS name' : 'users.email AS name');

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $application_id = (int) $_POST["application_id"];
    $action = $_POST["action"];

    $sql = "SELECT $a_user AS user_id
            FROM applications
            WHERE $a_id = ?
            AND $a_task = ?
            AND $a_status = 'PENDING'";

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
                    SET $a_status = 'ACCEPTED'
                    WHERE $a_id = ?
                    AND $a_task = ?";

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
                    SET $a_status = 'REJECTED'
                    WHERE $a_task = ?
                    AND $a_id != ?
                    AND $a_status = 'PENDING'";

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
                        $t_assigned = ?
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
                SET $a_status = 'REJECTED'
                WHERE $a_id = ?
                AND $a_task = ?
                AND $a_status = 'PENDING'";

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
        AND $t_creator = ?";

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
            $a_id_select,
            $a_msg,
            applications.$a_status AS application_status,
            $a_created AS created_at,
            users.user_id,
            $u_name,
            users.email
        FROM applications
        INNER JOIN users
            ON applications.$a_user = users.user_id
        WHERE applications.$a_task = ?
        ORDER BY $a_created ASC";

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
