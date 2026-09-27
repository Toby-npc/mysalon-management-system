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

    <link rel="stylesheet" href="MySalon(CSS).css">
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
                <label>Full Name</label>

                <input
                    type="text"
                    name="name"
                    placeholder="Enter your full name"
                    required
                >
            </div>

            <div class="form-group">
                <label>Email</label>

                <input
                    type="email"
                    name="email"
                    placeholder="Enter your email"
                    required
                >
            </div>

            <div class="form-group">
                <label>Password</label>

                <input
                    type="password"
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

        <p>
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