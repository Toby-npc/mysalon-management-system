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

$filter_staff =
    $_GET["staff_id"] ?? "";


// ==========================================
// AMBIL SENARAI STAFF
// ==========================================

$staffList =
    $conn->query(
        "SELECT
            user_id,
            name

         FROM users

         WHERE role = 'staff'

         ORDER BY name ASC"
    );


// ==========================================
// QUERY SCHEDULE
// ==========================================

$sql = "
    SELECT
        staff_availability.availability_id,
        staff_availability.available_date,
        staff_availability.start_time,
        staff_availability.end_time,
        staff_availability.status,

        users.user_id AS staff_id,
        users.name AS staff_name,
        users.email AS staff_email

    FROM staff_availability

    INNER JOIN users
        ON staff_availability.staff_id =
           users.user_id

    WHERE users.role = 'staff'
";


$params = [];
$types = "";


// ==========================================
// FILTER TARIKH
// ==========================================

if (
    !empty($filter_date)
) {

    $sql .= "
        AND staff_availability.available_date = ?
    ";


    $params[] =
        $filter_date;

    $types .= "s";

}


// ==========================================
// FILTER STAFF
// ==========================================

if (
    !empty($filter_staff) &&
    is_numeric($filter_staff)
) {

    $sql .= "
        AND users.user_id = ?
    ";


    $params[] =
        $filter_staff;

    $types .= "i";

}


// ==========================================
// ORDER
// ==========================================

$sql .= "
    ORDER BY
        staff_availability.available_date ASC,
        staff_availability.start_time ASC,
        users.name ASC
";


// ==========================================
// PREPARE QUERY
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


$schedules =
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
        Staff Schedule - MySalon
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
     SCHEDULE
=========================================== -->

<section>


    <div class="section-title">

        <h2>
            Staff Schedule
        </h2>

        <p>
            Review staff availability and working hours
        </p>

    </div>



    <!-- ======================================
         FILTER
    ======================================= -->

    <div class="booking-form">


        <h3 style="margin-bottom:25px;">
            Filter Schedule
        </h3>


        <form
            method="GET"
            action="admin_schedule.php"
        >


            <!-- DATE -->

            <div class="form-group">

                <label for="date">
                    Date
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



            <!-- STAFF -->

            <div class="form-group">

                <label for="staff_id">
                    Staff
                </label>


                <select
                    id="staff_id"
                    name="staff_id"
                >


                    <option value="">
                        All Staff
                    </option>


                    <?php while (
                        $staff =
                        $staffList->fetch_assoc()
                    ): ?>


                        <option
                            value="<?php
                            echo $staff[
                                "user_id"
                            ];
                            ?>"

                            <?php

                            if (
                                (string)$filter_staff ===
                                (string)$staff[
                                    "user_id"
                                ]
                            ) {

                                echo "selected";

                            }

                            ?>
                        >

                            <?php

                            echo htmlspecialchars(
                                $staff["name"]
                            );

                            ?>

                        </option>


                    <?php endwhile; ?>


                </select>


            </div>



            <!-- APPLY FILTER -->

            <button
                type="submit"
                class="button"
            >

                Apply Filter

            </button>



            <!-- CLEAR FILTER -->

            <a
                href="admin_schedule.php"
                class="button"
            >

                Clear Filter

            </a>


        </form>


    </div>



    <!-- ======================================
         SCHEDULE LIST
    ======================================= -->

    <div
        class="section-title"
        style="margin-top:60px;"
    >

        <h2>
            Schedule List
        </h2>

        <p>
            Availability submitted by salon staff
        </p>

    </div>



    <div class="booking-details">


        <?php if (
            $schedules->num_rows > 0
        ): ?>


            <?php while (
                $schedule =
                $schedules->fetch_assoc()
            ): ?>


                <div class="booking-card">


                    <!-- STAFF NAME -->

                    <h3>

                        <?php

                        echo htmlspecialchars(
                            $schedule[
                                "staff_name"
                            ]
                        );

                        ?>

                    </h3>



                    <!-- EMAIL -->

                    <p>

                        <strong>
                            Email:
                        </strong>

                        <?php

                        echo htmlspecialchars(
                            $schedule[
                                "staff_email"
                            ]
                        );

                        ?>

                    </p>



                    <!-- DATE -->

                    <p>

                        <strong>
                            Date:
                        </strong>

                        <?php

                        echo date(
                            "d M Y",
                            strtotime(
                                $schedule[
                                    "available_date"
                                ]
                            )
                        );

                        ?>

                    </p>



                    <!-- START TIME -->

                    <p>

                        <strong>
                            Start Time:
                        </strong>

                        <?php

                        echo date(
                            "h:i A",
                            strtotime(
                                $schedule[
                                    "start_time"
                                ]
                            )
                        );

                        ?>

                    </p>



                    <!-- END TIME -->

                    <p>

                        <strong>
                            End Time:
                        </strong>

                        <?php

                        echo date(
                            "h:i A",
                            strtotime(
                                $schedule[
                                    "end_time"
                                ]
                            )
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
                                $schedule[
                                    "status"
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

                No staff schedule found.

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