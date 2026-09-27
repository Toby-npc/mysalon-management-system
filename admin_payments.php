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
// DATE FILTER
// ==========================================

$from_date = $_GET["from_date"] ?? "";
$to_date   = $_GET["to_date"] ?? "";


// ==========================================
// VALIDATE DATE
// ==========================================

function validDate($date)
{

    if ($date === "") {

        return true;

    }


    $d = DateTime::createFromFormat(
        "Y-m-d",
        $date
    );


    return (
        $d &&
        $d->format("Y-m-d") === $date
    );

}


if (!validDate($from_date)) {

    $from_date = "";

}


if (!validDate($to_date)) {

    $to_date = "";

}


// ==========================================
// FROM DATE TAK BOLEH LEPAS TO DATE
// ==========================================

$date_error = "";


if (
    $from_date !== "" &&
    $to_date !== "" &&
    $from_date > $to_date
) {

    $date_error =
        "From Date cannot be later than To Date.";

}


// ==========================================
// BINA DATE CONDITION
// ==========================================

$dateCondition = "";
$params = [];
$types = "";


if ($date_error === "") {

    if (
        $from_date !== "" &&
        $to_date !== ""
    ) {

        $dateCondition =
            " AND DATE(payment_date) BETWEEN ? AND ? ";

        $params[] = $from_date;
        $params[] = $to_date;

        $types .= "ss";

    } elseif (
        $from_date !== ""
    ) {

        $dateCondition =
            " AND DATE(payment_date) >= ? ";

        $params[] = $from_date;

        $types .= "s";

    } elseif (
        $to_date !== ""
    ) {

        $dateCondition =
            " AND DATE(payment_date) <= ? ";

        $params[] = $to_date;

        $types .= "s";

    }

}


// ==========================================
// TOTAL INCOME
// ==========================================

$totalIncomeSql = "
    SELECT
        COALESCE(
            SUM(amount),
            0
        ) AS total_income

    FROM payments

    WHERE payment_status = 'paid'

    $dateCondition
";


$totalIncomeStmt =
    $conn->prepare(
        $totalIncomeSql
    );


if (!empty($params)) {

    $totalIncomeStmt->bind_param(
        $types,
        ...$params
    );

}


$totalIncomeStmt->execute();


$totalIncomeData =
    $totalIncomeStmt
    ->get_result()
    ->fetch_assoc();


$totalIncome =
    $totalIncomeData[
        "total_income"
    ];


$totalIncomeStmt->close();


// ==========================================
// SUCCESSFUL PAYMENTS
// ==========================================

$totalPaidSql = "
    SELECT
        COUNT(*) AS total_paid

    FROM payments

    WHERE payment_status = 'paid'

    $dateCondition
";


$totalPaidStmt =
    $conn->prepare(
        $totalPaidSql
    );


if (!empty($params)) {

    $totalPaidStmt->bind_param(
        $types,
        ...$params
    );

}


$totalPaidStmt->execute();


$totalPaidData =
    $totalPaidStmt
    ->get_result()
    ->fetch_assoc();


$totalPaid =
    $totalPaidData[
        "total_paid"
    ];


$totalPaidStmt->close();


// ==========================================
// PENDING PAYMENTS
// ==========================================

$totalPendingSql = "
    SELECT
        COUNT(*) AS total_pending

    FROM payments

    WHERE payment_status = 'pending'

    $dateCondition
";


$totalPendingStmt =
    $conn->prepare(
        $totalPendingSql
    );


if (!empty($params)) {

    $totalPendingStmt->bind_param(
        $types,
        ...$params
    );

}


$totalPendingStmt->execute();


$totalPendingData =
    $totalPendingStmt
    ->get_result()
    ->fetch_assoc();


$totalPending =
    $totalPendingData[
        "total_pending"
    ];


$totalPendingStmt->close();


// ==========================================
// PAYMENT HISTORY
// ==========================================

