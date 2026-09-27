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
// CREATE STAFF ACCOUNT
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name =
        trim($_POST["name"] ?? "");

    $email =
        trim($_POST["email"] ?? "");

    $password =
        $_POST["password"] ?? "";


    // ======================================
    // VALIDATION
    // ======================================

    if (
        empty($name) ||
        empty($email) ||
        empty($password)
    ) {

        $message =
            "Please complete all staff information.";

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $message =
            "Please enter a valid email address.";

    } elseif (
        strlen($password) < 6
    ) {

        $message =
            "Password must be at least 6 characters.";

    } else {


        // ==================================
        // CHECK EMAIL
        // ==================================

        $check =
            $conn->prepare(
                "SELECT user_id
                 FROM users
                 WHERE email = ?"
            );


        $check->bind_param(
            "s",
            $email
        );


        $check->execute();


        $checkResult =
            $check->get_result();


        if (
            $checkResult->num_rows > 0
        ) {

            $message =
                "This email is already registered.";

        } else {


            // ==================================
            // HASH PASSWORD
            // ==================================

            $hashedPassword =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


            // ==================================
            // ROLE SENTIASA STAFF
            // ==================================

            $role = "staff";


            // ==================================
            // INSERT STAFF
            // ==================================

            $stmt =
                $conn->prepare(
                    "INSERT INTO users
                    (
                        name,
                        email,
                        password,
                        role
                    )
                    VALUES (?, ?, ?, ?)"
                );


            $stmt->bind_param(
                "ssss",
                $name,
                $email,
                $hashedPassword,
                $role
            );


            if ($stmt->execute()) {

                $stmt->close();
                $check->close();


                header(
                    "Location: admin_staff.php?created=1"
                );

                exit();

            } else {

                $message =
                    "Unable to create staff account.";

            }


            $stmt->close();

        }


        $check->close();

    }

}


// ==========================================
// GET ALL STAFF
// ==========================================

$staffResult =
    $conn->query(
        "SELECT
            user_id,
            name,
            email,
            created_at

         FROM users

         WHERE role = 'staff'

         ORDER BY user_id DESC"
    );

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
        Manage Staff - MySalon
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
     MANAGE STAFF
=========================================== -->

<section>


    <div class="section-title">

        <h2>
            Manage Staff
        </h2>

        <p>
            Create and manage staff accounts
        </p>

    </div>



    <!-- ======================================
         SUCCESS MESSAGE
    ======================================= -->

    <?php if (
        isset($_GET["created"])
    ): ?>

        <p style="
            text-align:center;
            margin-bottom:25px;
        ">

            Staff account created successfully!

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
         CREATE STAFF
    ======================================= -->

    <div class="booking-form">


        <h3 style="margin-bottom:25px;">
            Add New Staff
        </h3>


        <form
            method="POST"
            action="admin_staff.php"
        >


            <!-- NAME -->

            <div class="form-group">

                <label for="name">
                    Staff Name
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="Enter staff name"
                    required
                >

            </div>



            <!-- EMAIL -->

            <div class="form-group">

                <label for="email">
                    Staff Email
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter staff email"
                    required
                >

            </div>



            <!-- PASSWORD -->

            <div class="form-group">

                <label for="password">
                    Temporary Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Minimum 6 characters"
                    minlength="6"
                    required
                >

            </div>



            <button
                type="submit"
                class="button confirm-button"
            >

                Create Staff Account

            </button>


        </form>


    </div>



    <!-- ======================================
         STAFF LIST
    ======================================= -->

    <div
        class="section-title"
        style="margin-top:60px;"
    >

        <h2>
            Staff Accounts
        </h2>

        <p>
            Registered MySalon staff members
        </p>

    </div>



    <div class="booking-details">


        <?php if (
            $staffResult->num_rows > 0
        ): ?>


            <?php while (
                $staff =
                $staffResult->fetch_assoc()
            ): ?>


                <div class="booking-card">


                    <h3>

                        <?php

                        echo htmlspecialchars(
                            $staff["name"]
                        );

                        ?>

                    </h3>



                    <!-- STAFF ID -->

                    <p>

                        <strong>
                            Staff ID:
                        </strong>

                        <?php

                        echo $staff["user_id"];

                        ?>

                    </p>



                    <!-- EMAIL -->

                    <p>

                        <strong>
                            Email:
                        </strong>

                        <?php

                        echo htmlspecialchars(
                            $staff["email"]
                        );

                        ?>

                    </p>



                    <!-- ROLE -->

                    <p>

                        <strong>
                            Role:
                        </strong>

                        Staff

                    </p>



                    <!-- ACCOUNT CREATED -->

                    <p>

                        <strong>
                            Account Created:
                        </strong>

                        <?php

                        echo date(
                            "d M Y",
                            strtotime(
                                $staff["created_at"]
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

                No staff accounts available.

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