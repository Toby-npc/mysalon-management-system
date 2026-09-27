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


$message = "";


// ==========================================
// ASSIGN STAFF
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $booking_id =
        $_POST["booking_id"] ?? "";

    $staff_id =
        $_POST["staff_id"] ?? "";


    if (
        !is_numeric($booking_id) ||
        !is_numeric($staff_id)
    ) {

        $message =
            "Invalid booking or staff.";

    } else {

        $booking_id =
            (int)$booking_id;

        $staff_id =
            (int)$staff_id;


        // ======================================
        // AMBIL BOOKING
        // ======================================

        $bookingCheck =
            $conn->prepare(
                "SELECT
                    booking_date,
                    booking_time,
                    status

                 FROM bookings

                 WHERE booking_id = ?"
            );


        $bookingCheck->bind_param(
            "i",
            $booking_id
        );


        $bookingCheck->execute();


        $bookingResult =
            $bookingCheck->get_result();


        if (
            $bookingResult->num_rows !== 1
        ) {

            $message =
                "Booking not found.";

            $bookingCheck->close();

        } else {

            $bookingData =
                $bookingResult->fetch_assoc();


            $bookingCheck->close();


            $booking_date =
                $bookingData["booking_date"];

            $booking_time =
                $bookingData["booking_time"];


            // ======================================
            // CANCELLED / COMPLETED TAK BOLEH ASSIGN
            // ======================================

            if (
                $bookingData["status"] === "cancelled" ||
                $bookingData["status"] === "completed"
            ) {

                $message =
                    "Staff cannot be assigned to this booking.";

            } else {


                // ==================================
                // SEMAK STAFF AVAILABLE
                // ==================================

                $availabilityCheck =
                    $conn->prepare(
                        "SELECT
                            staff_availability.availability_id

                         FROM staff_availability

                         INNER JOIN users
                            ON staff_availability.staff_id =
                               users.user_id

                         WHERE staff_availability.staff_id = ?

                         AND users.role = 'staff'

                         AND staff_availability.available_date = ?

                         AND staff_availability.status =
                             'available'

                         AND staff_availability.start_time <= ?

                         AND staff_availability.end_time >= ?

                         LIMIT 1"
                    );


                $availabilityCheck->bind_param(
                    "isss",
                    $staff_id,
                    $booking_date,
                    $booking_time,
                    $booking_time
                );


                $availabilityCheck->execute();


                $availabilityResult =
                    $availabilityCheck->get_result();


                // ==================================
                // STAFF TAK AVAILABLE
                // ==================================

                if (
                    $availabilityResult->num_rows === 0
                ) {

                    $message =
                        "Selected staff is not available at this date and time.";

                    $availabilityCheck->close();

                } else {

                    $availabilityCheck->close();


                    // ==================================
                    // SEMAK DOUBLE BOOKING
                    // ==================================

                    $conflictCheck =
                        $conn->prepare(
                            "SELECT booking_id

                             FROM bookings

                             WHERE staff_id = ?

                             AND booking_date = ?

                             AND booking_time = ?

                             AND status != 'cancelled'

                             AND booking_id != ?

                             LIMIT 1"
                        );


                    $conflictCheck->bind_param(
                        "issi",
                        $staff_id,
                        $booking_date,
                        $booking_time,
                        $booking_id
                    );


                    $conflictCheck->execute();


                    $conflictResult =
                        $conflictCheck->get_result();


                    // ==================================
                    // STAFF SUDAH ADA APPOINTMENT
                    // ==================================

                    if (
                        $conflictResult->num_rows > 0
                    ) {

                        $message =
                            "Selected staff already has another appointment at this time.";

                        $conflictCheck->close();

                    } else {

                        $conflictCheck->close();


                        // ==================================
                        // ASSIGN STAFF
                        // ==================================

                        $update =
                            $conn->prepare(
                                "UPDATE bookings

                                 SET staff_id = ?

                                 WHERE booking_id = ?"
                            );


                        $update->bind_param(
                            "ii",
                            $staff_id,
                            $booking_id
                        );


                        if ($update->execute()) {

                            $update->close();


                            header(
                                "Location: admin.php?assigned=1"
                            );

                            exit();

                        } else {

                            $message =
                                "Unable to assign staff.";

                            $update->close();

                        }

                    }

                }

            }

        }

    }

}


// ==========================================
// AMBIL SEMUA APPOINTMENT
// ==========================================

$sql = "
    SELECT
        bookings.booking_id,
        bookings.staff_id,
        bookings.booking_date,
        bookings.booking_time,
        bookings.status,

        customers.name AS customer_name,
        customers.email AS customer_email,

        services.service_name,
        services.price,

        staff.name AS staff_name

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

    ORDER BY
        bookings.booking_date ASC,
        bookings.booking_time ASC
";


$bookings =
    $conn->query($sql);

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
        Admin Dashboard - MySalon
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
     APPOINTMENTS
=========================================== -->

