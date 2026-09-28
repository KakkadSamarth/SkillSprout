<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . "/../config/database.php";

if (isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "user/dashboard.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $col_res = @mysqli_query($conn, "SHOW COLUMNS FROM users");
    $has_name = false;
    $has_username = false;
    if ($col_res) {
        while ($c = mysqli_fetch_assoc($col_res)) {
            if ($c['Field'] === 'name') $has_name = true;
            if ($c['Field'] === 'username') $has_username = true;
        }
    }
    $name_expr = $has_name ? "name" : ($has_username ? "username AS name" : "email AS name");
    $sql = "SELECT user_id, $name_expr, email, password FROM users WHERE email = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 1) {

        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user["password"])) {

            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["name"] = $user["name"] ?? ($user["email"] ?? "User");
            $_SESSION["email"] = $user["email"];

            echo "<script>
                    window.location.href = '" . BASE_URL . "user/dashboard.php';
                  </script>";

            exit();

        } else {

            $message = "Invalid email or password.";
        }

    } else {

        $message = "Invalid email or password.";
    }

    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - SkillSprout</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css?v=2">
</head>
<body>
    <?php include __DIR__ . "/../includes/header1.php"; ?>

    <main>

        <div class="login-box">

            <h1>Login</h1>

            <p>
                Login to your SkillSprout account
            </p>

            <form action="" method="post" onsubmit="return validateLoginForm()">

                <label for="email"> Email </label>
                <input type="email" id="email" name="email" placeholder="Enter your email">

                <label for="password"> Password </label>
                <input type="password" id="password" name="password" placeholder="Enter your password">

                <button type="submit"> Login </button>
            </form>

            <p>
                Don't have an account?
                <a href="<?= BASE_URL ?>auth/register.php">Register here</a>
            </p>

        </div>

    </main>

    <?php if ($message != "") { ?>
        <script>
            alert("<?php echo htmlspecialchars($message, ENT_QUOTES); ?>");
        </script>
    <?php } ?>

    <?php include __DIR__ . "/../includes/footer1.php"; ?>

    <script src="<?= BASE_URL ?>assets/js/login.js"></script>

</body>
</html>
