<?php

session_start();
include "db.php";


// ==========================================
// STAFF SAHAJA
// ==========================================

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "staff"
) {

    header("Location: login.php");
    exit();

}


$staff_id = $_SESSION["user_id"];
$message = "";


// ==========================================
// UPDATE BOOKING STATUS
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $booking_id = $_POST["booking_id"] ?? "";
    $status = $_POST["status"] ?? "";

    $allowed_status = [
        "pending",
        "confirmed",
        "completed",
        "cancelled"
    ];

    if (
        is_numeric($booking_id) &&
        in_array($status, $allowed_status, true)
    ) {

        $booking_id = (int)$booking_id;


        // ======================================
        // SEMAK BOOKING MILIK STAFF
        // ======================================

        $check = $conn->prepare(
            "SELECT status
             FROM bookings
             WHERE booking_id = ?
             AND staff_id = ?"
        );

        $check->bind_param("ii", $booking_id, $staff_id);
        $check->execute();

        $checkResult = $check->get_result();

        if ($checkResult->num_rows === 1) {

            $currentBooking = $checkResult->fetch_assoc();
            $currentStatus = $currentBooking["status"];


            // ==================================
            // COMPLETED / CANCELLED = FINAL
            // ==================================

            if (
                $currentStatus === "completed" ||
                $currentStatus === "cancelled"
            ) {

                $message = "Completed or cancelled appointments cannot be changed.";
                $check->close();

            } else {


                // ==================================
                // UPDATE STATUS
                // ==================================

                $update = $conn->prepare(
                    "UPDATE bookings
                     SET status = ?
                     WHERE booking_id = ?
                     AND staff_id = ?"
                );

                $update->bind_param("sii", $status, $booking_id, $staff_id);
                $update->execute();

                $update->close();
                $check->close();

                header("Location: staff_dashboard.php?updated=1");
                exit();

            }

        } else {

            $message = "Appointment not found.";
            $check->close();

        }

    } else {

        $message = "Invalid appointment information.";

    }

}


// ==========================================
// NOTIFICATIONS
// ==========================================

$notificationStmt = $conn->prepare(
    "SELECT
        bookings.booking_id,
        bookings.booking_date,
        bookings.booking_time,
        bookings.status,

        users.name AS customer_name,

        services.service_name

     FROM bookings

     INNER JOIN users
        ON bookings.customer_id = users.user_id

     INNER JOIN services
        ON bookings.service_id = services.service_id

     WHERE bookings.staff_id = ?

     AND bookings.status IN (
        'pending',
        'confirmed'
     )

     AND bookings.booking_date >= CURDATE()

     ORDER BY
        bookings.booking_date ASC,
        bookings.booking_time ASC"
);

$notificationStmt->bind_param("i", $staff_id);
$notificationStmt->execute();

$notifications = $notificationStmt->get_result();


// ==========================================
// AMBIL APPOINTMENT YANG ASSIGNED
// + PAYMENT INFORMATION
// ==========================================

$sql = "
    SELECT

        bookings.booking_id,
        bookings.booking_date,
        bookings.booking_time,
        bookings.status,

        users.name AS customer_name,
        users.email AS customer_email,

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
            SELECT payments.payment_method

            FROM payments

            WHERE payments.booking_id = bookings.booking_id

            ORDER BY payments.payment_id DESC

            LIMIT 1

        ) AS payment_method,

        (
            SELECT payments.amount

            FROM payments

            WHERE payments.booking_id = bookings.booking_id

            AND payments.payment_status = 'paid'

            ORDER BY payments.payment_id DESC

            LIMIT 1

        ) AS paid_amount

    FROM bookings

    INNER JOIN users
        ON bookings.customer_id = users.user_id

    INNER JOIN services
        ON bookings.service_id = services.service_id

    WHERE bookings.staff_id = ?

    ORDER BY
        bookings.booking_date ASC,
        bookings.booking_time ASC