<section>


    <div class="section-title">

        <h2>
            Manage Appointments
        </h2>

        <p>
            Review appointments and assign available staff
        </p>

    </div>



    <!-- ======================================
         SUCCESS MESSAGE
    ======================================= -->

    <?php if (
        isset($_GET["assigned"])
    ): ?>

        <p style="
            text-align:center;
            margin-bottom:25px;
        ">

            Staff assigned successfully!

        </p>

    <?php endif; ?>



    <!-- ======================================
         ERROR MESSAGE
    ======================================= -->

    <?php if (
        !empty($message)
    ): ?>

        <p style="
            text-align:center;
            margin-bottom:25px;
            color:#9c4444;
        ">

            <?php

            echo htmlspecialchars(
                $message
            );

            ?>

        </p>

    <?php endif; ?>



    <!-- ======================================
         APPOINTMENT CARDS
    ======================================= -->

    <div class="booking-details">


        <?php if (
            $bookings->num_rows > 0
        ): ?>


            <?php while (
                $booking =
                $bookings->fetch_assoc()
            ): ?>


                <div class="booking-card">


                    <!-- SERVICE -->

                    <h3>

                        <?php

                        echo htmlspecialchars(
                            $booking["service_name"]
                        );

                        ?>

                    </h3>



                    <!-- CUSTOMER -->

                    <p>

                        <strong>
                            Customer:
                        </strong>

                        <?php

                        echo htmlspecialchars(
                            $booking["customer_name"]
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
                            $booking["customer_email"]
                        );

                        ?>

                    </p>



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
                                $booking[
                                    "booking_time"
                                ]
                            )
                        );

                        ?>

                    </p>



                    <!-- PRICE -->

                    <p>

                        <strong>
                            Price:
                        </strong>

                        $<?php

                        echo number_format(
                            $booking["price"],
                            2
                        );

                        ?>

                    </p>



                    <!-- STATUS -->

                    <p>

                        <strong>
                            Status:
                        </strong>

                        <?php

                        echo ucfirst(
                            htmlspecialchars(
                                $booking[
                                    "status"
                                ]
                            )
                        );

                        ?>

                    </p>



                    <!-- ASSIGNED STAFF -->

                    <p>

                        <strong>
                            Assigned Staff:
                        </strong>

                        <?php

                        if (
                            !empty(
                                $booking[
                                    "staff_name"
                                ]
                            )
                        ) {

                            echo htmlspecialchars(
                                $booking[
                                    "staff_name"
                                ]
                            );

                        } else {

                            echo "Not assigned";

                        }

                        ?>

                    </p>



                    <!-- ==================================
                         AVAILABLE STAFF
                    =================================== -->

                    <?php


                    $availableStaff = [];


                    // Jangan cari staff untuk
                    // cancelled / completed booking

                    if (
                        $booking["status"] !== "cancelled" &&
                        $booking["status"] !== "completed"
                    ) {


                        $availableStmt =
                            $conn->prepare(
                                "SELECT DISTINCT
                                    users.user_id,
                                    users.name

                                 FROM users

                                 INNER JOIN staff_availability
                                    ON users.user_id =
                                       staff_availability.staff_id

                                 WHERE users.role = 'staff'

                                 AND staff_availability.available_date = ?

                                 AND staff_availability.status =
                                     'available'

                                 AND staff_availability.start_time <= ?

                                 AND staff_availability.end_time >= ?

                                 AND NOT EXISTS
                                 (
                                     SELECT 1

                                     FROM bookings AS conflict

                                     WHERE conflict.staff_id =
                                           users.user_id

                                     AND conflict.booking_date = ?

                                     AND conflict.booking_time = ?

                                     AND conflict.status != 'cancelled'

                                     AND conflict.booking_id != ?
                                 )

                                 ORDER BY users.name ASC"
                            );


                        $availableStmt->bind_param(
                            "sssssi",
                            $booking[
                                "booking_date"
                            ],
                            $booking[
                                "booking_time"
                            ],
                            $booking[
                                "booking_time"
                            ],
                            $booking[
                                "booking_date"
                            ],
                            $booking[
                                "booking_time"
                            ],
                            $booking[
                                "booking_id"
                            ]
                        );


                        $availableStmt->execute();


                        $availableResult =
                            $availableStmt->get_result();


                        while (
                            $staff =
                            $availableResult->fetch_assoc()
                        ) {

                            $availableStaff[] =
                                $staff;

                        }


                        $availableStmt->close();

                    }


                    ?>



                    <!-- ==================================
                         ASSIGN STAFF FORM
                    =================================== -->

                    <?php if (
                        $booking["status"] !== "cancelled" &&
                        $booking["status"] !== "completed"
                    ): ?>


                        <?php if (
                            count($availableStaff) > 0
                        ): ?>


                            <form
                                method="POST"
                                action="admin.php"
                            >


                                <input
                                    type="hidden"
                                    name="booking_id"
                                    value="<?php
                                    echo $booking[
                                        "booking_id"
                                    ];
                                    ?>"
                                >


                                <div class="form-group">


                                    <label>
                                        Assign Staff
                                    </label>


                                    <select
                                        name="staff_id"
                                        required
                                    >


                                        <option value="">
                                            Select Available Staff
                                        </option>


                                        <?php foreach (
                                            $availableStaff as $staff
                                        ): ?>


                                            <option

                                                value="<?php
                                                echo $staff[
                                                    "user_id"
                                                ];
                                                ?>"

                                                <?php

                                                if (
                                                    $booking[
                                                        "staff_id"
                                                    ]
                                                    ==
                                                    $staff[
                                                        "user_id"
                                                    ]
                                                ) {

                                                    echo "selected";

                                                }

                                                ?>

                                            >

                                                <?php

                                                echo htmlspecialchars(
                                                    $staff[
                                                        "name"
                                                    ]
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

                                    Assign Staff

                                </button>


                            </form>


                        <?php else: ?>


                            <p style="
                                margin-top:15px;
                                color:#9c4444;
                            ">

                                No staff available for this appointment.

                            </p>


                        <?php endif; ?>


                    <?php else: ?>


                        <p style="
                            margin-top:15px;
                        ">

                            Staff assignment unavailable for

                            <?php

                            echo htmlspecialchars(
                                $booking[
                                    "status"
                                ]
                            );

                            ?>

                            appointments.

                        </p>


                    <?php endif; ?>


                </div>


            <?php endwhile; ?>


        <?php else: ?>


            <p style="
                text-align:center;
                width:100%;
            ">

                No appointments available.

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

$conn->close();

?>