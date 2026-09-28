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

$t_cols = [];
$tcol_res = @mysqli_query($conn, "SHOW COLUMNS FROM tasks");
if ($tcol_res) {
    while ($tc = mysqli_fetch_assoc($tcol_res)) {
        $t_cols[$tc['Field']] = true;
    }
}
$t_creator_col = isset($t_cols['creator_id']) ? 'creator_id' : (isset($t_cols['client_id']) ? 'client_id' : (isset($t_cols['user_id']) ? 'user_id' : 'creator_id'));

$app_cols = [];
$appcol_res = @mysqli_query($conn, "SHOW COLUMNS FROM applications");
if ($appcol_res) {
    while ($ac = mysqli_fetch_assoc($appcol_res)) {
        $app_cols[$ac['Field']] = true;
    }
}
$a_id_col = isset($app_cols['application_id']) ? 'application_id' : (isset($app_cols['id']) ? 'id AS application_id' : '1 AS application_id');
$a_user_col = isset($app_cols['user_id']) ? 'user_id' : (
    isset($app_cols['applicant_id']) ? 'applicant_id' : (
    isset($app_cols['worker_id']) ? 'worker_id' : 'user_id'));
$a_task_col = isset($app_cols['task_id']) ? 'task_id' : 'task_id';
$a_msg_col = isset($app_cols['message']) ? 'message' : (isset($app_cols['proposal']) ? 'proposal' : 'message');

$sql = "SELECT task_id, $t_creator_col AS creator_id, title, status
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
    header("Location: " . BASE_URL . "tasks/task_details.php?id=" . $task_id . "&err=own_task");
    exit();
}

if ($task["status"] != "OPEN") {
    header("Location: " . BASE_URL . "tasks/task_details.php?id=" . $task_id);
    exit();
}

$sql = "SELECT $a_id_col
        FROM applications
        WHERE $a_task_col = ?
        AND $a_user_col = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "ii", $task_id, $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$already_applied = mysqli_num_rows($result) > 0;

mysqli_stmt_close($stmt);

if ($_SERVER["REQUEST_METHOD"] == "POST" && !$already_applied) {

    $application_message = trim($_POST["message"]);

    $app_meta_res = @mysqli_query($conn, "SHOW COLUMNS FROM applications");
    $app_meta = [];
    if ($app_meta_res) {
        while ($c = mysqli_fetch_assoc($app_meta_res)) {
            $app_meta[$c['Field']] = $c;
        }
    }

    $ins_cols = [$a_task_col, $a_user_col];
    $ins_placeholders = ["?", "?"];
    $types = "ii";
    $params = [$task_id, $user_id];

    if ($a_msg_col && isset($app_meta[$a_msg_col])) {
        $ins_cols[] = $a_msg_col;
        $ins_placeholders[] = "?";
        $types .= "s";
        $params[] = $application_message;
    }

    if (isset($app_meta['status']) && !in_array('status', $ins_cols, true)) {
        $ins_cols[] = 'status';
        $ins_placeholders[] = "'PENDING'";
    }

    foreach ($app_meta as $field => $meta) {
        if (in_array($field, $ins_cols, true)) continue;
        if (stripos($meta['Extra'] ?? '', 'auto_increment') !== false) continue;
        if (($meta['Null'] ?? '') === 'NO' && ($meta['Default'] === null)) {
            $ins_cols[] = "`$field`";
            $type = strtolower($meta['Type'] ?? '');
            if (preg_match('/int|decimal|float/i', $type)) {
                $ins_placeholders[] = "1";
            } elseif (preg_match('/date|time/i', $type)) {
                $ins_placeholders[] = "'" . date('Y-m-d H:i:s') . "'";
            } else {
                $ins_placeholders[] = "''";
            }
        }
    }

    $sql = "INSERT INTO applications (" . implode(', ', $ins_cols) . ") VALUES (" . implode(', ', $ins_placeholders) . ")";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);

        if (mysqli_stmt_execute($stmt)) {
            header("Location: " . BASE_URL . "tasks/task_details.php?id=" . $task_id);
            exit();
        } else {
            $message = "Failed to submit application: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    } else {
        $message = "Failed to prepare application submission: " . mysqli_error($conn);
    }
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
