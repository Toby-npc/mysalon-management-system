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

    <!-- FONTS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,400;0,500;0,600;1,500&family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="MySalon(CSS).css"
    >

    <!-- LOGIN PAGE POLISH -->
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

        /* removes the grey outline around the Sign In button */
        .login-button {
            border: none;
            cursor: pointer;
            font-weight: 500;
        }

        .login-button.secondary {
            border: 1px solid var(--copper);
        }

        /* nav brand uses the heading font too */
        nav .logo {
            font-family: 'Fraunces', serif;
        }

    </style>

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
