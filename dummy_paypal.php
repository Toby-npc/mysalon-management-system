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


$customer_id = (int)$_SESSION["user_id"];
$message = "";


// ==========================================
// AMBIL BOOKING ID
// ==========================================

$booking_id =
    $_GET["id"]
    ??
    $_POST["booking_id"]
    ??
    "";


if (
    empty($booking_id) ||
    !is_numeric($booking_id)
) {
    header("Location: MySalon(MYBOOKING).php");
    exit();
}


$booking_id = (int)$booking_id;


// ==========================================
// AMBIL BOOKING + SERVICE
// ==========================================

$stmt = $conn->prepare(
    "SELECT
        bookings.booking_id,
        bookings.booking_date,
        bookings.booking_time,
        bookings.status,

        services.service_name,
        services.price,
        services.discount_percent,
        services.promotion_status

     FROM bookings

     INNER JOIN services
        ON bookings.service_id = services.service_id

     WHERE bookings.booking_id = ?
     AND bookings.customer_id = ?"
);


$stmt->bind_param(
    "ii",
    $booking_id,
    $customer_id
);


$stmt->execute();

$result = $stmt->get_result();


// ==========================================
// BOOKING TAK WUJUD
// ==========================================

if ($result->num_rows === 0) {

    $stmt->close();
    $conn->close();

    header("Location: MySalon(MYBOOKING).php");
    exit();
}


$booking = $result->fetch_assoc();

$stmt->close();


// ==========================================
// CANCELLED / COMPLETED TAK BOLEH BAYAR
// ==========================================

if (
    $booking["status"] === "cancelled" ||
    $booking["status"] === "completed"
) {

    $conn->close();

    header(
        "Location: MySalon(MYBOOKING).php?payment_error=locked"
    );

    exit();
}


// ==========================================
// CALCULATE FINAL PRICE
// ==========================================

$original_price =
    (float)$booking["price"];


$discount =
    (float)$booking["discount_percent"];


$promotion_active =
    (
        $booking["promotion_status"] === "active"
        &&
        $discount > 0
    );


$final_price =
    $original_price;


if ($promotion_active) {

    $final_price =
        $original_price
        -
        (
            $original_price
            *
            ($discount / 100)
        );
}


$final_price =
    round($final_price, 2);


// ==========================================
// SEMAK PAYMENT SEDIA ADA
// ==========================================

$paymentCheck =
    $conn->prepare(
        "SELECT
            payment_id,
            payment_status

         FROM payments

         WHERE booking_id = ?

         ORDER BY payment_id DESC

         LIMIT 1"
    );


$paymentCheck->bind_param(
    "i",
    $booking_id
);


$paymentCheck->execute();

$paymentResult =
    $paymentCheck->get_result();


if ($paymentResult->num_rows > 0) {

    $existingPayment =
        $paymentResult->fetch_assoc();


    if (
        $existingPayment["payment_status"] === "paid"
    ) {

        $payment_id =
            (int)$existingPayment["payment_id"];

        $paymentCheck->close();
        $conn->close();

        header(
            "Location: receipt.php?id="
            .
            $payment_id
        );

        exit();
    }
}


$paymentCheck->close();


