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
// ADD / UPDATE SERVICE
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";


    // ======================================
    // ADD SERVICE
    // ======================================

    if ($action === "add") {

        $service_name =
            trim($_POST["service_name"] ?? "");

        $description =
            trim($_POST["description"] ?? "");

        $price =
            $_POST["price"] ?? "";

        $status =
            $_POST["status"] ?? "available";

        $discount_percent =
            $_POST["discount_percent"] ?? "0";

        $promotion_status =
            $_POST["promotion_status"] ?? "inactive";


        // ==================================
        // VALIDATION
        // ==================================

        if (
            empty($service_name) ||
            $price === ""
        ) {

            $message =
                "Please complete the required information.";

        } elseif (
            !is_numeric($price) ||
            $price < 0
        ) {

            $message =
                "Please enter a valid price.";

        } elseif (
            !is_numeric($discount_percent) ||
            $discount_percent < 0 ||
            $discount_percent > 100
        ) {

            $message =
                "Discount must be between 0% and 100%.";

        } elseif (
            !in_array(
                $status,
                [
                    "available",
                    "unavailable"
                ],
                true
            )
        ) {

            $message =
                "Invalid service status.";

        } elseif (
            !in_array(
                $promotion_status,
                [
                    "active",
                    "inactive"
                ],
                true
            )
        ) {

            $message =
                "Invalid promotion status.";

        } else {

            $price =
                (float)$price;

            $discount_percent =
                (float)$discount_percent;


            $stmt =
                $conn->prepare(
                    "INSERT INTO services
                    (
                        service_name,
                        description,
                        price,
                        status,
                        discount_percent,
                        promotion_status
                    )
                    VALUES (?, ?, ?, ?, ?, ?)"
                );


            $stmt->bind_param(
                "ssdsds",
                $service_name,
                $description,
                $price,
                $status,
                $discount_percent,
                $promotion_status
            );


            if ($stmt->execute()) {

                $stmt->close();

                header(
                    "Location: admin_services.php?added=1"
                );

                exit();

            } else {

                $message =
                    "Unable to add service.";

            }


            $stmt->close();

        }

    }


    // ======================================
    // UPDATE SERVICE
    // ======================================

    elseif ($action === "update") {

        $service_id =
            $_POST["service_id"] ?? "";

        $service_name =
            trim($_POST["service_name"] ?? "");

        $description =
            trim($_POST["description"] ?? "");

        $price =
            $_POST["price"] ?? "";

        $status =
            $_POST["status"] ?? "";

        $discount_percent =
            $_POST["discount_percent"] ?? "0";

        $promotion_status =
            $_POST["promotion_status"] ?? "inactive";


        // ==================================
        // VALIDATION
        // ==================================

        if (
            !is_numeric($service_id) ||
            empty($service_name) ||
            $price === ""
        ) {

            $message =
                "Please complete the required information.";

        } elseif (
            !is_numeric($price) ||
            $price < 0
        ) {

            $message =
                "Please enter a valid price.";

        } elseif (
            !is_numeric($discount_percent) ||
            $discount_percent < 0 ||
            $discount_percent > 100
        ) {

            $message =
                "Discount must be between 0% and 100%.";

        } elseif (
            !in_array(
                $status,
                [
                    "available",
                    "unavailable"
                ],
                true
            )
        ) {

            $message =
                "Invalid service status.";

        } elseif (
            !in_array(
                $promotion_status,
                [
                    "active",
                    "inactive"
                ],
                true
            )
        ) {

            $message =
                "Invalid promotion status.";

        } else {

            $service_id =
                (int)$service_id;

            $price =
                (float)$price;

            $discount_percent =
                (float)$discount_percent;


            $stmt =
                $conn->prepare(
                    "UPDATE services

                     SET
                        service_name = ?,
                        description = ?,
                        price = ?,
                        status = ?,
                        discount_percent = ?,
                        promotion_status = ?

                     WHERE service_id = ?"
                );


            $stmt->bind_param(
                "ssdsdsi",
                $service_name,
                $description,
                $price,
                $status,
                $discount_percent,
                $promotion_status,
                $service_id
            );


            if ($stmt->execute()) {

                $stmt->close();

                header(
                    "Location: admin_services.php?updated=1"
                );

                exit();

            } else {

                $message =
                    "Unable to update service.";

            }


            $stmt->close();

        }

    }

}


