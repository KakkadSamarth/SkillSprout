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

$u_cols = [];
$ucol_res = @mysqli_query($conn, "SHOW COLUMNS FROM users");
if ($ucol_res) {
    while ($c = mysqli_fetch_assoc($ucol_res)) {
        $u_cols[$c['Field']] = true;
    }
}
$bal_col = isset($u_cols['wp_balance']) ? 'wp_balance' : (isset($u_cols['points']) ? 'points' : (isset($u_cols['balance']) ? 'balance' : 'wp_balance'));

$sql = "SELECT $bal_col AS wp_balance FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

$current_balance = $user ? (int) $user["wp_balance"] : 0;
$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $domain = trim($_POST["domain"] ?? "");
    $required_skills = trim($_POST["required_skills"] ?? "");
    $reward_wp = isset($_POST["reward_wp"]) ? (int) $_POST["reward_wp"] : 0;
    $deadline = trim($_POST["deadline"] ?? "");

    $today = date("Y-m-d");

    if (
        $title === "" ||
        $description === "" ||
        $domain === "" ||
        $reward_wp <= 0 ||
        $deadline === ""
    ) {
        $message = "Please fill all required fields.";
        $message_type = "error";
    } elseif ($deadline <= $today) {
        $message = "The deadline must be selected after today only.";
        $message_type = "error";
    } elseif ($reward_wp > $current_balance) {
        $message = "Insufficient Work Points. You have " . $current_balance . " WP available, but tried to offer " . $reward_wp . " WP.";
        $message_type = "error";
    } else {
        mysqli_begin_transaction($conn);

        try {
            // 1. Deduct balance from users table (updating any available balance columns)
            $deduct_clauses = [];
            $deduct_types = "";
            $deduct_params = [];

            if (isset($u_cols['wp_balance'])) {
                $deduct_clauses[] = "wp_balance = wp_balance - ?";
                $deduct_types .= "i";
                $deduct_params[] = $reward_wp;
            }
            if (isset($u_cols['points'])) {
                $deduct_clauses[] = "points = points - ?";
                $deduct_types .= "i";
                $deduct_params[] = $reward_wp;
            }
            if (isset($u_cols['balance'])) {
                $deduct_clauses[] = "balance = balance - ?";
                $deduct_types .= "i";
                $deduct_params[] = $reward_wp;
            }
            if (empty($deduct_clauses)) {
                $deduct_clauses[] = "wp_balance = wp_balance - ?";
                $deduct_types .= "i";
                $deduct_params[] = $reward_wp;
            }

            $deduct_sql = "UPDATE users SET " . implode(", ", $deduct_clauses) . " WHERE user_id = ? AND $bal_col >= ?";
            $deduct_types .= "ii";
            $deduct_params[] = $user_id;
            $deduct_params[] = $reward_wp;

            $deduct_stmt = mysqli_prepare($conn, $deduct_sql);
            mysqli_stmt_bind_param($deduct_stmt, $deduct_types, ...$deduct_params);
            mysqli_stmt_execute($deduct_stmt);

            if (mysqli_stmt_affected_rows($deduct_stmt) !== 1) {
                mysqli_stmt_close($deduct_stmt);
                mysqli_rollback($conn);
                $message = "Failed to deduct Work Points. You do not have enough balance.";
                $message_type = "error";
            } else {
                mysqli_stmt_close($deduct_stmt);

                // 2. Insert into tasks with dynamic column mapping for both standard and legacy schemas
                $t_cols = [];
                $tcol_res = @mysqli_query($conn, "SHOW COLUMNS FROM tasks");
                if ($tcol_res) {
                    while ($c = mysqli_fetch_assoc($tcol_res)) {
                        $t_cols[$c['Field']] = true;
                    }
                }

                $insert_cols = [];
                $insert_placeholders = [];
                $insert_types = "";
                $insert_values = [];

                // Title
                $insert_cols[] = "title";
                $insert_placeholders[] = "?";
                $insert_types .= "s";
                $insert_values[] = $title;

                // Description
                $insert_cols[] = "description";
                $insert_placeholders[] = "?";
                $insert_types .= "s";
                $insert_values[] = $description;

                // Creator ID / User ID
                if (isset($t_cols['creator_id'])) {
                    $insert_cols[] = "creator_id";
                    $insert_placeholders[] = "?";
                    $insert_types .= "i";
                    $insert_values[] = $user_id;
                }
                if (isset($t_cols['user_id'])) {
                    $insert_cols[] = "user_id";
                    $insert_placeholders[] = "?";
                    $insert_types .= "i";
                    $insert_values[] = $user_id;
                }
                if (!isset($t_cols['creator_id']) && !isset($t_cols['user_id'])) {
                    $insert_cols[] = "creator_id";
                    $insert_placeholders[] = "?";
                    $insert_types .= "i";
                    $insert_values[] = $user_id;
                }

                // Domain / Category
                if (isset($t_cols['domain'])) {
                    $insert_cols[] = "domain";
                    $insert_placeholders[] = "?";
                    $insert_types .= "s";
                    $insert_values[] = $domain;
                }
                if (isset($t_cols['category'])) {
                    $insert_cols[] = "category";
                    $insert_placeholders[] = "?";
                    $insert_types .= "s";
                    $insert_values[] = $domain;
                }
                if (!isset($t_cols['domain']) && !isset($t_cols['category'])) {
                    $insert_cols[] = "domain";
                    $insert_placeholders[] = "?";
                    $insert_types .= "s";
                    $insert_values[] = $domain;
                }

                // Required Skills / Skills
                if (isset($t_cols['required_skills'])) {
                    $insert_cols[] = "required_skills";
                    $insert_placeholders[] = "?";
                    $insert_types .= "s";
                    $insert_values[] = $required_skills;
                }
                if (isset($t_cols['skills'])) {
                    $insert_cols[] = "skills";
                    $insert_placeholders[] = "?";
                    $insert_types .= "s";
                    $insert_values[] = $required_skills;
                }

                // Reward / Points
                if (isset($t_cols['reward_wp'])) {
                    $insert_cols[] = "reward_wp";
                    $insert_placeholders[] = "?";
                    $insert_types .= "i";
                    $insert_values[] = $reward_wp;
                }
                if (isset($t_cols['reward'])) {
                    $insert_cols[] = "reward";
                    $insert_placeholders[] = "?";
                    $insert_types .= "i";
                    $insert_values[] = $reward_wp;
                }
                if (isset($t_cols['points'])) {
                    $insert_cols[] = "points";
                    $insert_placeholders[] = "?";
                    $insert_types .= "i";
                    $insert_values[] = $reward_wp;
                }
                if (isset($t_cols['work_points'])) {
                    $insert_cols[] = "work_points";
                    $insert_placeholders[] = "?";
                    $insert_types .= "i";
                    $insert_values[] = $reward_wp;
                }
                if (!isset($t_cols['reward_wp']) && !isset($t_cols['reward']) && !isset($t_cols['points']) && !isset($t_cols['work_points'])) {
                    $insert_cols[] = "reward_wp";
                    $insert_placeholders[] = "?";
                    $insert_types .= "i";
                    $insert_values[] = $reward_wp;
                }

                // Deadline
                if (isset($t_cols['deadline']) || empty($t_cols)) {
                    $insert_cols[] = "deadline";
                    $insert_placeholders[] = "?";
                    $insert_types .= "s";
                    $insert_values[] = $deadline;
                }

                // Status
                if (isset($t_cols['status']) || empty($t_cols)) {
                    $insert_cols[] = "status";
                    $insert_placeholders[] = "'OPEN'";
                }

                $task_sql = "INSERT INTO tasks (" . implode(", ", $insert_cols) . ") VALUES (" . implode(", ", $insert_placeholders) . ")";
                $task_stmt = mysqli_prepare($conn, $task_sql);
                if (!empty($insert_types)) {
                    mysqli_stmt_bind_param($task_stmt, $insert_types, ...$insert_values);
                }

                if (mysqli_stmt_execute($task_stmt)) {
                    $new_task_id = mysqli_insert_id($conn);
                    mysqli_stmt_close($task_stmt);

                    mysqli_commit($conn);

                    $current_balance -= $reward_wp;
                    $message = "Task created successfully! " . $reward_wp . " Work Points deducted from your balance.";
                    $message_type = "success";
                } else {
                    $insert_err = mysqli_stmt_error($task_stmt);
                    mysqli_stmt_close($task_stmt);
                    mysqli_rollback($conn);
                    $message = "Failed to create task (" . htmlspecialchars($insert_err) . "). Points were not deducted.";
                    $message_type = "error";
                }
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $err_msg = $e->getMessage();
            error_log("Task creation failed: " . $err_msg);
            $message = "An error occurred while creating task: " . htmlspecialchars($err_msg) . ". Points were not deducted.";
            $message_type = "error";
        }
    }
}

