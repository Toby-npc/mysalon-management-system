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
// AMBIL CUSTOMER
// ==========================================

$stmt = $conn->prepare(
    "SELECT
        user_id,
        name,
        email,
        created_at
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

$name = $customer["name"];
$email = $customer["email"];


// ==========================================
// UPDATE CUSTOMER
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim(
        $_POST["name"] ?? ""
    );

    $email = trim(
        $_POST["email"] ?? ""
    );


    // ======================================
    // VALIDATION
    // ======================================

    if (
        empty($name) ||
        empty($email)
    ) {

        $error =
            "Name and email are required.";

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            "Please enter a valid email address.";

    } else {


        // ==================================
        // CHECK EMAIL DUPLICATE
        // ==================================

        $check = $conn->prepare(
            "SELECT user_id
             FROM users
             WHERE email = ?
             AND user_id != ?"
        );

        $check->bind_param(
            "si",
            $email,
            $customer_id
        );

        $check->execute();

        $checkResult =
            $check->get_result();


        if ($checkResult->num_rows > 0) {

            $error =
                "This email is already used by another account.";

            $check->close();

        } else {

            $check->close();


            // ==============================
            // UPDATE
            // ==============================

            $update = $conn->prepare(
                "UPDATE users
                 SET
                    name = ?,
                    email = ?
                 WHERE user_id = ?
                 AND role = 'customer'"
            );

            $update->bind_param(
                "ssi",
                $name,
                $email,
                $customer_id
            );


            if ($update->execute()) {

                $update->close();
                $conn->close();

                header(
                    "Location: admin_customers.php?updated=1"
                );

                exit();

            } else {

                $error =
                    "Unable to update customer.";

            }

            $update->close();
        }
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
        Edit Customer - MySalon
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
     EDIT CUSTOMER
=========================================== -->

<section>

    <div class="section-title">

        <h2>
            Edit Customer
        </h2>

        <p>
            Update customer account information
        </p>

    </div>


    <div
        class="booking-card"
        style="
            max-width:600px;
            margin:0 auto;
        "
    >


        <?php if (!empty($error)): ?>

            <p
                style="
                    color:#b00020;
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


        <p>

            <strong>
                Customer ID:
            </strong>

            #<?php
            echo $customer_id;
            ?>

        </p>


        <form
            method="POST"
            action=""
        >


            <label for="name">

                <strong>
                    Customer Name
                </strong>

            </label>

            <input
                type="text"
                id="name"
                name="name"
                value="<?php
                echo htmlspecialchars(
                    $name
                );
                ?>"
                required
            >


            <br><br>


            <label for="email">

                <strong>
                    Email
                </strong>

            </label>

            <input
                type="email"
                id="email"
                name="email"
                value="<?php
                echo htmlspecialchars(
                    $email
                );
                ?>"
                required
            >


            <br><br>


            <button
                type="submit"
                class="button"
            >
                Save Changes
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
