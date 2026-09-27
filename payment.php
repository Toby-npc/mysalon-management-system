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
// AMBIL BOOKING CUSTOMER
// + PROMOTION INFORMATION
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

$result =
    $stmt->get_result();


// ==========================================
// BOOKING TAK WUJUD / BUKAN MILIK CUSTOMER
// ==========================================

if ($result->num_rows === 0) {

    $stmt->close();
    $conn->close();

    header("Location: MySalon(MYBOOKING).php");
    exit();
}


$booking =
    $result->fetch_assoc();

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
// CALCULATE PROMOTION PRICE
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


// Pastikan 2 decimal

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


$existingPayment = null;


if ($paymentResult->num_rows > 0) {

    $existingPayment =
        $paymentResult->fetch_assoc();

}


$paymentCheck->close();


// ==========================================
// JIKA SUDAH PAID
// TERUS KE RECEIPT
// ==========================================

if (
    $existingPayment &&
    $existingPayment["payment_status"] === "paid"
) {

    $payment_id =
        (int)$existingPayment["payment_id"];


    $conn->close();


    header(
        "Location: receipt.php?id="
        .
        $payment_id
    );

    exit();
}


// ==========================================
// PROCESS PAYMENT
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    $payment_method =
        trim(
            $_POST["payment_method"]
            ??
            ""
        );


    $allowed_methods = [

        "Cash",

        "Online Banking",

        "Credit / Debit Card"

    ];


    // ======================================
    // VALIDATE PAYMENT METHOD
    // ======================================

    if (
        !in_array(
            $payment_method,
            $allowed_methods,
            true
        )
    ) {

        $message =
            "Please select a valid payment method.";

    } else {


        // ==================================
        // SIMULATED PAYMENT
        // ==================================
        //
        // Sistem projek sahaja.
        // Tiada transaksi bank sebenar.
        //
        // Amount datang daripada database
        // dan dikira di server.
        //
        // Customer tidak boleh ubah amount
        // melalui browser.
        // ==================================


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


            // ==================================
            // TERUS KE RECEIPT
            // ==================================

            header(
                "Location: receipt.php?id="
                .
                $payment_id
            );

            exit();


        } else {


            $message =
                "Payment could not be processed.";


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
        Payment - MySalon
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
     PAYMENT SECTION
=========================================== -->

<section>


    <div class="section-title">


        <h2>
            Payment
        </h2>


        <p>
            Review your appointment and complete payment
        </p>


    </div>



    <div class="booking-form">


        <!-- ==================================
             ERROR MESSAGE
        =================================== -->

        <?php if (!empty($message)): ?>


            <p style="
                text-align:center;
                margin-bottom:20px;
                color:#9c4444;
            ">

                <?php

                echo htmlspecialchars(
                    $message
                );

                ?>

            </p>


        <?php endif; ?>



        <!-- ==================================
             SERVICE
        =================================== -->

        <h3 style="margin-bottom:20px;">

            <?php

            echo htmlspecialchars(
                $booking["service_name"]
            );

            ?>

        </h3>



        <!-- ==================================
             DATE
        =================================== -->

        <p>


            <strong>
                Date:
            </strong>


            <?php

            echo htmlspecialchars(
                $booking["booking_date"]
            );

            ?>


        </p>



        <!-- ==================================
             TIME
        =================================== -->

        <p>


            <strong>
                Time:
            </strong>


            <?php

            echo date(
                "h:i A",
                strtotime(
                    $booking["booking_time"]
                )
            );

            ?>


        </p>



        <!-- ==================================
             APPOINTMENT STATUS
        =================================== -->

        <p>


            <strong>
                Appointment Status:
            </strong>


            <?php

            echo ucfirst(
                htmlspecialchars(
                    $booking["status"]
                )
            );

            ?>


        </p>



        <!-- ==================================
             PRICE / PROMOTION
        =================================== -->

        <div style="
            margin-top:25px;
            margin-bottom:30px;
        ">


            <?php if ($promotion_active): ?>


                <!-- ORIGINAL PRICE -->

                <p>


                    <strong>
                        Original Price:
                    </strong>


                    <span style="
                        text-decoration:line-through;
                        opacity:0.65;
                    ">

                        $<?php

                        echo number_format(
                            $original_price,
                            2
                        );

                        ?>

                    </span>


                </p>



                <!-- PROMOTION -->

                <p>


                    <strong>
                        Promotion:
                    </strong>


                    <?php

                    echo number_format(
                        $discount,
                        0
                    );

                    ?>% OFF


                </p>


            <?php endif; ?>



            <!-- FINAL AMOUNT -->

            <p>


                <strong>
                    Amount:
                </strong>


                $<?php

                echo number_format(
                    $final_price,
                    2
                );

                ?>


            </p>


        </div>



        <!-- ==================================
             PAYMENT FORM
        =================================== -->

        <form
            method="POST"
            action="payment.php"
        >


            <input
                type="hidden"
                name="booking_id"
                value="<?php
                echo $booking["booking_id"];
                ?>"
            >



            <!-- ==============================
                 PAYMENT METHOD
            =============================== -->

            <div class="form-group">


                <label for="payment_method">
                    Payment Method
                </label>


                <select
                    id="payment_method"
                    name="payment_method"
                    required
                >


                    <option value="">
                        Choose payment method
                    </option>


                    <option value="Cash">
                        Cash
                    </option>


                    <option value="Online Banking">
                        Online Banking
                    </option>


                    <option value="Credit / Debit Card">
                        Credit / Debit Card
                    </option>


                </select>


            </div>



            <!-- ==============================
                 PAY BUTTON
            =============================== -->

            <button
                type="submit"
                class="button confirm-button"
            >
                Pay Now
            </button>


        </form>



        <!-- ==================================
             BACK BUTTON
        =================================== -->

        <div style="
            margin-top:20px;
            text-align:center;
        ">


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

$conn->close();

?>