// ==========================================
// PROCESS DUMMY PAYPAL
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    $paypal_email =
        trim(
            $_POST["paypal_email"]
            ??
            ""
        );


    $paypal_password =
        trim(
            $_POST["paypal_password"]
            ??
            ""
        );


    // ======================================
    // VALIDATION
    // ======================================

    if (
        empty($paypal_email) ||
        empty($paypal_password)
    ) {

        $message =
            "Please enter the dummy PayPal email and password.";

    } elseif (
        !filter_var(
            $paypal_email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $message =
            "Please enter a valid email address.";

    } else {


        // ==================================
        // DUMMY PAYMENT ONLY
        // ==================================
        //
        // Email/password ini TIDAK dihantar
        // ke PayPal dan TIDAK disimpan.
        //
        // Tiada transaksi wang sebenar.
        // ==================================


        $payment_method =
            "PayPal (Dummy)";


        $amount =
            $final_price;


        $insert =
            $conn->prepare(
                "INSERT INTO payments
                (
                    booking_id,
                    amount,
                    payment_method,
                    payment_status
                )

                VALUES (?, ?, ?, 'paid')"
            );


        $insert->bind_param(
            "ids",
            $booking_id,
            $amount,
            $payment_method
        );


        if ($insert->execute()) {


            $payment_id =
                $insert->insert_id;


            $insert->close();


            header(
                "Location: receipt.php?id="
                .
                $payment_id
            );

            exit();


        } else {


            $message =
                "Dummy PayPal payment could not be processed.";


            $insert->close();

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
        PayPal Demo - MySalon
    </title>


    <link
        rel="stylesheet"
        href="MySalon(CSS).css"
    >


    <style>

        .paypal-wrapper {
            max-width: 520px;
            margin: 60px auto;
            padding: 0 20px;
        }

        .paypal-card {
            background: #ffffff;
            border: 1px solid #ebdcd0;
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 12px 30px rgba(43, 37, 38, 0.08);
        }

        .paypal-title {
            text-align: center;
            margin-bottom: 8px;
            font-size: 30px;
            font-weight: 700;
            color: #2b2526;
        }

        .paypal-demo {
            text-align: center;
            color: #b0717a;
            font-weight: 700;
            margin-bottom: 25px;
        }

        .paypal-warning {
            background: #faf6f0;
            border: 1px solid #ebdcd0;
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 25px;
            text-align: center;
            color: #635657;
            font-size: 14px;
        }

        .paypal-order {
            background: #faf6f0;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .paypal-order p {
            margin: 8px 0;
        }

        .paypal-amount {
            font-size: 22px;
            font-weight: 700;
            color: #b0717a;
        }

        .paypal-card label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2b2526;
        }

        .paypal-card input {
            width: 100%;
            padding: 13px 14px;
            margin-bottom: 18px;
            border: 1px solid #ebdcd0;
            border-radius: 10px;
            box-sizing: border-box;
            font-size: 15px;
        }

        .paypal-card input:focus {
            outline: none;
            border-color: #b0717a;
        }

        .paypal-pay-button {
            width: 100%;
            border: none;
            cursor: pointer;
            margin-top: 5px;
        }

        .paypal-back {
            text-align: center;
            margin-top: 20px;
        }

        .paypal-error {
            color: #9c4444;
            text-align: center;
            margin-bottom: 20px;
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

    <ul>

        <li>
            <a href="MySalon(HOME).php">
                Home
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
     DUMMY PAYPAL
=========================================== -->

<div class="paypal-wrapper">


    <div class="paypal-card">


        <div class="paypal-title">
            PayPal
        </div>


        <div class="paypal-demo">
            DEMO PAYMENT
        </div>


        <div class="paypal-warning">

            This is a simulated PayPal payment
            for the MySalon project.

            <br>

            No real money will be charged and
            no PayPal credentials are required.

        </div>


        <?php if (!empty($message)): ?>

            <div class="paypal-error">

                <?php

                echo htmlspecialchars(
                    $message
                );

                ?>

            </div>

        <?php endif; ?>


        <!-- ORDER INFORMATION -->

        <div class="paypal-order">


            <p>

                <strong>
                    Service:
                </strong>

                <?php

                echo htmlspecialchars(
                    $booking["service_name"]
                );

                ?>

            </p>


            <p>

                <strong>
                    Appointment:
                </strong>

                <?php

                echo htmlspecialchars(
                    $booking["booking_date"]
                );

                ?>

                at

                <?php

                echo date(
                    "h:i A",
                    strtotime(
                        $booking["booking_time"]
                    )
                );

                ?>

            </p>


            <p>

                <strong>
                    Total:
                </strong>

                <span class="paypal-amount">

                    $<?php

                    echo number_format(
                        $final_price,
                        2
                    );

                    ?>

                </span>

            </p>


        </div>


        <!-- ==================================
             DUMMY PAYPAL FORM
        =================================== -->

        <form
            method="POST"
            action="dummy_paypal.php"
            autocomplete="off"
        >


            <input
                type="hidden"
                name="booking_id"
                value="<?php
                echo $booking_id;
                ?>"
            >


            <label for="paypal_email">
                Dummy PayPal Email
            </label>


            <input
                type="email"
                id="paypal_email"
                name="paypal_email"
                placeholder="demo@paypal.test"
                required
            >


            <label for="paypal_password">
                Dummy PayPal Password
            </label>


            <input
                type="password"
                id="paypal_password"
                name="paypal_password"
                placeholder="Enter any demo password"
                required
            >


            <button
                type="submit"
                class="button confirm-button paypal-pay-button"
            >
                Pay with PayPal (Demo)
            </button>


        </form>


        <div class="paypal-back">

            <a
                href="payment.php?id=<?php
                echo $booking_id;
                ?>"
                class="button"
            >
                Cancel and Go Back
            </a>

        </div>


    </div>


</div>


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
