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
// FILTER
// ==========================================

$filter_date =
    $_GET["date"] ?? "";

$filter_status =
    $_GET["status"] ?? "";

$filter_customer =
    trim($_GET["customer"] ?? "");


// ==========================================
// STATUS YANG DIBENARKAN
// ==========================================

$allowed_status = [
    "pending",
    "confirmed",
    "completed",
    "cancelled"
];


if (
    $filter_status !== "" &&
    !in_array(
        $filter_status,
        $allowed_status,
        true
    )
) {

    $filter_status = "";

}


// ==========================================
// QUERY BOOKING RECORDS
// ==========================================

$sql = "
    SELECT

        bookings.booking_id,
        bookings.booking_date,
        bookings.booking_time,
        bookings.status,
        bookings.created_at,

        customers.name AS customer_name,
        customers.email AS customer_email,

        services.service_name,
        services.price,
        services.discount_percent,
        services.promotion_status,

        staff.name AS staff_name,

        (
            SELECT payments.payment_id

            FROM payments

            WHERE payments.booking_id =
                  bookings.booking_id

            AND payments.payment_status = 'paid'

            ORDER BY payments.payment_id DESC

            LIMIT 1

        ) AS payment_id,

        (
            SELECT payments.payment_status

            FROM payments

            WHERE payments.booking_id =
                  bookings.booking_id

            ORDER BY payments.payment_id DESC

            LIMIT 1

        ) AS payment_status,

        (
            SELECT payments.payment_method

            FROM payments

            WHERE payments.booking_id =
                  bookings.booking_id

            ORDER BY payments.payment_id DESC

            LIMIT 1

        ) AS payment_method,

        (
            SELECT payments.amount

            FROM payments

            WHERE payments.booking_id =
                  bookings.booking_id

            AND payments.payment_status = 'paid'

            ORDER BY payments.payment_id DESC

            LIMIT 1

        ) AS paid_amount

    FROM bookings


    INNER JOIN users AS customers
        ON bookings.customer_id =
           customers.user_id


    INNER JOIN services
        ON bookings.service_id =
           services.service_id


    LEFT JOIN users AS staff
        ON bookings.staff_id =
           staff.user_id


    WHERE 1 = 1
";


$params = [];
$types = "";


// ==========================================
// FILTER DATE
// ==========================================

if (
    $filter_date !== ""
) {

    $sql .= "
        AND bookings.booking_date = ?
    ";


    $params[] =
        $filter_date;

    $types .= "s";

}


// ==========================================
// FILTER STATUS
// ==========================================

if (
    $filter_status !== ""
) {

    $sql .= "
        AND bookings.status = ?
    ";


    $params[] =
        $filter_status;

    $types .= "s";

}


// ==========================================
// FILTER CUSTOMER
// ==========================================

if (
    $filter_customer !== ""
) {

    $sql .= "
        AND
        (
            customers.name LIKE ?
            OR customers.email LIKE ?
        )
    ";


    $customerSearch =
        "%" . $filter_customer . "%";


    $params[] =
        $customerSearch;

    $params[] =
        $customerSearch;

    $types .= "ss";

}


// ==========================================
// ORDER
// ==========================================

$sql .= "
    ORDER BY
        bookings.booking_date DESC,
        bookings.booking_time DESC,
        bookings.booking_id DESC
";


// ==========================================
// PREPARE
// ==========================================

$stmt =
    $conn->prepare($sql);


if (
    !empty($params)
) {

    $stmt->bind_param(
        $types,
        ...$params
    );

}


$stmt->execute();


$records =
    $stmt->get_result();

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
        Booking Records - MySalon
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
     BOOKING RECORDS
=========================================== -->

