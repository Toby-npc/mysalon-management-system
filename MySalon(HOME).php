<?php

session_start();

$isLoggedIn =
    isset($_SESSION["user_id"]) &&
    isset($_SESSION["role"]);

$isCustomer =
    $isLoggedIn &&
    $_SESSION["role"] === "customer";

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Home - MySalon</title>

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



        <!-- ==================================
             LOGO DROPDOWN
        =================================== -->

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


            <?php if ($isCustomer): ?>


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


            <?php else: ?>


                <div class="mysalon-dropdown-divider"></div>


                <a href="login.php">
                    Sign In
                </a>


            <?php endif; ?>


        </div>


    </div>



    <!-- ======================================
         RIGHT NAVIGATION
    ======================================= -->

<!-- ======================================
     RIGHT NAVIGATION
======================================= -->

<ul>

    <?php if ($isCustomer): ?>

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

    <?php else: ?>

        <li>

            <a href="login.php">
                Sign In
            </a>

        </li>

    <?php endif; ?>

</ul>


</nav>



<!-- ==========================================
     HERO
=========================================== -->

<section class="hero">


    <div class="hero-text">


        <h1>

            Look Good.<br>

            <span>
                Feel Confident.
            </span>

        </h1>


        <p>

            Step into an experience of personalized care and
            trend-setting styling. Your premium salon experience
            is just a click away.

        </p>


        <a
            href="MySalon(BOOKING).php"
            class="button"
        >
            Book Appointment Now
        </a>


    </div>



    <div class="hero-box">


        <div class="icon">
            ✨
        </div>


        <p>
            Premium Quality Care
        </p>


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


// OPEN / CLOSE DROPDOWN

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


// CLOSE DROPDOWN

function closeDropdown() {

    dropdown.classList.remove(
        "mysalon-dropdown-open"
    );

    logoTrigger.setAttribute(
        "aria-expanded",
        "false"
    );
}


// CLICK LOGO

logoTrigger.addEventListener(
    "click",
    function(event) {

        event.stopPropagation();

        toggleDropdown();

    }
);


// KEYBOARD SUPPORT

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


// DON'T CLOSE WHEN CLICKING INSIDE DROPDOWN

dropdown.addEventListener(
    "click",
    function(event) {

        event.stopPropagation();

    }
);


// CLICK OUTSIDE

document.addEventListener(
    "click",
    function() {

        closeDropdown();

    }
);


// ESCAPE KEY

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