$paymentSql = "
    SELECT

        payments.payment_id,
        payments.amount,
        payments.payment_method,
        payments.payment_status,
        payments.payment_date,

        bookings.booking_id,
        bookings.booking_date,
        bookings.booking_time,

        users.name AS customer_name,
        users.email AS customer_email,

        services.service_name

    FROM payments

    INNER JOIN bookings
        ON payments.booking_id =
           bookings.booking_id

    INNER JOIN users
        ON bookings.customer_id =
           users.user_id

    INNER JOIN services
        ON bookings.service_id =
           services.service_id

    WHERE 1 = 1

    $dateCondition

    ORDER BY
        payments.payment_date DESC
";


$paymentStmt =
    $conn->prepare(
        $paymentSql
    );


if (!empty($params)) {

    $paymentStmt->bind_param(
        $types,
        ...$params
    );

}


$paymentStmt->execute();


$payments =
    $paymentStmt->get_result();

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
        Payments - MySalon
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
            MySalon Admin
        </div>


        <!-- ADMIN DROPDOWN -->

        <div
            class="mysalon-dropdown"
            id="mysalonDropdown"
        >

            <a href="admin.php">
                Appointments
            </a>

            <a href="admin_staff.php">
                Staff
            </a>

            <a href="admin_services.php">
                Services
            </a>

            <a href="admin_inventory.php">
                Inventory
            </a>

            <a href="admin_payments.php">
                Payments
            </a>

            <a href="admin_customers.php">
                Customers
            </a>

            <a href="admin_schedule.php">
                Schedule
            </a>

            <a href="admin_records.php">
                Records
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
     PAYMENT SECTION
=========================================== -->

