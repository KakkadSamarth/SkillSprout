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

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["task_id"])) {
    $task_id = (int) $_POST["task_id"];

    $t_cols = [];
    $tcol_res = @mysqli_query($conn, "SHOW COLUMNS FROM tasks");
    if ($tcol_res) {
        while ($tc = mysqli_fetch_assoc($tcol_res)) {
            $t_cols[$tc['Field']] = true;
        }
    }
    $t_creator = isset($t_cols['creator_id']) ? 'creator_id' : (isset($t_cols['client_id']) ? 'client_id' : (isset($t_cols['user_id']) ? 'user_id' : 'creator_id'));
    $t_reward = isset($t_cols['reward_wp']) ? 'reward_wp' : (isset($t_cols['reward']) ? 'reward AS reward_wp' : (isset($t_cols['points']) ? 'points AS reward_wp' : (isset($t_cols['budget']) ? 'budget AS reward_wp' : '10 AS reward_wp')));

    $u_cols = [];
    $ucol_res = @mysqli_query($conn, "SHOW COLUMNS FROM users");
    if ($ucol_res) {
        while ($uc = mysqli_fetch_assoc($ucol_res)) {
            $u_cols[$uc['Field']] = true;
        }
    }
    $u_bal = isset($u_cols['wp_balance']) ? 'wp_balance' : (isset($u_cols['points']) ? 'points' : 'wp_balance');

    $sql = "SELECT task_id, $t_creator AS creator_id, $t_reward, status, title FROM tasks WHERE task_id = ? AND $t_creator = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $task_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $task = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($task && $task["status"] === "OPEN") {
        $refund_wp = (int) $task["reward_wp"];

        mysqli_begin_transaction($conn);

        try {
            $update_sql = "UPDATE tasks SET status = 'CANCELLED' WHERE task_id = ? AND $t_creator = ? AND status = 'OPEN'";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "ii", $task_id, $user_id);
            mysqli_stmt_execute($update_stmt);

            if (mysqli_stmt_affected_rows($update_stmt) !== 1) {
                mysqli_stmt_close($update_stmt);
                mysqli_rollback($conn);
                header("Location: " . BASE_URL . "user/my_work.php?err=already_processed");
                exit();
            }
            mysqli_stmt_close($update_stmt);

            $app_sql = "UPDATE applications SET status = 'REJECTED' WHERE task_id = ? AND status = 'PENDING'";
            $app_stmt = mysqli_prepare($conn, $app_sql);
            mysqli_stmt_bind_param($app_stmt, "i", $task_id);
            mysqli_stmt_execute($app_stmt);
            mysqli_stmt_close($app_stmt);

            $refund_sql = "UPDATE users SET $u_bal = $u_bal + ? WHERE user_id = ?";
            $refund_stmt = mysqli_prepare($conn, $refund_sql);
            mysqli_stmt_bind_param($refund_stmt, "ii", $refund_wp, $user_id);
            mysqli_stmt_execute($refund_stmt);
            mysqli_stmt_close($refund_stmt);

            mysqli_commit($conn);

            header("Location: " . BASE_URL . "user/my_work.php?cancelled=1&refund=" . $refund_wp);
            exit();

        } catch (Exception $e) {
            mysqli_rollback($conn);
            header("Location: " . BASE_URL . "user/my_work.php?err=cancel_failed");
            exit();
        }
    }
}

header("Location: " . BASE_URL . "user/my_work.php");
exit();
