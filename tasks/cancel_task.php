<?php
session_start();

include __DIR__ . "/../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["task_id"])) {
    $task_id = (int) $_POST["task_id"];

    $sql = "SELECT task_id, creator_id, reward_wp, status, title FROM tasks WHERE task_id = ? AND creator_id = ?";
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
            $update_sql = "UPDATE tasks SET status = 'CANCELLED' WHERE task_id = ? AND creator_id = ? AND status = 'OPEN'";
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

            $refund_sql = "UPDATE users SET wp_balance = wp_balance + ? WHERE user_id = ?";
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
