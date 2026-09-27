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
// AMBIL BOOKING CUSTOMER
// HANYA YANG BELUM DISEMBUNYIKAN
// ==========================================

$sql = "
    SELECT
        bookings.booking_id,
        bookings.booking_date,
        bookings.booking_time,
        bookings.status,

        services.service_name,
        services.price,
        services.discount_percent,
        services.promotion_status,

        (
            SELECT payments.payment_id
            FROM payments
            WHERE payments.booking_id = bookings.booking_id
            AND payments.payment_status = 'paid'
            ORDER BY payments.payment_id DESC
            LIMIT 1
        ) AS payment_id,

        (
            SELECT payments.payment_status
            FROM payments
            WHERE payments.booking_id = bookings.booking_id
            ORDER BY payments.payment_id DESC
            LIMIT 1
        ) AS payment_status,

        (
            SELECT payments.amount
            FROM payments
            WHERE payments.booking_id = bookings.booking_id
            AND payments.payment_status = 'paid'
            ORDER BY payments.payment_id DESC
            LIMIT 1
        ) AS paid_amount

    FROM bookings

    INNER JOIN services
        ON bookings.service_id = services.service_id

    WHERE bookings.customer_id = ?
    AND bookings.customer_hidden = 0

    ORDER BY
        bookings.booking_date ASC,
        bookings.booking_time ASC
";


$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $customer_id
);

$stmt->execute();

$result = $stmt->get_result();

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
        My Bookings - MySalon
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


    <!-- LEFT BRAND -->

    <div class="mysalon-brand">


        <!-- CLICKABLE LOGO -->

        <div
            class="mysalon-logo-trigger"
            id="mysalonLogoTrigger"
            role="button"
            tabindex="0"
            aria-label="Open menu"
            aria-expanded="false"
        >

            <img
                src="asset/image/images.jpg"
                alt="MySalon Logo"
            >

        </div>


        <!-- BRAND NAME -->

        <div class="logo">
            MySalon
        </div>


        <!-- DROPDOWN -->

        <div
            class="mysalon-dropdown"
            id="mysalonDropdown"
        >

            <a href="MySalon(HOME).php">
                Home
            </a>

            <a href="MySalon(SERVICES).php">
                Services
            </a>

            <a href="MySalon(BOOKING).php">
                Booking
            </a>

            <a href="MySalon(MYBOOKING).php">
                My Booking
            </a>


            <div class="mysalon-dropdown-divider"></div>


            <div class="mysalon-dropdown-user">

                Hi,

                <?php
                echo htmlspecialchars(
                    $_SESSION["name"]
                );
                ?>

            </div>


            <a
                href="logout.php"
                class="mysalon-dropdown-logout"
            >
                Logout
            </a>

        </div>

    </div>



    <!-- RIGHT NAVIGATION -->

    <ul>


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
     MY BOOKING SECTION
=========================================== -->