<section>


    <div class="section-title">

        <h2>
            Booking Records
        </h2>

        <p>
            Review and search appointment history
        </p>

    </div>



    <!-- ======================================
         FILTER
    ======================================= -->

    <div class="booking-form">


        <h3 style="margin-bottom:25px;">
            Filter Records
        </h3>


        <form
            method="GET"
            action="admin_records.php"
        >


            <!-- CUSTOMER -->

            <div class="form-group">

                <label for="customer">
                    Customer
                </label>

                <input
                    type="text"
                    id="customer"
                    name="customer"
                    placeholder="Name or email"
                    value="<?php
                    echo htmlspecialchars(
                        $filter_customer
                    );
                    ?>"
                >

            </div>



            <!-- DATE -->

            <div class="form-group">

                <label for="date">
                    Appointment Date
                </label>

                <input
                    type="date"
                    id="date"
                    name="date"
                    value="<?php
                    echo htmlspecialchars(
                        $filter_date
                    );
                    ?>"
                >

            </div>



            <!-- STATUS -->

            <div class="form-group">

                <label for="status">
                    Booking Status
                </label>


                <select
                    id="status"
                    name="status"
                >


                    <option value="">
                        All Status
                    </option>


                    <?php foreach (
                        $allowed_status as $status
                    ): ?>


                        <option
                            value="<?php
                            echo $status;
                            ?>"

                            <?php

                            if (
                                $filter_status ===
                                $status
                            ) {

                                echo "selected";

                            }

                            ?>
                        >

                            <?php

                            echo ucfirst(
                                $status
                            );

                            ?>

                        </option>


                    <?php endforeach; ?>


                </select>


            </div>



            <button
                type="submit"
                class="button"
            >

                Apply Filter

            </button>


            <a
                href="admin_records.php"
                class="button"
            >

                Clear Filter

            </a>


        </form>


    </div>



    <!-- ======================================
         RECORD LIST
    ======================================= -->

    <div
        class="section-title"
        style="margin-top:60px;"
    >

        <h2>
            Appointment History
        </h2>


        <p>

            <?php

            echo $records->num_rows;

            ?>

            record(s) found

        </p>


    </div>



    <div class="booking-details">


        <?php if (
            $records->num_rows > 0
        ): ?>


            <?php while (
                $record =
                $records->fetch_assoc()
            ): ?>


                <?php


                // ======================================
                // KIRA CURRENT SERVICE PRICE
                // ======================================

                $original_price =
                    (float)$record[
                        "price"
                    ];


                $discount =
                    (float)$record[
                        "discount_percent"
                    ];


                $promotion_active =
                    (
                        $record[
                            "promotion_status"
                        ] === "active"
                        &&
                        $discount > 0
                    );


                $current_price =
                    $original_price;


                if (
                    $promotion_active
                ) {

                    $current_price =
                        $original_price
                        -
                        (
                            $original_price
                            *
                            (
                                $discount / 100
                            )
                        );

                }


                $current_price =
                    round(
                        $current_price,
                        2
                    );


                // ======================================
                // CHECK PAYMENT
                // ======================================

                $is_paid =
                    (
                        $record[
                            "payment_status"
                        ] === "paid"
                        &&
                        $record[
                            "paid_amount"
                        ] !== null
                    );


                ?>


                <div class="booking-card">


                    <!-- SERVICE -->

                    <h3>

                        <?php

                        echo htmlspecialchars(
                            $record[
                                "service_name"
                            ]
                        );

                        ?>

                    </h3>



                    <!-- BOOKING ID -->

                    <p>

                        <strong>
                            Booking ID:
                        </strong>

                        #<?php

                        echo $record[
                            "booking_id"
                        ];

                        ?>

                    </p>



                    <!-- CUSTOMER -->

                    <p>

                        <strong>
                            Customer:
                        </strong>

                        <?php

                        echo htmlspecialchars(
                            $record[
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
                            $record[
                                "customer_email"
                            ]
                        );

                        ?>

                    </p>



                    <!-- STAFF -->

                    <p>

                        <strong>
                            Staff:
                        </strong>

                        <?php

                        if (
                            !empty(
                                $record[
                                    "staff_name"
                                ]
                            )
                        ) {

                            echo htmlspecialchars(
                                $record[
                                    "staff_name"
                                ]
                            );

                        } else {

                            echo "Not assigned";

                        }

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
                                $record[
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
                                $record[
                                    "booking_time"
                                ]
                            )
                        );

                        ?>

                    </p>



                    <!-- ==================================
                         PRICE
                    =================================== -->

                    <?php if (
                        $is_paid
                    ): ?>


                        <p>

                            <strong>
                                Original Service Price:
                            </strong>

                            $<?php

                            echo number_format(
                                $original_price,
                                2
                            );

                            ?>

                        </p>


                        <p>

                            <strong>
                                Amount Paid:
                            </strong>

                            $<?php

                            echo number_format(
                                $record[
                                    "paid_amount"
                                ],
                                2
                            );

                            ?>

                        </p>


                    <?php else: ?>


                        <?php if (
                            $promotion_active
                        ): ?>


                            <p>

                                <strong>
                                    Original Price:
                                </strong>

                                <span
                                    style="
                                        text-decoration:line-through;
                                        opacity:0.65;
                                    "
                                >

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
                                Current Price:
                            </strong>

                            $<?php

                            echo number_format(
                                $current_price,
                                2
                            );

                            ?>

                        </p>


                    <?php endif; ?>



                    <!-- BOOKING STATUS -->

                    <p>

                        <strong>
                            Booking Status:
                        </strong>

                        <?php

                        echo ucfirst(
                            htmlspecialchars(
                                $record[
                                    "status"
                                ]
                            )
                        );

                        ?>

                    </p>



                    <!-- PAYMENT STATUS -->

                    <p>

                        <strong>
                            Payment Status:
                        </strong>

                        <?php

                        if (
                            !empty(
                                $record[
                                    "payment_status"
                                ]
                            )
                        ) {

                            echo ucfirst(
                                htmlspecialchars(
                                    $record[
                                        "payment_status"
                                    ]
                                )
                            );

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

                        if (
                            !empty(
                                $record[
                                    "payment_method"
                                ]
                            )
                        ) {

                            echo htmlspecialchars(
                                $record[
                                    "payment_method"
                                ]
                            );

                        } else {

                            echo "-";

                        }

                        ?>

                    </p>



                    <!-- CREATED -->

                    <p>

                        <strong>
                            Booking Created:
                        </strong>

                        <?php

                        echo date(
                            "d M Y h:i A",
                            strtotime(
                                $record[
                                    "created_at"
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

                No booking records found.

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

$stmt->close();
$conn->close();

?>