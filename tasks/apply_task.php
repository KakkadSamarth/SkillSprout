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

$task_status = strtoupper(trim($task["status"] ?? 'OPEN'));
if (empty($task_status)) {
    $task_status = 'OPEN';
}

if ($task_status !== "OPEN") {
    header("Location: " . BASE_URL . "tasks/task_details.php?id=" . $task_id);
    exit();
}

$sql = "SELECT $a_id_col, $a_msg_col
        FROM applications
        WHERE $a_task_col = ?
        AND $a_user_col = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $task_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$already_applied = false;
$existing_message = "";
if ($result && ($row = mysqli_fetch_assoc($result))) {
    $already_applied = true;
    $existing_message = $row['message'] ?? ($row['proposal'] ?? '');
}
mysqli_stmt_close($stmt);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $application_message = trim($_POST["message"] ?? "");

    if ($already_applied) {
        $sql = "UPDATE applications SET $a_msg_col = ? WHERE $a_task_col = ? AND $a_user_col = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sii", $application_message, $task_id, $user_id);
            if (mysqli_stmt_execute($stmt)) {
                header("Location: " . BASE_URL . "tasks/task_details.php?id=" . $task_id . "&msg=applied");
                exit();
            } else {
                $message = "Failed to update application: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        } else {
            $message = "Database prepare error: " . mysqli_error($conn);
        }
    } else {
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
                header("Location: " . BASE_URL . "tasks/task_details.php?id=" . $task_id . "&msg=applied");
                exit();
            } else {
                $message = "Failed to submit application: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        } else {
            $message = "Failed to prepare application submission: " . mysqli_error($conn);
        }
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

                <?php if ((int)$task["creator_id"] === (int)$user_id) { ?>
                    <div class="alert-box alert-info mb-2" style="background: #e7f3fe; border-left: 4px solid #2196F3; padding: 12px; margin-bottom: 15px;">
                        <strong>Task Creator Note:</strong> You posted this task. Submitting an application here will allow you to test candidate acceptance and deliverables submission in testing mode.
                    </div>
                <?php } ?>

                <?php if ($already_applied) { ?>
                    <div class="alert-box alert-success mb-2" style="background: #e8f5e9; border-left: 4px solid #4CAF50; padding: 12px; margin-bottom: 15px;">
                        <strong>Application Already Active:</strong> You have submitted an application for this task. You can modify your proposal note below or click back to view details.
                    </div>
                <?php } else { ?>
                    <p>
                        Send a short pitch or proposal to the task creator explaining your skills and approach.
                    </p>
                <?php } ?>

                <?php if ($message != "") { ?>
                    <div class="alert-box alert-danger mb-2" style="background: #ffebee; border-left: 4px solid #f44336; padding: 12px; margin-bottom: 15px;">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php } ?>

                <form action="<?= BASE_URL ?>tasks/apply_task.php?id=<?= $task_id ?>" method="post">

                    <p>
                        <label for="message">
                            <strong>Your Application Message / Pitch:</strong>
                        </label>
                        <br>
                        <textarea
                            name="message"
                            id="message"
                            rows="6"
                            required
                            style="width: 100%; box-sizing: border-box; padding: 12px; border: 1px solid #ccc; border-radius: 4px; font-family: inherit; font-size: 15px; margin-top: 6px;"
                            placeholder="Tell the task creator why you are suitable for this task..."
                        ><?php echo htmlspecialchars($existing_message); ?></textarea>
                    </p>

                    <p style="margin-top: 20px;">
                        <button type="submit" class="btn btn-primary" style="padding: 12px 30px; font-size: 16px; font-weight: bold; background: #000; color: #fff; border: none; cursor: pointer; border-radius: 4px; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);">
                            <?php echo $already_applied ? 'Update Application &rarr;' : 'Submit Application &rarr;'; ?>
                        </button>
                        <a href="<?= BASE_URL ?>tasks/task_details.php?id=<?php echo $task_id; ?>" class="btn btn-secondary" style="margin-left: 12px; padding: 12px 20px; display: inline-block; text-decoration: none; color: #333; border: 1px solid #ccc; border-radius: 4px;">
                            Cancel &amp; Return to Task
                        </a>
                    </p>

                </form>

            </td>

        </tr>

    </table>

</main>

<?php include __DIR__ . "/../includes/footer2.php"; ?>

</body>
</html>