<section>


    <div class="section-title">

        <h2>
            Payments & Income
        </h2>

        <p>
            Review customer payments and salon income
        </p>

    </div>



    <!-- ======================================
         DATE FILTER
    ======================================= -->

    <div class="booking-form">


        <h3 style="margin-bottom:25px;">
            Income Report
        </h3>


        <?php if (
            $date_error !== ""
        ): ?>

            <p style="
                color:#9c4444;
                margin-bottom:20px;
            ">

                <?php

                echo htmlspecialchars(
                    $date_error
                );

                ?>

            </p>

        <?php endif; ?>


        <form
            method="GET"
            action="admin_payments.php"
        >


            <!-- FROM DATE -->

            <div class="form-group">

                <label for="from_date">
                    From Date
                </label>

                <input
                    type="date"
                    id="from_date"
                    name="from_date"
                    value="<?php
                    echo htmlspecialchars(
                        $from_date
                    );
                    ?>"
                >

            </div>



            <!-- TO DATE -->

            <div class="form-group">

                <label for="to_date">
                    To Date
                </label>

                <input
                    type="date"
                    id="to_date"
                    name="to_date"
                    value="<?php
                    echo htmlspecialchars(
                        $to_date
                    );
                    ?>"
                >

            </div>



            <button
                type="submit"
                class="button"
            >

                Apply Filter

            </button>


            <a
                href="admin_payments.php"
                class="button"
            >

                Clear Filter

            </a>


        </form>


    </div>



    <!-- ======================================
         CURRENT REPORT PERIOD
    ======================================= -->

    <div
        class="section-title"
        style="margin-top:50px;"
    >

        <h2>
            Report Summary
        </h2>


        <p>


            <?php if (
                $from_date !== "" &&
                $to_date !== ""
            ): ?>


                <?php

                echo date(
                    "d M Y",
                    strtotime(
                        $from_date
                    )
                );

                ?>


                -


                <?php

                echo date(
                    "d M Y",
                    strtotime(
                        $to_date
                    )
                );

                ?>


            <?php elseif (
                $from_date !== ""
            ): ?>


                From


                <?php

                echo date(
                    "d M Y",
                    strtotime(
                        $from_date
                    )
                );

                ?>


            <?php elseif (
                $to_date !== ""
            ): ?>


                Until


                <?php

                echo date(
                    "d M Y",
                    strtotime(
                        $to_date
                    )
                );

                ?>


            <?php else: ?>


                All Time


            <?php endif; ?>


        </p>


    </div>



    <!-- ======================================
         SUMMARY
    ======================================= -->

    <div class="booking-details">


        <!-- TOTAL INCOME -->

        <div class="booking-card">


            <h3>
                Total Income
            </h3>


            <p style="
                font-size:28px;
                font-weight:bold;
                margin-top:15px;
            ">

                $<?php

                echo number_format(
                    $totalIncome,
                    2
                );

                ?>

            </p>


        </div>



        <!-- SUCCESSFUL PAYMENTS -->

        <div class="booking-card">


            <h3>
                Successful Payments
            </h3>


            <p style="
                font-size:28px;
                font-weight:bold;
                margin-top:15px;
            ">

                <?php

                echo $totalPaid;

                ?>

            </p>


        </div>



        <!-- PENDING PAYMENTS -->

        <div class="booking-card">


            <h3>
                Pending Payments
            </h3>


            <p style="
                font-size:28px;
                font-weight:bold;
                margin-top:15px;
            ">

                <?php

                echo $totalPending;

                ?>

            </p>


        </div>


    </div>



    <!-- ======================================
         PAYMENT HISTORY TITLE
    ======================================= -->

    <div
        class="section-title"
        style="margin-top:60px;"
    >

        <h2>
            Payment History
        </h2>


        <p>

            <?php

            echo $payments->num_rows;

            ?>

            transaction(s) found

        </p>


    </div>



    <!-- ======================================
         PAYMENT LIST
    ======================================= -->

    <div class="booking-details">


        <?php if (
            $payments &&
            $payments->num_rows > 0
        ): ?>


            <?php while (
                $payment =
                $payments->fetch_assoc()
            ): ?>


                <div class="booking-card">


                    <!-- PAYMENT ID -->

                    <h3>

                        Payment #<?php
                        echo $payment[
                            "payment_id"
                        ];
                        ?>

                    </h3>



                    <!-- CUSTOMER -->

                    <p>

                        <strong>
                            Customer:
                        </strong>

                        <?php

                        echo htmlspecialchars(
                            $payment[
                                "customer_name"
                            ]
                        );

                        ?>

                    </p>



                    <!-- EMAIL -->

                    <p>

                        <strong>
                            Email:
                        </strong>

                        <?php

                        echo htmlspecialchars(
                            $payment[
                                "customer_email"
                            ]
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
                            $payment[
                                "service_name"
                            ]
                        );

                        ?>

                    </p>



                    <!-- APPOINTMENT -->

                    <p>

                        <strong>
                            Appointment:
                        </strong>

                        <?php

                        echo date(
                            "d M Y",
                            strtotime(
                                $payment[
                                    "booking_date"
                                ]
                            )
                        );

                        ?>

                        at

                        <?php

                        echo date(
                            "h:i A",
                            strtotime(
                                $payment[
                                    "booking_time"
                                ]
                            )
                        );

                        ?>

                    </p>



                    <!-- AMOUNT -->

                    <p>

                        <strong>
                            Amount:
                        </strong>

                        $<?php

                        echo number_format(
                            $payment[
                                "amount"
                            ],
                            2
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
                            $payment[
                                "payment_method"
                            ]
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
                                $payment[
                                    "payment_status"
                                ]
                            )
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
                                $payment[
                                    "payment_date"
                                ]
                            )
                        );

                        ?>

                    </p>


                </div>


            <?php endwhile; ?>


        <?php else: ?>


            <p style="
                text-align:center;
                width:100%;
            ">

                No payment records available
                for the selected period.

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

        if (
            event.key === "Escape"
        ) {

            closeDropdown();

        }

    }
);


</script>


</body>

</html>


<?php

$paymentStmt->close();
$conn->close();

?>