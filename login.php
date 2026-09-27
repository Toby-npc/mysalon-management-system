<?php
session_start();
include "db.php";

// ==========================================
// ERROR MESSAGE
// ==========================================

$error = "";


// ==========================================
// LOGIN PROCESS
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";


    // Pastikan email dan password diisi
    if (empty($email) || empty($password)) {

        $error = "Please enter your email and password.";

    } else {

        // ==================================
        // CARI USER BERDASARKAN EMAIL
        // ==================================

        $sql = "
            SELECT
                user_id,
                name,
                email,
                password,
                role
            FROM users
            WHERE email = ?
        ";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "s",
            $email
        );

        $stmt->execute();

        $result = $stmt->get_result();


        // ==================================
        // AKAUN DIJUMPAI
        // ==================================

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();


            // ==================================
            // SEMAK PASSWORD
            // ==================================

            if (
                password_verify(
                    $password,
                    $user["password"]
                )
            ) {

                // Simpan maklumat user
                // dalam session

                $_SESSION["user_id"] =
                    $user["user_id"];

                $_SESSION["name"] =
                    $user["name"];

                $_SESSION["role"] =
                    $user["role"];


                // ==================================
                // CUSTOMER
                // ==================================

                if ($user["role"] === "customer") {

                    header(
                        "Location: MySalon(HOME).php"
                    );

                    exit();
                }


                // ==================================
                // STAFF
                // ==================================

                elseif ($user["role"] === "staff") {

                    header(
                        "Location: staff_dashboard.php"
                    );

                    exit();
                }


                // ==================================
                // ADMIN
                // ==================================

                elseif ($user["role"] === "admin") {

                    header(
                        "Location: admin.php"
                    );

                    exit();
                }


                // ==================================
                // ROLE TIDAK DIKENALI
                // ==================================

                else {

                    session_unset();
                    session_destroy();

                    $error =
                        "Invalid account role.";
                }


            } else {

                $error =
                    "Incorrect password.";

            }


        } else {

            $error =
                "Account not found.";

        }


        $stmt->close();
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Login - MySalon
    </title>

    <link
        rel="stylesheet"
        href="MySalon(CSS).css"
    >

</head>


<body>


<!-- ==========================================
     NAVBAR
=========================================== -->

<nav>

    <div class="logo">
        MySalon
    </div>

</nav>



<!-- ==========================================
     LOGIN SECTION
=========================================== -->

<section class="login-section">


    <div class="login-box">


        <h1>

            Welcome to

            <span>
                MySalon
            </span>

        </h1>


        <p>
            Sign in to continue
        </p>



        <!-- ==================================
             ERROR MESSAGE
        =================================== -->

        <?php if ($error !== ""): ?>


            <p style="
                color:#9c4444;
                margin-bottom:20px;
            ">

                <?php
                echo htmlspecialchars(
                    $error
                );
                ?>

            </p>


        <?php endif; ?>



        <!-- ==================================
             LOGIN FORM
        =================================== -->

        <form
            method="POST"
            action="login.php"
            class="role-login-form"
        >


            <!-- EMAIL -->

            <div class="form-group">


                <label for="email">

                    Email

                </label>


                <input

                    type="email"

                    id="email"

                    name="email"

                    placeholder="Enter your email"

                    value="<?php
                    echo htmlspecialchars(
                        $_POST["email"] ?? ""
                    );
                    ?>"

                    required

                >


            </div>



            <!-- PASSWORD -->

            <div class="form-group">


                <label for="password">

                    Password

                </label>


                <input

                    type="password"

                    id="password"

                    name="password"

                    placeholder="Enter your password"

                    required

                >


            </div>



            <!-- SIGN IN -->

            <button
                type="submit"
                class="login-button"
            >

                Sign In

            </button>



            <!-- REGISTER -->

            <p style="margin-top:20px;">

                Don't have an account?

                <a
                    href="register.php"
                    class="login-back"
                >
                    Sign Up
                </a>

            </p>


        </form>



        <!-- GUEST -->

        <a
            href="MySalon(HOME).php"
            class="login-button secondary"
        >

            Continue as Guest

        </a>


    </div>


</section>



<!-- ==========================================
     FOOTER
=========================================== -->

<footer>

    <p>

        &copy; 2026 MySalon Management System.
        All rights reserved.

    </p>

</footer>


</body>

</html>