";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $staff_id);
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
        Staff Dashboard - MySalon
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
        href="MySalon(CSS).css?v=2"
    >

    <!-- STAFF DASHBOARD: only things the main stylesheet does not cover.
         Panel, card and field colours now come from MySalon(CSS).css,
         so they match every other page. -->
    <style>

        /* Subtitle text a little larger on this page */
        .section-title p {
            font-size: 16px;
            font-weight: 500;
        }

        /* Status messages (success / error) */
        .notice {
            font-size: 15px;
            font-weight: 500;
            color: var(--cream);
            background: rgba(35, 58, 52, 0.88);
            border: 1px solid var(--line-dark);
            border-left: 4px solid var(--sage);
            border-radius: 6px;
            padding: 14px 18px;
            margin-bottom: 25px;
            text-align: center;
            width: 100%;
            backdrop-filter: blur(6px);
        }

        .notice.notice-error {
            color: #e8a3a3;
            border-left-color: var(--danger);
        }

        /* Cards: spacing and label style only (colours come from the CSS file) */
        .booking-card {
            margin-bottom: 24px;
        }

        .booking-card h3 {
            font-size: 24px;
        }

        .booking-card p {
            font-size: 15.5px;
        }

        .booking-card p strong {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 12px;
            font-weight: 500;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-right: 8px;
        }

        .booking-card select {
            font-size: 15px;
            padding: 14px 16px;
        }

        .booking-card select:focus {
            box-shadow: 0 0 0 3px rgba(191, 122, 74, 0.25);
        }

        .booking-card form .button {
            margin-top: 4px;
        }

        /* Buttons row */
        .booking-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
        }

        .booking-actions .button {
            padding: 12px 24px;
            font-size: 12.5px;
        }

    </style>

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
            MySalon Staff
        </div>


        <!-- DROPDOWN -->

        <div
            class="mysalon-dropdown"
            id="mysalonDropdown"
        >

            <a href="staff_dashboard.php">
                Dashboard
            </a>

            <a href="staff_availability.php">
                Availability
            </a>

            <a href="staff_attendance.php">
                Attendance
            </a>


            <div class="mysalon-dropdown-divider"></div>


            <div class="mysalon-dropdown-user">

                Hi,

                <?php echo htmlspecialchars($_SESSION["name"]); ?>

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

                <?php echo htmlspecialchars($_SESSION["name"]); ?>

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
     STAFF DASHBOARD
=========================================== -->

