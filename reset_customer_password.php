<?php

session_start();
include "db.php";


// ==========================================
// ADMIN SAHAJA
// ==========================================

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: login.php");
    exit();
}


// ==========================================
// AMBIL CUSTOMER ID
// ==========================================

$customer_id = $_GET["id"] ?? "";

if (
    empty($customer_id) ||
    !is_numeric($customer_id)
) {
    header("Location: admin_customers.php");
    exit();
}

$customer_id = (int)$customer_id;


// ==========================================
// SEMAK CUSTOMER
// ==========================================

$stmt = $conn->prepare(
    "SELECT
        user_id,
        name,
        email
     FROM users
     WHERE user_id = ?
     AND role = 'customer'"
);

$stmt->bind_param(
    "i",
    $customer_id
);

$stmt->execute();

$result = $stmt->get_result();


// ==========================================
// CUSTOMER TAK WUJUD
// ==========================================

if ($result->num_rows === 0) {

    $stmt->close();
    $conn->close();

    header("Location: admin_customers.php");
    exit();
}


$customer = $result->fetch_assoc();

$stmt->close();


// ==========================================
// VARIABLES
// ==========================================

$error = "";


// ==========================================
// RESET PASSWORD
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $new_password =
        $_POST["new_password"] ?? "";

    $confirm_password =
        $_POST["confirm_password"] ?? "";


    // ======================================
    // VALIDATION
    // ======================================

    if (
        empty($new_password) ||
        empty($confirm_password)
    ) {

        $error =
            "Please enter and confirm the new password.";

    } elseif (
        strlen($new_password) < 6
    ) {

        $error =
            "Password must be at least 6 characters.";

    } elseif (
        $new_password !== $confirm_password
    ) {

        $error =
            "Password confirmation does not match.";

    } else {


        // ==================================
        // HASH PASSWORD
        // ==================================

        $hashed_password =
            password_hash(
                $new_password,
                PASSWORD_DEFAULT
            );


        // ==================================
        // UPDATE PASSWORD
        // ==================================

        $update = $conn->prepare(
            "UPDATE users
             SET password = ?
             WHERE user_id = ?
             AND role = 'customer'"
        );

        $update->bind_param(
            "si",
            $hashed_password,
            $customer_id
        );


        if ($update->execute()) {

            $update->close();
            $conn->close();

            header(
                "Location: admin_customers.php?password_reset=1"
            );

            exit();

        } else {

            $error =
                "Unable to reset customer password.";

        }


        $update->close();
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
        Reset Customer Password - MySalon
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
        MySalon Admin
    </div>


    <ul>

        <li>
            <a href="admin.php">
                Appointments
            </a>
        </li>

        <li>
            <a href="admin_staff.php">
                Staff
            </a>
        </li>

        <li>
            <a href="admin_services.php">
                Services
            </a>
        </li>

        <li>
            <a href="admin_inventory.php">
                Inventory
            </a>
        </li>

        <li>
            <a href="admin_payments.php">
                Payments
            </a>
        </li>

        <li>

            <a
                href="admin_customers.php"
                class="active"
            >
                Customers
            </a>

        </li>

        <li>
            <a href="admin_schedule.php">
                Schedule
            </a>
        </li>

        <li>
            <a href="admin_records.php">
                Records
            </a>
        </li>

        <li>

            <span>

                Hi,

                <?php

                echo htmlspecialchars(
                    $_SESSION["name"]
                );

                ?>

            </span>

        </li>

        <li>

            <a href="logout.php">
                Logout
            </a>

        </li>

    </ul>

</nav>



<!-- ==========================================
     RESET PASSWORD
=========================================== -->

<section>


    <div class="section-title">

        <h2>
            Reset Customer Password
        </h2>

        <p>
            Set a new password for this customer account
        </p>

    </div>



    <div
        class="booking-card"
        style="
            max-width:600px;
            margin:0 auto;
        "
    >


        <!-- CUSTOMER INFORMATION -->

        <h3>

            <?php

            echo htmlspecialchars(
                $customer["name"]
            );

            ?>

        </h3>


        <p>

            <strong>
                Customer ID:
            </strong>

            #<?php
            echo $customer["user_id"];
            ?>

        </p>


        <p>

            <strong>
                Email:
            </strong>

            <?php

            echo htmlspecialchars(
                $customer["email"]
            );

            ?>

        </p>



        <!-- ERROR MESSAGE -->

        <?php if (!empty($error)): ?>

            <p
                style="
                    color:#b00020;
                    margin-top:20px;
                    margin-bottom:20px;
                "
            >

                <?php

                echo htmlspecialchars(
                    $error
                );

                ?>

            </p>

        <?php endif; ?>



        <!-- RESET PASSWORD FORM -->

        <form
            method="POST"
            action=""
            style="margin-top:25px;"
        >


            <label for="new_password">

                <strong>
                    New Password
                </strong>

            </label>


            <input
                type="password"
                id="new_password"
                name="new_password"
                minlength="6"
                required
            >


            <br><br>


            <label for="confirm_password">

                <strong>
                    Confirm New Password
                </strong>

            </label>


            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                minlength="6"
                required
            >


            <br><br>


            <button
                type="submit"
                class="button"
            >
                Reset Password
            </button>


            <a
                href="admin_customers.php"
                class="button"
            >
                Cancel
            </a>


        </form>


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


<?php

$conn->close();

?>