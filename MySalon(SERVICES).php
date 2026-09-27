<?php

session_start();
include "db.php";


// ==========================================
// CHECK LOGIN
// ==========================================

$isLoggedIn =
    isset($_SESSION["user_id"]) &&
    isset($_SESSION["role"]);

$isCustomer =
    $isLoggedIn &&
    $_SESSION["role"] === "customer";


// ==========================================
// GET AVAILABLE SERVICES
// ==========================================

$sql = "
    SELECT
        service_id,
        service_name,
        description,
        price,
        status,
        discount_percent,
        promotion_status
    FROM services
    WHERE status = 'available'
    ORDER BY service_id ASC
";

$services = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Services - MySalon</title>

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


    <!-- RIGHT NAVIGATION -->

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
     SERVICES
=========================================== -->

<section>

    <div class="section-title">

        <h2>
            Our Services
        </h2>

        <p>
            Choose from our premium tailored menu selections
        </p>

    </div>


    <div class="services">


        <?php if (
            $services &&
            $services->num_rows > 0
        ): ?>


            <?php while (
                $service =
                $services->fetch_assoc()
            ): ?>


                <?php

                // ==================================
                // CALCULATE PROMOTION PRICE
                // ==================================

                $original_price =
                    (float)$service["price"];

                $discount =
                    (float)$service[
                        "discount_percent"
                    ];


                $promotion_active =
                    $service[
                        "promotion_status"
                    ] === "active"
                    &&
                    $discount > 0;


                $final_price =
                    $original_price;


                if ($promotion_active) {

                    $final_price =
                        $original_price -
                        (
                            $original_price *
                            ($discount / 100)
                        );

                }

                ?>


                <div class="service-card">


                    <!-- ICON -->

                    <div class="service-icon">

                        <?php

                        $name =
                            strtolower(
                                $service[
                                    "service_name"
                                ]
                            );


                        if (
                            strpos(
                                $name,
                                "color"
                            ) !== false
                        ) {

                            echo "🎨";

                        } elseif (
                            strpos(
                                $name,
                                "manicure"
                            ) !== false
                            ||
                            strpos(
                                $name,
                                "pedicure"
                            ) !== false
                        ) {

                            echo "💅";

                        } elseif (
                            strpos(
                                $name,
                                "facial"
                            ) !== false
                            ||
                            strpos(
                                $name,
                                "spa"
                            ) !== false
                        ) {

                            echo "💆‍♂️";

                        } else {

                            echo "💇‍♀️";

                        }

                        ?>

                    </div>


                    <!-- SERVICE NAME -->

                    <h3>

                        <?php
                        echo htmlspecialchars(
                            $service[
                                "service_name"
                            ]
                        );
                        ?>

                    </h3>


                    <!-- DESCRIPTION -->

                    <p>

                        <?php
                        echo htmlspecialchars(
                            $service[
                                "description"
                            ] ?? ""
                        );
                        ?>

                    </p>


                    <!-- PROMOTION ACTIVE -->

                    <?php if (
                        $promotion_active
                    ): ?>


                        <p style="
                            margin-bottom:5px;
                            opacity:0.65;
                        ">

                            Original Price:

                            <span style="
                                text-decoration:line-through;
                            ">

                                $<?php
                                echo number_format(
                                    $original_price,
                                    2
                                );
                                ?>

                            </span>

                        </p>


                        <p style="
                            font-weight:bold;
                            margin-bottom:5px;
                        ">

                            <?php
                            echo number_format(
                                $discount,
                                0
                            );
                            ?>% OFF

                        </p>


                        <div class="price">

                            $<?php
                            echo number_format(
                                $final_price,
                                2
                            );
                            ?>

                        </div>


                    <?php else: ?>


                        <div class="price">

                            $<?php
                            echo number_format(
                                $original_price,
                                2
                            );
                            ?>

                        </div>


                    <?php endif; ?>


                    <!-- SELECT BUTTON -->

                    <a
                        href="MySalon(BOOKING).php?service_id=<?php
                        echo $service["service_id"];
                        ?>"
                        class="button"
                    >
                        Select
                    </a>


                </div>


            <?php endwhile; ?>


        <?php else: ?>


            <p style="
                text-align:center;
                width:100%;
            ">

                No services are currently available.

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

<?php

$conn->close();

?>