<section>


    <div class="section-title">

        <h2>
            My Bookings
        </h2>

        <p>
            Review or adjust your upcoming styling confirmations
        </p>

    </div>



    <div class="booking-details">


        <!-- ==================================
             BOOKING SUCCESS
        =================================== -->

        <?php if (isset($_GET["success"])): ?>

            <p style="
                text-align:center;
                margin-bottom:25px;
                width:100%;
            ">
                Appointment booked successfully!
            </p>

        <?php endif; ?>



        <!-- ==================================
             EDIT SUCCESS
        =================================== -->

        <?php if (isset($_GET["updated"])): ?>

            <p style="
                text-align:center;
                margin-bottom:25px;
                width:100%;
            ">
                Appointment updated successfully!
            </p>

        <?php endif; ?>



        <!-- ==================================
             PAYMENT SUCCESS
        =================================== -->

        <?php if (isset($_GET["paid"])): ?>

            <p style="
                text-align:center;
                margin-bottom:25px;
                width:100%;
            ">
                Payment completed successfully!
            </p>

        <?php endif; ?>



        <!-- ==================================
             CANCEL SUCCESS
        =================================== -->

        <?php if (isset($_GET["cancelled"])): ?>

            <p style="
                text-align:center;
                margin-bottom:25px;
                width:100%;
            ">
                Appointment cancelled successfully.
            </p>

        <?php endif; ?>



        <!-- ==================================
             DELETE HISTORY SUCCESS
        =================================== -->

        <?php if (
            isset($_GET["history_deleted"])
        ): ?>

            <p style="
                text-align:center;
                margin-bottom:25px;
                width:100%;
            ">
                Booking removed from your history.
            </p>

        <?php endif; ?>



        <!-- ==================================
             EDIT LOCKED
        =================================== -->

        <?php if (
            isset($_GET["edit_error"]) &&
            $_GET["edit_error"] === "locked"
        ): ?>

            <p style="
                text-align:center;
                margin-bottom:25px;
                width:100%;
            ">
                Cancelled or completed appointments
                cannot be edited.
            </p>

        <?php endif; ?>



        <!-- ==================================
             PAYMENT LOCKED
        =================================== -->

        <?php if (
            isset($_GET["payment_error"]) &&
            $_GET["payment_error"] === "locked"
        ): ?>

            <p style="
                text-align:center;
                margin-bottom:25px;
                width:100%;
                color:#9c4444;
            ">
                Cancelled or completed appointments
                cannot be paid.
            </p>

        <?php endif; ?>



        <!-- ==================================
             ADA BOOKING
        =================================== -->

        <?php if (
            $result->num_rows > 0
        ): ?>


            <?php while (
                $booking =
                $result->fetch_assoc()
            ): ?>


                <?php

                // ==================================
                // CALCULATE CURRENT PROMOTION PRICE
                // ==================================

                $original_price =
                    (float)$booking["price"];


                $discount =
                    (float)$booking[
                        "discount_percent"
                    ];


                $promotion_active =
                    (
                        $booking[
                            "promotion_status"
                        ] === "active"
                        &&
                        $discount > 0
                    );


                $display_price =
                    $original_price;


                if ($promotion_active) {

                    $display_price =
                        $original_price
                        -
                        (
                            $original_price
                            *
                            ($discount / 100)
                        );

                }


                $display_price =
                    round(
                        $display_price,
                        2
                    );


                // ==================================
                // JIKA SUDAH BAYAR
                // GUNA AMOUNT SEBENAR PAYMENT
                // ==================================

                if (
                    $booking["payment_status"] === "paid" &&
                    $booking["paid_amount"] !== null
                ) {

                    $display_price =
                        (float)$booking["paid_amount"];

                }

                ?>


                <div class="booking-card">


                    <!-- SERVICE NAME -->

                    <h3>

                        <?php
                        echo htmlspecialchars(
                            $booking["service_name"]
                        );
                        ?>

                    </h3>



                    <!-- DATE -->

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



                    <!-- TIME -->

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
                         PRICE
                    =================================== -->

                    <?php if (
                        $promotion_active &&
                        $booking["payment_status"] !== "paid"
                    ): ?>


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



                    <p>

                        <strong>

                            <?php if (
                                $booking["payment_status"] === "paid"
                            ): ?>

                                Amount Paid:

                            <?php else: ?>

                                Price:

                            <?php endif; ?>

                        </strong>


                        $<?php
                        echo number_format(
                            $display_price,
                            2
                        );
                        ?>

                    </p>



                    <!-- BOOKING STATUS -->

                    <p>

                        <strong>
                            Status:
                        </strong>

                        <?php
                        echo ucfirst(
                            htmlspecialchars(
                                $booking["status"]
                            )
                        );
                        ?>

                    </p>



                    <!-- PAYMENT STATUS -->

                    <p>

                        <strong>
                            Payment:
                        </strong>

                        <?php

                        if (
                            $booking["payment_status"] === "paid"
                        ) {

                            echo "Paid";

                        } elseif (
                            $booking["payment_status"] === "pending"
                        ) {

                            echo "Pending";

                        } elseif (
                            $booking["payment_status"] === "failed"
                        ) {

                            echo "Failed";

                        } else {

                            echo "Not Paid";

                        }

                        ?>

                    </p>



                    <!-- ==================================
                         ACTION BUTTONS
                    =================================== -->

                    <div class="booking-actions">


                        <!-- =================================
                             PENDING / CONFIRMED
                        ================================== -->

                        <?php if (
                            $booking["status"] === "pending" ||
                            $booking["status"] === "confirmed"
                        ): ?>


                            <!-- PAYMENT -->

                            <?php if (
                                $booking["payment_status"] !== "paid"
                            ): ?>


                                <a
                                    href="payment.php?id=<?php
                                    echo $booking["booking_id"];
                                    ?>"
                                    class="button"
                                >
                                    Pay Now
                                </a>


                            <?php elseif (
                                !empty(
                                    $booking["payment_id"]
                                )
                            ): ?>


                                <a
                                    href="receipt.php?id=<?php
                                    echo $booking["payment_id"];
                                    ?>"
                                    class="button"
                                >
                                    View Receipt
                                </a>


                            <?php endif; ?>



                            <!-- EDIT APPOINTMENT -->

                            <a
                                href="edit_booking.php?id=<?php
                                echo $booking["booking_id"];
                                ?>"
                                class="button"
                            >
                                Edit Appointment
                            </a>



                            <!-- CANCEL APPOINTMENT -->

                            <form
                                method="POST"
                                action="delete_booking.php"
                                style="display:inline;"
                                onsubmit="return confirm(
                                    'Are you sure you want to cancel this appointment?'
                                );"
                            >

                                <input
                                    type="hidden"
                                    name="booking_id"
                                    value="<?php
                                    echo $booking["booking_id"];
                                    ?>"
                                >

                                <button
                                    type="submit"
                                    class="button delete-button"
                                >
                                    Cancel Appointment
                                </button>

                            </form>



                        <!-- =================================
                             CANCELLED / COMPLETED
                        ================================== -->

                        <?php elseif (
                            $booking["status"] === "cancelled" ||
                            $booking["status"] === "completed"
                        ): ?>


                            <!-- RECEIPT JIKA SUDAH BAYAR -->

                            <?php if (
                                $booking["payment_status"] === "paid" &&
                                !empty(
                                    $booking["payment_id"]
                                )
                            ): ?>


                                <a
                                    href="receipt.php?id=<?php
                                    echo $booking["payment_id"];
                                    ?>"
                                    class="button"
                                >
                                    View Receipt
                                </a>


                            <?php endif; ?>



                            <!-- DELETE HISTORY -->

                            <form
                                method="POST"
                                action="delete_history.php"
                                style="display:inline;"
                                onsubmit="return confirm(
                                    'Remove this booking from your history?'
                                );"
                            >

                                <input
                                    type="hidden"
                                    name="booking_id"
                                    value="<?php
                                    echo $booking["booking_id"];
                                    ?>"
                                >

                                <button
                                    type="submit"
                                    class="button delete-button"
                                >
                                    Delete History
                                </button>

                            </form>


                        <?php endif; ?>


                    </div>


                </div>


            <?php endwhile; ?>



        <!-- ==================================
             TIADA BOOKING
        =================================== -->

        <?php else: ?>


            <p style="
                text-align:center;
                width:100%;
            ">

                You don't have any bookings yet.

            </p>


        <?php endif; ?>


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



