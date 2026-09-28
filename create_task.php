<?php

session_start();

include "../config/database.php";

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

$message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $domain = trim($_POST["domain"]);
    $required_skills = trim($_POST["required_skills"]);
    $reward_wp = (int) $_POST["reward_wp"];
    $deadline = $_POST["deadline"];

    $today = date("Y-m-d");

    // Basic validation
    if (
        $title == "" ||
        $description == "" ||
        $domain == "" ||
        $reward_wp <= 0 ||
        $deadline == ""
    ) {

        $message = "Please fill all required fields.";

    } elseif ($deadline <= $today) {

        $message = "The deadline must be selected after today only.";

    } else {

        // Check user's current WP balance
        $sql = "SELECT wp_balance FROM users WHERE user_id = ?";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        // Check if user has enough WP
        if ($reward_wp > $user["wp_balance"]) {

            $message = "You do not have enough Work Points.";

        } else {

            // Insert task
            $sql = "INSERT INTO tasks
                    (creator_id, title, description, domain,
                     required_skills, reward_wp, deadline)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param(
                $stmt,
                "issssis",
                $user_id,
                $title,
                $description,
                $domain,
                $required_skills,
                $reward_wp,
                $deadline
            );

            if (mysqli_stmt_execute($stmt)) {

                $message = "Task created successfully!";

            } else {

                $message = "Failed to create task.";
            }

            mysqli_stmt_close($stmt);
        }
    }
}

include "header2.php";

?>


<main>

    <table class="create-task-layout">

        <tr>

            <td class="create-task-box">

                <h1>Create a Task</h1>

                <p>
                    Post a task and offer Work Points to someone
                    who completes it.
                </p>

                <?php if ($message != "") { ?>

                    <p>
                        <?php echo htmlspecialchars($message); ?>
                    </p>

                <?php } ?>

                <form action="" method="post">

                    <table class="create-task-form">

                        <tr>
                            <td>
                                <label>Task Title</label>
                            </td>

                            <td>
                                <input
                                    type="text"
                                    name="title"
                                    required
                                >
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <label>Description</label>
                            </td>

                            <td>
                                <textarea
                                    name="description"
                                    rows="6"
                                    required
                                ></textarea>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <label>Domain</label>
                            </td>

                            <td>

                                <select name="domain" required>

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
                                <label>Required Skills</label>
                            </td>

                            <td>
                                <input
                                    type="text"
                                    name="required_skills"
                                    placeholder="Example: HTML, CSS, JavaScript"
                                >
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <label>Reward (Work Points)</label>
                            </td>

                            <td>
                                <input
                                    type="number"
                                    name="reward_wp"
                                    min="1"
                                    required
                                >
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <label>Deadline</label>
                            </td>

                            <td>
                                <input
                                    type="date"
                                    id="deadline"
                                    name="deadline"
                                    min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                    required
                                >
                            </td>
                        </tr>

                        <tr>
                            <td></td>

                            <td>
                                <button type="submit">
                                    Create Task
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
                </script>

            </td>

        </tr>

    </table>

</main>

<?php

include "footer2.php";

?>