<section>


    <div class="section-title">

        <h2>
            Staff Dashboard
        </h2>

        <p>
            View and manage your assigned appointments
        </p>

    </div>



    <!-- SUCCESS MESSAGE -->

    <?php if (isset($_GET["updated"])): ?>

        <p class="notice" style="margin-top:30px;">

            Appointment status updated successfully!

        </p>

    <?php endif; ?>



    <!-- ERROR MESSAGE -->

    <?php if (!empty($message)): ?>

        <p class="notice notice-error" style="margin-top:30px;">

            <?php echo htmlspecialchars($message); ?>

        </p>

    <?php endif; ?>



    <!-- ======================================
         NOTIFICATIONS
    ======================================= -->

    <div
        class="section-title"
        style="margin-top:40px;"
    >

        <h2>
            Notifications
        </h2>

        <p>

            <?php echo $notifications->num_rows; ?>

            active notification(s)

        </p>

    </div>



    <?php if ($notifications->num_rows > 0): ?>

        <div class="booking-details" style="margin-top:30px;">


            <?php while ($notification = $notifications->fetch_assoc()): ?>


                <div class="booking-card">


                    <?php if ($notification["status"] === "pending"): ?>

                        <h3>
                            New / Pending Appointment
                        </h3>

                    <?php else: ?>

                        <h3>
                            Confirmed Appointment
                        </h3>

                    <?php endif; ?>



                    <p>

                        <strong>
                            Service:
                        </strong>

                        <?php echo htmlspecialchars($notification["service_name"]); ?>

                    </p>



                    <p>

                        <strong>
                            Customer:
                        </strong>

                        <?php echo htmlspecialchars($notification["customer_name"]); ?>

                    </p>



                    <p>

                        <strong>
                            Date:
                        </strong>

                        <?php echo date("d M Y", strtotime($notification["booking_date"])); ?>

                    </p>



                    <p>

                        <strong>
                            Time:
                        </strong>

                        <?php echo date("h:i A", strtotime($notification["booking_time"])); ?>

                    </p>



                    <p>

                        <strong>
                            Status:
                        </strong>

                        <?php echo ucfirst(htmlspecialchars($notification["status"])); ?>

                    </p>


                </div>


            <?php endwhile; ?>


        </div>

    <?php endif; ?>



    <!-- ======================================
         APPOINTMENTS TITLE
    ======================================= -->

    <div
        class="section-title"
        style="margin-top:70px;"
    >

        <h2>
            My Appointments
        </h2>

        <p>
            Appointments assigned to you
        </p>

    </div>



    <!-- ======================================
         APPOINTMENTS
    ======================================= -->

    <div class="booking-details" style="margin-top:30px;">


        <?php if ($result->num_rows > 0): ?>


            <?php while ($booking = $result->fetch_assoc()): ?>


                <?php

                // ==================================
                // CURRENT PROMOTION PRICE
                // ==================================

                $original_price = (float)$booking["price"];

                $discount = (float)$booking["discount_percent"];

                $promotion_active = (
                    $booking["promotion_status"] === "active"
                    &&
                    $discount > 0
                );

                $current_price = $original_price;

                if ($promotion_active) {

                    $current_price =
                        $original_price
                        -
                        ($original_price * ($discount / 100));

                }

                $current_price = round($current_price, 2);

                $is_paid = (
                    $booking["payment_status"] === "paid"
                    &&
                    $booking["paid_amount"] !== null
                );

                ?>


                <div class="booking-card">


                    <!-- SERVICE -->

                    <h3>

                        <?php echo htmlspecialchars($booking["service_name"]); ?>

                    </h3>



                    <!-- CUSTOMER -->

                    <p>

                        <strong>
                            Customer:
                        </strong>

                        <?php echo htmlspecialchars($booking["customer_name"]); ?>

                    </p>



                    <!-- EMAIL -->

                    <p>

                        <strong>
                            Email:
                        </strong>

                        <?php echo htmlspecialchars($booking["customer_email"]); ?>

                    </p>



                    <!-- DATE -->

                    <p>

                        <strong>
                            Date:
                        </strong>

                        <?php echo htmlspecialchars($booking["booking_date"]); ?>

                    </p>



                    <!-- TIME -->

                    <p>

                        <strong>
                            Time:
                        </strong>

                        <?php echo date("h:i A", strtotime($booking["booking_time"])); ?>

                    </p>



                    <!-- ==================================
                         PRICE / PAYMENT AMOUNT
                    =================================== -->

                    <?php if ($is_paid): ?>


                        <p>

                            <strong>
                                Amount Paid:
                            </strong>

                            $<?php echo number_format($booking["paid_amount"], 2); ?>

                        </p>


                    <?php else: ?>


                        <?php if ($promotion_active): ?>


                            <p>

                                <strong>
                                    Original Price:
                                </strong>

                                <span style="
                                    text-decoration:line-through;
                                    opacity:0.65;
                                ">

                                    $<?php echo number_format($original_price, 2); ?>

                                </span>

                            </p>


                            <p>

                                <strong>
                                    Promotion:
                                </strong>

                                <?php echo number_format($discount, 0); ?>% OFF

                            </p>


                        <?php endif; ?>


                        <p>

                            <strong>
                                Current Price:
                            </strong>

                            $<?php echo number_format($current_price, 2); ?>

                        </p>


                    <?php endif; ?>



                    <!-- PAYMENT STATUS -->

                    <p>

                        <strong>
                            Payment Status:
                        </strong>

                        <?php

                        if ($booking["payment_status"] === "paid") {

                            echo "Paid";

                        } elseif ($booking["payment_status"] === "pending") {

                            echo "Pending";

                        } elseif ($booking["payment_status"] === "failed") {

                            echo "Failed";

                        } else {

                            echo "Not Paid";

                        }

                        ?>

                    </p>



                    <!-- PAYMENT METHOD -->

                    <p>

                        <strong>
                            Payment Method:
                        </strong>

                        <?php

                        if (!empty($booking["payment_method"])) {

                            echo htmlspecialchars($booking["payment_method"]);

                        } else {

                            echo "-";

                        }

                        ?>

                    </p>



                    <!-- DOWNLOAD RECEIPT -->

                    <?php if ($is_paid): ?>

                        <div
                            class="booking-actions"
                            style="margin-top:20px;"
                        >

                            <a
                                href="staff_download_receipt.php?id=<?php echo $booking["payment_id"]; ?>"
                                class="button"
                            >
                                Download Receipt
                            </a>

                        </div>

                    <?php endif; ?>



                    <!-- STATUS -->

                    <p>

                        <strong>
                            Current Status:
                        </strong>

                        <?php echo ucfirst(htmlspecialchars($booking["status"])); ?>

                    </p>



                    <!-- ==================================
                         ACTIVE APPOINTMENT
                    =================================== -->

                    <?php if (
                        $booking["status"] !== "completed" &&
                        $booking["status"] !== "cancelled"
                    ): ?>


                        <form
                            method="POST"
                            action="staff_dashboard.php"
                            style="margin-top:20px;"
                        >


                            <input
                                type="hidden"
                                name="booking_id"
                                value="<?php echo $booking["booking_id"]; ?>"
                            >


                            <div class="form-group">


                                <label>
                                    Update Status
                                </label>


                                <select
                                    name="status"
                                    required
                                >

                                    <option
                                        value="pending"
                                        <?php if ($booking["status"] === "pending") { echo "selected"; } ?>
                                    >
                                        Pending
                                    </option>

                                    <option
                                        value="confirmed"
                                        <?php if ($booking["status"] === "confirmed") { echo "selected"; } ?>
                                    >
                                        Confirmed
                                    </option>

                                    <option value="completed">
                                        Completed
                                    </option>

                                    <option value="cancelled">
                                        Cancelled
                                    </option>

                                </select>


                            </div>


                            <button
                                type="submit"
                                class="button"
                            >

                                Update Status

                            </button>


                        </form>


                    <!-- FINAL STATUS -->

                    <?php else: ?>


                        <p style="margin-top:20px;">

                            <strong>
                                Final Status:
                            </strong>

                            <?php echo ucfirst(htmlspecialchars($booking["status"])); ?>

                        </p>


                    <?php endif; ?>


                </div>


            <?php endwhile; ?>


        <?php else: ?>


            <p class="notice">

                No appointments have been assigned to you.

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

const logoTrigger = document.getElementById("mysalonLogoTrigger");
const dropdown = document.getElementById("mysalonDropdown");


function toggleDropdown() {

    dropdown.classList.toggle("mysalon-dropdown-open");

    const isOpen = dropdown.classList.contains("mysalon-dropdown-open");

    logoTrigger.setAttribute("aria-expanded", isOpen ? "true" : "false");

}


function closeDropdown() {

    dropdown.classList.remove("mysalon-dropdown-open");

    logoTrigger.setAttribute("aria-expanded", "false");

}


logoTrigger.addEventListener("click", function (event) {

    event.stopPropagation();
    toggleDropdown();

});


logoTrigger.addEventListener("keydown", function (event) {

    if (event.key === "Enter" || event.key === " ") {

        event.preventDefault();
        event.stopPropagation();
        toggleDropdown();

    }

});


dropdown.addEventListener("click", function (event) {

    event.stopPropagation();

});


document.addEventListener("click", function () {

    closeDropdown();

});


document.addEventListener("keydown", function (event) {

    if (event.key === "Escape") {

        closeDropdown();

    }

});

</script>


</body>

</html>


<?php

$notificationStmt->close();

$stmt->close();

$conn->close();

?>