<!-- ==========================================
     LOGO DROPDOWN JAVASCRIPT
=========================================== -->

<script>


const logoTrigger =
    document.getElementById(
        "mysalonLogoTrigger"
    );


const dropdown =
    document.getElementById(
        "mysalonDropdown"
    );



function toggleDropdown() {

    dropdown.classList.toggle(
        "mysalon-dropdown-open"
    );


    const isOpen =
        dropdown.classList.contains(
            "mysalon-dropdown-open"
        );


    logoTrigger.setAttribute(
        "aria-expanded",
        isOpen ? "true" : "false"
    );

}



function closeDropdown() {

    dropdown.classList.remove(
        "mysalon-dropdown-open"
    );


    logoTrigger.setAttribute(
        "aria-expanded",
        "false"
    );

}



logoTrigger.addEventListener(
    "click",
    function(event) {

        event.stopPropagation();

        toggleDropdown();

    }
);



logoTrigger.addEventListener(
    "keydown",
    function(event) {

        if (
            event.key === "Enter" ||
            event.key === " "
        ) {

            event.preventDefault();

            event.stopPropagation();

            toggleDropdown();

        }

    }
);



dropdown.addEventListener(
    "click",
    function(event) {

        event.stopPropagation();

    }
);



document.addEventListener(
    "click",
    function() {

        closeDropdown();

    }
);



document.addEventListener(
    "keydown",
    function(event) {

        if (event.key === "Escape") {

            closeDropdown();

        }

    }
);


</script>


</body>

</html>


<?php

$stmt->close();
$conn->close();

?>