include __DIR__ . "/../includes/header2.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Task - SkillSprout</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

<main>

    <table class="create-task-layout">

        <tr>

            <td class="create-task-box">

                <h1>Create a Task</h1>

                <p>
                    Post a task and offer Work Points to someone who completes it.
                </p>

                <div class="balance-badge">
                    Your Available Balance: <strong><?php echo (int) $current_balance; ?> WP</strong>
                </div>

                <?php if ($message != "") { ?>
                    <div class="alert-box <?php echo $message_type == 'success' ? 'alert-success' : 'alert-error'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                        <?php if ($message_type == 'success') { ?>
                            <br><a href="<?= BASE_URL ?>tasks/tasks.php" class="inline-block mt-1">View in Available Tasks &rarr;</a>
                        <?php } ?>
                    </div>
                <?php } ?>

                <?php if ($current_balance <= 0) { ?>
                    <div class="alert-warning">
                        <strong>You have 0 Work Points.</strong><br>
                        You need Work Points in your balance to post tasks.
                        <a href="<?= BASE_URL ?>tasks/tasks.php">
                            Browse tasks
                        </a> to apply, complete work, and earn Work Points!
                    </div>
                <?php } ?>

                <form action="" method="post" onsubmit="return validateTaskForm()">

                    <table class="create-task-form">

                        <tr>
                            <td>
                                <label for="title">Task Title</label>
                            </td>

                            <td>
                                <input
                                    type="text"
                                    id="title"
                                    name="title"
                                    placeholder="Brief task title"
                                    required
                                    <?php if ($current_balance <= 0) echo 'disabled'; ?>
                                >
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <label for="description">Description</label>
                            </td>

                            <td>
                                <textarea
                                    id="description"
                                    name="description"
                                    rows="6"
                                    placeholder="Explain the requirements, instructions, and expectations clearly..."
                                    required
                                    <?php if ($current_balance <= 0) echo 'disabled'; ?>
                                ></textarea>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <label for="domain">Domain</label>
                            </td>

                            <td>

                                <select id="domain" name="domain" required <?php if ($current_balance <= 0) echo 'disabled'; ?>>

                                    <option value="">
                                        Select Domain
                                    </option>

                                    <option value="Web Development">
                                        Web Development
                                    </option>

                                    <option value="Graphic Design">
                                        Graphic Design
                                    </option>

                                    <option value="Content Writing">
                                        Content Writing
                                    </option>

                                    <option value="Data Entry">
                                        Data Entry
                                    </option>

                                    <option value="Programming">
                                        Programming
                                    </option>

                                    <option value="Other Skills">
                                        Other Skills
                                    </option>

                                </select>

                            </td>
                        </tr>

                        <tr>
                            <td>
                                <label for="required_skills">Required Skills</label>
                            </td>

                            <td>
                                <input
                                    type="text"
                                    id="required_skills"
                                    name="required_skills"
                                    placeholder="Example: HTML, CSS, JavaScript"
                                    <?php if ($current_balance <= 0) echo 'disabled'; ?>
                                >
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <label for="reward_wp">Reward (Work Points)</label>
                            </td>

                            <td>
                                <input
                                    type="number"
                                    id="reward_wp"
                                    name="reward_wp"
                                    min="1"
                                    max="<?php echo (int) $current_balance; ?>"
                                    placeholder="Amount in WP (Max: <?php echo (int) $current_balance; ?>)"
                                    required
                                    <?php if ($current_balance <= 0) echo 'disabled'; ?>
                                >
                                <small class="help-text">
                                    Points will be deducted from your wallet when the task is posted and paid to the worker upon your approval.
                                </small>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <label for="deadline">Deadline</label>
                            </td>

                            <td>
                                <input
                                    type="date"
                                    id="deadline"
                                    name="deadline"
                                    min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                    required
                                    <?php if ($current_balance <= 0) echo 'disabled'; ?>
                                >
                            </td>
                        </tr>

                        <tr>
                            <td></td>

                            <td>
                                <button type="submit" <?php if ($current_balance <= 0) echo 'disabled'; ?>>
                                    Create Task & Deduct Reward
                                </button>
                            </td>
                        </tr>

                    </table>

                </form>

                <script>
                    (function() {
                        var deadlineInput = document.getElementById("deadline");
                        if (deadlineInput) {
                            var tomorrow = new Date();
                            tomorrow.setDate(tomorrow.getDate() + 1);
                            var yyyy = tomorrow.getFullYear();
                            var mm = String(tomorrow.getMonth() + 1).padStart(2, '0');
                            var dd = String(tomorrow.getDate()).padStart(2, '0');
                            deadlineInput.min = yyyy + '-' + mm + '-' + dd;
                        }
                    })();

                    function validateTaskForm() {
                        var rewardInput = document.getElementById("reward_wp");
                        if (!rewardInput) return true;
                        var reward = parseInt(rewardInput.value, 10);
                        var maxBalance = <?php echo (int) $current_balance; ?>;

                        if (isNaN(reward) || reward <= 0) {
                            alert("Please enter a valid reward amount (at least 1 WP).");
                            rewardInput.focus();
                            return false;
                        }

                        if (reward > maxBalance) {
                            alert("You do not have enough Work Points! You have " + maxBalance + " WP, but entered " + reward + " WP.");
                            rewardInput.focus();
                            return false;
                        }

                        return confirm("Are you sure you want to post this task? " + reward + " WP will be deducted from your balance.");
                    }
                </script>

            </td>

        </tr>

    </table>

</main>

<?php include __DIR__ . "/../includes/footer2.php"; ?>

</body>
</html>
