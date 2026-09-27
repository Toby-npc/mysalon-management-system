<?php

session_start();
include "db.php";


// ==========================================
// CUSTOMER MESTI LOGIN
// ==========================================

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "customer"
) {
    header("Location: login.php");
    exit();
}


$customer_id = $_SESSION["user_id"];


// ==========================================
// AMBIL PAYMENT ID
// ==========================================

$payment_id = $_GET["id"] ?? "";

if (
    empty($payment_id) ||
    !is_numeric($payment_id)
) {
    header("Location: MySalon(MYBOOKING).php");
    exit();
}


// ==========================================
// AMBIL PAYMENT + BOOKING + SERVICE
// ==========================================

$stmt = $conn->prepare(
    "SELECT
        payments.payment_id,
        payments.amount,
        payments.payment_method,
        payments.payment_status,
        payments.payment_date,

        bookings.booking_id,
        bookings.booking_date,
        bookings.booking_time,
        bookings.status AS booking_status,

        services.service_name

     FROM payments

     INNER JOIN bookings
        ON payments.booking_id = bookings.booking_id

     INNER JOIN services
        ON bookings.service_id = services.service_id

     WHERE payments.payment_id = ?
     AND bookings.customer_id = ?"
);


$stmt->bind_param(
    "ii",
    $payment_id,
    $customer_id
);


$stmt->execute();

$result = $stmt->get_result();


// ==========================================
// PAYMENT TAK WUJUD / BUKAN MILIK CUSTOMER
// ==========================================

if ($result->num_rows === 0) {

    $stmt->close();
    $conn->close();

    header("Location: MySalon(MYBOOKING).php");
    exit();
}


$receipt = $result->fetch_assoc();

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
        Receipt - MySalon
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


    <ul>

        <li>
            <a href="MySalon(HOME).php">
                Home
            </a>
        </li>


        <li>
            <a href="MySalon(SERVICES).php">
                Services
            </a>
        </li>


        <li>
            <a href="MySalon(BOOKING).php">
                Booking
            </a>
        </li>


        <li>
            <a href="MySalon(MYBOOKING).php">
                My Booking
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
     RECEIPT
=========================================== -->

<section>


    <div class="section-title">

        <h2>
            Payment Receipt
        </h2>

        <p>
            Your payment has been recorded successfully
        </p>

    </div>



    <div class="booking-form">


        <h3 style="
            text-align:center;
            margin-bottom:30px;
        ">
            MySalon Receipt
        </h3>



        <!-- RECEIPT NUMBER -->

        <p>

            <strong>
                Receipt No:
            </strong>

            #<?php
            echo $receipt["payment_id"];
            ?>

        </p>



        <!-- CUSTOMER -->

        <p>

            <strong>
                Customer:
            </strong>

            <?php

            echo htmlspecialchars(
                $_SESSION["name"]
            );

            ?>

        </p>



        <!-- SERVICE -->

        <p>

            <strong>
                Service:
            </strong>

            <?php

            echo htmlspecialchars(
                $receipt["service_name"]
            );

            ?>

        </p>



        <!-- APPOINTMENT DATE -->

        <p>

            <strong>
                Appointment Date:
            </strong>

            <?php

            echo htmlspecialchars(
                $receipt["booking_date"]
            );

            ?>

        </p>



        <!-- APPOINTMENT TIME -->

        <p>

            <strong>
                Appointment Time:
            </strong>

            <?php

            echo date(
                "h:i A",
                strtotime(
                    $receipt["booking_time"]
                )
            );

            ?>

        </p>



        <!-- PAYMENT METHOD -->

        <p>

            <strong>
                Payment Method:
            </strong>

            <?php

            echo htmlspecialchars(
                $receipt["payment_method"]
            );

            ?>

        </p>



        <!-- PAYMENT DATE -->

        <p>

            <strong>
                Payment Date:
            </strong>

            <?php

            echo date(
                "d M Y, h:i A",
                strtotime(
                    $receipt["payment_date"]
                )
            );

            ?>

        </p>



        <!-- AMOUNT -->

        <p>

            <strong>
                Amount Paid:
            </strong>

            $<?php

            echo number_format(
                $receipt["amount"],
                2
            );

            ?>

        </p>



        <!-- PAYMENT STATUS -->

        <p>

            <strong>
                Payment Status:
            </strong>

            <?php

            echo ucfirst(
                htmlspecialchars(
                    $receipt["payment_status"]
                )
            );

            ?>

        </p>



        <hr style="
            margin:30px 0;
            border:none;
            border-top:1px solid #ebdcd0;
        ">



        <!-- ==================================
             BUTTONS
        =================================== -->

        <div
            class="booking-actions"
            style="justify-content:center;"
        >


            <a
			href="download_receipt.php?id=<?php
			echo $receipt["payment_id"];
			?>"
			class="button"
			>
			Download Receipt
			</a>


            <a
                href="MySalon(MYBOOKING).php"
                class="button"
            >
                Back to My Booking
            </a>


        </div>


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

$stmt->close();
$conn->close();

?>