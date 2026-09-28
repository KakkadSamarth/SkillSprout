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

    $sql = "SELECT user_id, name, email, password FROM users WHERE email = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 1) {

        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user["password"])) {

            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["name"] = $user["name"];
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
    <link rel="stylesheet" href="/assets/css/style.css">
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
                <a href="/auth/register.php">Register here</a>
            </p>

        </div>

    </main>

    <?php if ($message != "") { ?>
        <script>
            alert("<?php echo htmlspecialchars($message, ENT_QUOTES); ?>");
        </script>
    <?php } ?>

    <?php include __DIR__ . "/../includes/footer1.php"; ?>

    <script src="/assets/js/login.js"></script>

</body>
</html>
