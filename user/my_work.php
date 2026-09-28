<?php
session_start();

include __DIR__ . "/../config/database.php";

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

/* =========================================
   1. MY CREATED TASKS
   ========================================= */

$sql = "SELECT
            tasks.task_id,
            tasks.title,
            tasks.reward_wp,
            tasks.deadline,
            tasks.status,
            COUNT(applications.application_id) AS applicant_count
        FROM tasks
        LEFT JOIN applications
            ON tasks.task_id = applications.task_id
        WHERE tasks.creator_id = ?
        GROUP BY
            tasks.task_id,
            tasks.title,
            tasks.reward_wp,
            tasks.deadline,
            tasks.status
        ORDER BY tasks.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$created_tasks = mysqli_stmt_get_result($stmt);


/* =========================================
   2. MY APPLICATIONS
   ========================================= */

$sql = "SELECT
            applications.application_id,
            applications.status AS application_status,
            tasks.task_id,
            tasks.title,
            tasks.reward_wp,
            tasks.deadline
        FROM applications
        INNER JOIN tasks
            ON applications.task_id = tasks.task_id
        WHERE applications.user_id = ?
        AND applications.status = 'PENDING'
        ORDER BY applications.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$my_applications = mysqli_stmt_get_result($stmt);


/* =========================================
   3. ACTIVE WORK
   ========================================= */

$sql = "SELECT
            tasks.task_id,
            tasks.title,
            tasks.reward_wp,
            tasks.deadline,
            tasks.status
        FROM tasks
        WHERE tasks.assigned_user_id = ?
        AND tasks.status = 'ASSIGNED'
        ORDER BY tasks.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$active_work = mysqli_stmt_get_result($stmt);

include __DIR__ . "/../includes/header2.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Work - SkillSprout</title>
</head>
<body>

<main>

    <h1>My Work</h1>

    <p>
        Manage your created tasks, applications and active work.
    </p>


    <!-- =========================================
         MY CREATED TASKS
         ========================================= -->

    <h2>My Created Tasks</h2>

    <?php if (mysqli_num_rows($created_tasks) > 0) { ?>

        <table class="my-work-table">

            <tr>
                <th>Task</th>
                <th>Reward</th>
                <th>Deadline</th>
                <th>Status</th>
                <th>Applicants</th>
                <th>Action</th>
            </tr>

            <?php while ($task = mysqli_fetch_assoc($created_tasks)) { ?>

                <tr>

                    <td>
                        <?php echo htmlspecialchars($task["title"]); ?>
                    </td>

                    <td>
                        <?php echo $task["reward_wp"]; ?> WP
                    </td>

                    <td>
                        <?php echo htmlspecialchars($task["deadline"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($task["status"]); ?>
                    </td>

                    <td>
                        <?php echo $task["applicant_count"]; ?>
                    </td>

                    <td>

                        <?php if ($task["status"] == "OPEN") { ?>

                            <a href="<?= BASE_URL ?>tasks/manage_applications.php?id=<?php echo $task["task_id"]; ?>">
                                Manage Applications
                            </a>

                        <?php } elseif ($task["status"] == "SUBMITTED") { ?>
                        
                            <a href="<?= BASE_URL ?>tasks/review_submission.php?id=<?php echo $task["task_id"]; ?>">
                                Review Submission
                            </a>
                        
                        <?php } else { ?>
                        
                            <a href="<?= BASE_URL ?>tasks/task_details.php?id=<?php echo $task["task_id"]; ?>">
                                View Task
                            </a>
                        
                        <?php } ?>

                    </td>

                </tr>

            <?php } ?>

        </table>

    <?php } else { ?>

        <p>
            You have not created any tasks yet.
        </p>

    <?php } ?>


    <!-- =========================================
         MY APPLICATIONS
         ========================================= -->

    <h2>My Applications</h2>

    <?php if (mysqli_num_rows($my_applications) > 0) { ?>

        <table class="my-work-table">

            <tr>
                <th>Task</th>
                <th>Reward</th>
                <th>Deadline</th>
                <th>Status</th>
                <th>Action</th>
            </tr>

            <?php while ($application = mysqli_fetch_assoc($my_applications)) { ?>

                <tr>

                    <td>
                        <?php echo htmlspecialchars($application["title"]); ?>
                    </td>

                    <td>
                        <?php echo $application["reward_wp"]; ?> WP
                    </td>

                    <td>
                        <?php echo htmlspecialchars($application["deadline"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars(
                            $application["application_status"]
                        ); ?>
                    </td>

                    <td>
                        <a href="<?= BASE_URL ?>tasks/task_details.php?id=<?php echo $application["task_id"]; ?>">
                            View Task
                        </a>
                    </td>

                </tr>

            <?php } ?>

        </table>

    <?php } else { ?>

        <p>
            You have no pending applications.
        </p>

    <?php } ?>


    <!-- =========================================
                        ACTIVE WORK
         ========================================= -->

    <h2>Active Work</h2>

    <?php if (mysqli_num_rows($active_work) > 0) { ?>

        <table class="my-work-table">

            <tr>
                <th>Task</th>
                <th>Reward</th>
                <th>Deadline</th>
                <th>Status</th>
                <th>Action</th>
            </tr>

            <?php while ($work = mysqli_fetch_assoc($active_work)) { ?>

                <tr>

                    <td>
                        <?php echo htmlspecialchars($work["title"]); ?>
                    </td>

                    <td>
                        <?php echo $work["reward_wp"]; ?> WP
                    </td>

                    <td>
                        <?php echo htmlspecialchars($work["deadline"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($work["status"]); ?>
                    </td>

                    <td>
                        <a href="<?= BASE_URL ?>tasks/submit_work.php?id=<?php echo $work["task_id"]; ?>">
                            Submit Work
                        </a>
                    </td>

                </tr>

             <?php } ?> 

        </table>

    <?php } else { ?>

        <p>
            You have no active work.
        </p>

    <?php } ?>

</main>

<?php include __DIR__ . "/../includes/footer2.php"; ?>

</body>
</html>
