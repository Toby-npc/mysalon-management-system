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
// AMBIL SEMUA CUSTOMER
// ==========================================

$sql = "
    SELECT
        users.user_id,
        users.name,
        users.email,
        users.created_at,
        COUNT(bookings.booking_id) AS total_bookings

    FROM users

    LEFT JOIN bookings
        ON users.user_id =
           bookings.customer_id

    WHERE users.role = 'customer'

    GROUP BY
        users.user_id,
        users.name,
        users.email,
        users.created_at

    ORDER BY
        users.created_at DESC
";


$customers =
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
        Customers - MySalon
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
     CUSTOMER ACCOUNTS
=========================================== -->

<section>


    <div class="section-title">

        <h2>
            Customer Accounts
        </h2>

        <p>
            View and manage registered MySalon customers
        </p>

    </div>



    <!-- ======================================
         SUCCESS MESSAGE
    ======================================= -->

    <?php if (
        isset($_GET["updated"]) &&
        $_GET["updated"] === "1"
    ): ?>


        <div
            class="booking-card"
            style="
                max-width:700px;
                margin:0 auto 25px auto;
                text-align:center;
            "
        >

            <strong>
                Customer account updated successfully.
            </strong>

        </div>


    <?php endif; ?>



    <!-- ======================================
         CUSTOMER LIST
    ======================================= -->

    <div class="booking-details">


        <?php if (
            $customers &&
            $customers->num_rows > 0
        ): ?>


            <?php while (
                $customer =
                $customers->fetch_assoc()
            ): ?>


                <div class="booking-card">


                    <!-- CUSTOMER NAME -->

                    <h3>

                        <?php

                        echo htmlspecialchars(
                            $customer["name"]
                        );

                        ?>

                    </h3>



                    <!-- CUSTOMER ID -->

                    <p>

                        <strong>
                            Customer ID:
                        </strong>

                        #<?php

                        echo $customer[
                            "user_id"
                        ];

                        ?>

                    </p>



                    <!-- EMAIL -->

                    <p>

                        <strong>
                            Email:
                        </strong>

                        <?php

                        echo htmlspecialchars(
                            $customer["email"]
                        );

                        ?>

                    </p>



                    <!-- TOTAL BOOKINGS -->

                    <p>

                        <strong>
                            Total Bookings:
                        </strong>

                        <?php

                        echo $customer[
                            "total_bookings"
                        ];

                        ?>

                    </p>



                    <!-- REGISTERED DATE -->

                    <p>

                        <strong>
                            Registered:
                        </strong>

                        <?php

                        echo date(
                            "d M Y, h:i A",
                            strtotime(
                                $customer[
                                    "created_at"
                                ]
                            )
                        );

                        ?>

                    </p>



                    <!-- ==================================
                         CUSTOMER ACTIONS
                    =================================== -->

                    <div
                        style="
                            margin-top:20px;
                            display:flex;
                            gap:10px;
                            flex-wrap:wrap;
                        "
                    >


                        <!-- EDIT CUSTOMER -->

                        <a
                            href="edit_customer.php?id=<?php
                            echo $customer[
                                "user_id"
                            ];
                            ?>"
                            class="button"
                        >

                            Edit Customer

                        </a>



                        <!-- RESET PASSWORD -->

                        <a
                            href="reset_customer_password.php?id=<?php
                            echo $customer[
                                "user_id"
                            ];
                            ?>"
                            class="button"
                        >

                            Reset Password

                        </a>


                    </div>


                </div>


            <?php endwhile; ?>


        <?php else: ?>


            <p
                style="
                    text-align:center;
                    width:100%;
                "
            >

                No customer accounts available.

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