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

$sql = "SELECT wp_balance FROM users WHERE user_id = ?";
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
            $deduct_sql = "UPDATE users 
                           SET wp_balance = wp_balance - ? 
                           WHERE user_id = ? AND wp_balance >= ?";
            $deduct_stmt = mysqli_prepare($conn, $deduct_sql);
            mysqli_stmt_bind_param($deduct_stmt, "iii", $reward_wp, $user_id, $reward_wp);
            mysqli_stmt_execute($deduct_stmt);

            if (mysqli_stmt_affected_rows($deduct_stmt) !== 1) {
                mysqli_stmt_close($deduct_stmt);
                mysqli_rollback($conn);
                $message = "Failed to deduct Work Points. You do not have enough balance.";
                $message_type = "error";
            } else {
                mysqli_stmt_close($deduct_stmt);

                $task_sql = "INSERT INTO tasks
                             (creator_id, title, description, domain,
                              required_skills, reward_wp, deadline, status)
                             VALUES (?, ?, ?, ?, ?, ?, ?, 'OPEN')";

                $task_stmt = mysqli_prepare($conn, $task_sql);
                mysqli_stmt_bind_param(
                    $task_stmt,
                    "issssis",
                    $user_id,
                    $title,
                    $description,
                    $domain,
                    $required_skills,
                    $reward_wp,
                    $deadline
                );

                if (mysqli_stmt_execute($task_stmt)) {
                    $new_task_id = mysqli_insert_id($conn);
                    mysqli_stmt_close($task_stmt);

                    mysqli_commit($conn);

                    $current_balance -= $reward_wp;
                    $message = "Task created successfully! " . $reward_wp . " Work Points deducted from your balance.";
                    $message_type = "success";
                } else {
                    mysqli_stmt_close($task_stmt);
                    mysqli_rollback($conn);
                    $message = "Failed to create task. Points were not deducted.";
                    $message_type = "error";
                }
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $message = "An error occurred while creating task. Points were not deducted.";
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
