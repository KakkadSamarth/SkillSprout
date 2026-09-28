<?php
session_start();

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

        $sql = "INSERT INTO users (name, email, password)
                VALUES (?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "sss",
            $name,
            $email,
            $hashedPassword
        );

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
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }

        main {
            padding: 40px;
        }

        .register-box {
            width: 400px;
            margin: auto;
            border: 1px solid #ccc;
            padding: 25px;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .register-box:hover {
            box-shadow: 0 6px 16px rgba(0,0,0,0.08);
        }

        .register-box h1 {
            text-align: center;
        }

        label {
            display: block;
            margin-top: 15px;
            margin-bottom: 5px;
        }

        input {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            transition: border-color 0.25s ease, box-shadow 0.25s ease;
        }

        input:focus {
            border-color: #333;
            box-shadow: 0 0 5px rgba(0,0,0,0.15);
            outline: none;
        }

        button {
            width: 100%;
            padding: 10px;
            margin-top: 20px;
            background-color: black;
            color: white;
            border: none;
            cursor: pointer;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.25);
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
        }

        .login-link a {
            color: black;
            transition: color 0.2s ease;
        }

        .login-link a:hover {
            color: #555;
        }
    </style>
</head>
<body>

<?php include __DIR__ . "/../includes/header1.php"; ?>

<main>

    <div class="register-box">

        <h1>Create Account</h1>

        <p style="text-align: center;">
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
