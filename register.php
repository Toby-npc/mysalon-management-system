<?php
include "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $message = "Email already registered.";
    } else {

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = "customer";

        $sql = "INSERT INTO users (name, email, password, role)
                VALUES (?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssss",
            $name,
            $email,
            $hashed_password,
            $role
        );

        if ($stmt->execute()) {
            header("Location: login.php?registered=1");
            exit();
        } else {
            $message = "Registration failed.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sign Up - MySalon</title>

    <!-- FONTS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,400;0,500;0,600;1,500&family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600&display=swap"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="MySalon(CSS).css">

    <!-- SIGN UP PAGE POLISH -->
    <style>

        .login-box h1 {
            font-family: 'Fraunces', serif;
            font-size: 38px;
            font-weight: 500;
            line-height: 1.1;
        }

        .login-box h1 span {
            font-style: italic;
            color: var(--copper);
        }

        .login-box p {
            font-family: 'IBM Plex Sans', sans-serif;
            font-size: 16px;
            color: #4a443f;
        }

        .login-box label {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 12px;
            font-weight: 500;
            color: #4a443f;
        }

        .login-box input {
            font-family: 'IBM Plex Sans', sans-serif;
            font-size: 15px;
            padding: 14px 16px;
        }

        .login-box input:focus {
            border-color: var(--copper);
            box-shadow: 0 0 0 3px rgba(191, 122, 74, 0.18);
        }

        /* removes the grey outline around the Sign Up button */
        .login-button {
            border: none;
            cursor: pointer;
            font-weight: 500;
        }

        /* nav brand uses the heading font too */
        nav .logo {
            font-family: 'Fraunces', serif;
        }

    </style>
</head>

<body>

<nav>
    <div class="logo">MySalon</div>
</nav>

<section class="login-section">

    <div class="login-box">

        <h1>Create <span>Account</span></h1>

        <p>Sign up as a MySalon customer.</p>

        <?php if ($message != ""): ?>
            <p style="color:#9c4444;">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>

        <form method="POST" class="role-login-form">

            <div class="form-group">
                <label for="name">Full Name</label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="Enter your full name"
                    required
                >
            </div>

            <div class="form-group">
                <label for="email">Email</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Create a password"
                    minlength="6"
                    required
                >
            </div>

            <button type="submit" class="login-button">
                Sign Up
            </button>

        </form>

        <p style="margin-top:20px;">
            Already have an account?
            <a href="login.php" class="login-back">
                Sign In
            </a>
        </p>

    </div>

</section>

<footer>
    <p>&copy; 2026 MySalon Management System. All rights reserved.</p>
</footer>

</body>
</html>
