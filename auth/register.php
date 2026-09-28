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

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $check = "SELECT user_id FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $check);

    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {

        $message = "Email already registered.";

    } else {

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $col_res = @mysqli_query($conn, "SHOW COLUMNS FROM users");
        $cols = [];
        if ($col_res) {
            while ($c = mysqli_fetch_assoc($col_res)) {
                $cols[$c['Field']] = true;
            }
        }

        if (isset($cols['name']) && isset($cols['username'])) {
            $sql = "INSERT INTO users (name, username, email, password) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssss", $name, $name, $email, $hashedPassword);
        } elseif (isset($cols['name'])) {
            $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sss", $name, $email, $hashedPassword);
        } elseif (isset($cols['username'])) {
            $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sss", $name, $email, $hashedPassword);
        } else {
            $sql = "INSERT INTO users (email, password) VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ss", $email, $hashedPassword);
        }

        if (mysqli_stmt_execute($stmt)) {

            echo "<script> alert('Registration successful! You can now login.'); window.location.href = '" . BASE_URL . "auth/login.php'; </script>";
            exit();

        } else {

            $message = "Registration failed. Please try again.";
        }
    }

    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - SkillSprout</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css?v=2">
</head>
<body>

<?php include __DIR__ . "/../includes/header1.php"; ?>

<main>

    <div class="register-box">

        <h1>Create Account</h1>

        <p class="text-center">
            Register to start using SkillSprout
        </p>

        <form action="" method="post" onsubmit="return validateRegisterForm()">

            <label for="name"> Full Name </label>
            <input type="text" id="name" name="name" placeholder="Enter your full name">

            <label for="email"> Email </label>
            <input type="text" id="email" name="email" placeholder="Enter your email">

            <label for="password"> Password </label>
            <input type="password" id="password" name="password" placeholder="Enter your password">

            <label for="confirm_password"> Confirm Password </label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Enter your password again">

            <button type="submit"> Register </button>

        </form>

        <script src="<?= BASE_URL ?>assets/js/register.js"></script>

        <div class="login-link">
            <p>
                Already have an account?
                <a href="<?= BASE_URL ?>auth/login.php">Login here</a>
            </p>
        </div>

    </div>

</main>

<?php if ($message != "") { ?>
<script>
    alert("<?php echo htmlspecialchars($message, ENT_QUOTES); ?>");
</script>
<?php } ?>

<?php include __DIR__ . "/../includes/footer1.php"; ?>

</body>
</html>