// ==========================================
// GET ALL SERVICES
// ==========================================

$services =
    $conn->query(
        "SELECT
            service_id,
            service_name,
            description,
            price,
            status,
            discount_percent,
            promotion_status

         FROM services

         ORDER BY service_id ASC"
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
        Manage Services - MySalon
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
     MANAGE SERVICES
=========================================== -->

<section>


    <div class="section-title">

        <h2>
            Manage Services
        </h2>

        <p>
            Add and manage salon services and promotions
        </p>

    </div>



    <!-- ======================================
         SUCCESS MESSAGES
    ======================================= -->

    <?php if (
        isset($_GET["added"])
    ): ?>

        <p style="
            text-align:center;
            margin-bottom:25px;
        ">

            Service added successfully!

        </p>

    <?php endif; ?>


    <?php if (
        isset($_GET["updated"])
    ): ?>

        <p style="
            text-align:center;
            margin-bottom:25px;
        ">

            Service updated successfully!

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
         ADD NEW SERVICE
    ======================================= -->

    <div class="booking-form">


        <h3 style="margin-bottom:25px;">
            Add New Service
        </h3>


        <form
            method="POST"
            action="admin_services.php"
        >


            <input
                type="hidden"
                name="action"
                value="add"
            >



            <!-- SERVICE NAME -->

            <div class="form-group">

                <label>
                    Service Name
                </label>

                <input
                    type="text"
                    name="service_name"
                    placeholder="Example: Hair Treatment"
                    required
                >

            </div>



            <!-- DESCRIPTION -->

            <div class="form-group">

                <label>
                    Description
                </label>

                <textarea
                    name="description"
                    rows="4"
                    placeholder="Enter service description"
                ></textarea>

            </div>



            <!-- PRICE -->

            <div class="form-group">

                <label>
                    Original Price ($)
                </label>

                <input
                    type="number"
                    name="price"
                    min="0"
                    step="0.01"
                    placeholder="0.00"
                    required
                >

            </div>



            <!-- SERVICE STATUS -->

            <div class="form-group">

                <label>
                    Service Status
                </label>

                <select
                    name="status"
                    required
                >

                    <option value="available">
                        Available
                    </option>

                    <option value="unavailable">
                        Unavailable
                    </option>

                </select>

            </div>



            <!-- DISCOUNT -->

            <div class="form-group">

                <label>
                    Discount (%)
                </label>

                <input
                    type="number"
                    name="discount_percent"
                    min="0"
                    max="100"
                    step="0.01"
                    value="0"
                    required
                >

            </div>



            <!-- PROMOTION STATUS -->

            <div class="form-group">

                <label>
                    Promotion Status
                </label>

                <select
                    name="promotion_status"
                    required
                >

                    <option value="inactive">
                        Inactive
                    </option>

                    <option value="active">
                        Active
                    </option>

                </select>

            </div>



            <button
                type="submit"
                class="button confirm-button"
            >

                Add Service

            </button>


        </form>


    </div>



    <!-- ======================================
         EXISTING SERVICES
    ======================================= -->

    <div
        class="section-title"
        style="margin-top:60px;"
    >

        <h2>
            Existing Services
        </h2>

        <p>
            Edit service information, availability and promotions
        </p>

    </div>



    <div class="booking-details">


        <?php if (
            $services->num_rows > 0
        ): ?>


            <?php while (
                $service =
                $services->fetch_assoc()
            ): ?>


                <?php


                // ==================================
                // CALCULATE FINAL PRICE
                // ==================================

                $final_price =
                    (float)$service["price"];


                if (
                    $service["promotion_status"]
                    === "active" &&
                    (float)$service[
                        "discount_percent"
                    ] > 0
                ) {

                    $final_price =
                        $service["price"]
                        -
                        (
                            $service["price"]
                            *
                            (
                                $service[
                                    "discount_percent"
                                ]
                                / 100
                            )
                        );

                }


                ?>


                <div class="booking-card">


                    <form
                        method="POST"
                        action="admin_services.php"
                    >


                        <input
                            type="hidden"
                            name="action"
                            value="update"
                        >


                        <input
                            type="hidden"
                            name="service_id"
                            value="<?php
                            echo $service["service_id"];
                            ?>"
                        >



                        <!-- SERVICE NAME -->

                        <div class="form-group">

                            <label>
                                Service Name
                            </label>

                            <input
                                type="text"
                                name="service_name"
                                value="<?php
                                echo htmlspecialchars(
                                    $service["service_name"]
                                );
                                ?>"
                                required
                            >

                        </div>



                        <!-- DESCRIPTION -->

                        <div class="form-group">

                            <label>
                                Description
                            </label>

                            <textarea
                                name="description"
                                rows="4"
                            ><?php
                            echo htmlspecialchars(
                                $service["description"] ?? ""
                            );
                            ?></textarea>

                        </div>



                        <!-- ORIGINAL PRICE -->

                        <div class="form-group">

                            <label>
                                Original Price ($)
                            </label>

                            <input
                                type="number"
                                name="price"
                                min="0"
                                step="0.01"
                                value="<?php
                                echo htmlspecialchars(
                                    $service["price"]
                                );
                                ?>"
                                required
                            >

                        </div>



                        <!-- SERVICE STATUS -->

                        <div class="form-group">

                            <label>
                                Service Status
                            </label>


                            <select
                                name="status"
                                required
                            >


                                <option
                                    value="available"

                                    <?php

                                    if (
                                        $service["status"]
                                        === "available"
                                    ) {

                                        echo "selected";

                                    }

                                    ?>
                                >

                                    Available

                                </option>


                                <option
                                    value="unavailable"

                                    <?php

                                    if (
                                        $service["status"]
                                        === "unavailable"
                                    ) {

                                        echo "selected";

                                    }

                                    ?>
                                >

                                    Unavailable

                                </option>


                            </select>

                        </div>



                        <!-- DISCOUNT -->

                        <div class="form-group">

                            <label>
                                Discount (%)
                            </label>

                            <input
                                type="number"
                                name="discount_percent"
                                min="0"
                                max="100"
                                step="0.01"
                                value="<?php
                                echo htmlspecialchars(
                                    $service[
                                        "discount_percent"
                                    ]
                                );
                                ?>"
                                required
                            >

                        </div>



                        <!-- PROMOTION STATUS -->

                        <div class="form-group">

                            <label>
                                Promotion Status
                            </label>

                            <select
                                name="promotion_status"
                                required
                            >


                                <option
                                    value="inactive"

                                    <?php

                                    if (
                                        $service[
                                            "promotion_status"
                                        ]
                                        === "inactive"
                                    ) {

                                        echo "selected";

                                    }

                                    ?>
                                >

                                    Inactive

                                </option>


                                <option
                                    value="active"

                                    <?php

                                    if (
                                        $service[
                                            "promotion_status"
                                        ]
                                        === "active"
                                    ) {

                                        echo "selected";

                                    }

                                    ?>
                                >

                                    Active

                                </option>


                            </select>

                        </div>



                        <!-- PROMOTION PREVIEW -->

                        <div
                            style="
                                margin-top:20px;
                                margin-bottom:20px;
                            "
                        >


                            <p>

                                <strong>
                                    Promotion:
                                </strong>

                                <?php

                                if (
                                    $service[
                                        "promotion_status"
                                    ]
                                    === "active" &&
                                    $service[
                                        "discount_percent"
                                    ] > 0
                                ) {

                                    echo htmlspecialchars(
                                        $service[
                                            "discount_percent"
                                        ]
                                    );

                                    echo "% OFF";

                                } else {

                                    echo "No active promotion";

                                }

                                ?>

                            </p>



                            <p>

                                <strong>
                                    Final Price:
                                </strong>

                                $<?php

                                echo number_format(
                                    $final_price,
                                    2
                                );

                                ?>

                            </p>


                        </div>



                        <button
                            type="submit"
                            class="button"
                        >

                            Save Changes

                        </button>


                    </form>


                </div>


            <?php endwhile; ?>


        <?php else: ?>


            <p style="
                text-align:center;
                width:100%;
            ">

                No services available.

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