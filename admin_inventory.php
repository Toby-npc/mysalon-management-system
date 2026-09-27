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
// ADD / UPDATE INVENTORY
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";


    // ======================================
    // ADD ITEM
    // ======================================

    if ($action === "add") {

        $item_name =
            trim($_POST["item_name"] ?? "");

        $quantity =
            $_POST["quantity"] ?? "";

        $unit =
            trim($_POST["unit"] ?? "");

        $minimum_stock =
            $_POST["minimum_stock"] ?? "";


        // ==================================
        // VALIDATION
        // ==================================

        if (
            empty($item_name) ||
            $quantity === "" ||
            empty($unit) ||
            $minimum_stock === ""
        ) {

            $message =
                "Please complete all inventory information.";

        } elseif (
            !is_numeric($quantity) ||
            $quantity < 0
        ) {

            $message =
                "Quantity must be 0 or higher.";

        } elseif (
            !is_numeric($minimum_stock) ||
            $minimum_stock < 0
        ) {

            $message =
                "Minimum stock must be 0 or higher.";

        } else {


            // ==================================
            // INSERT ITEM
            // ==================================

            $stmt =
                $conn->prepare(
                    "INSERT INTO inventory
                    (
                        item_name,
                        quantity,
                        unit,
                        minimum_stock
                    )
                    VALUES (?, ?, ?, ?)"
                );


            $stmt->bind_param(
                "sisi",
                $item_name,
                $quantity,
                $unit,
                $minimum_stock
            );


            if ($stmt->execute()) {

                $stmt->close();

                header(
                    "Location: admin_inventory.php?added=1"
                );

                exit();

            } else {

                $message =
                    "Unable to add inventory item.";

            }


            $stmt->close();

        }

    }


    // ======================================
    // UPDATE ITEM
    // ======================================

    elseif ($action === "update") {

        $item_id =
            $_POST["item_id"] ?? "";

        $item_name =
            trim($_POST["item_name"] ?? "");

        $quantity =
            $_POST["quantity"] ?? "";

        $unit =
            trim($_POST["unit"] ?? "");

        $minimum_stock =
            $_POST["minimum_stock"] ?? "";


        // ==================================
        // VALIDATION
        // ==================================

        if (
            !is_numeric($item_id) ||
            empty($item_name) ||
            $quantity === "" ||
            empty($unit) ||
            $minimum_stock === ""
        ) {

            $message =
                "Please complete all inventory information.";

        } elseif (
            !is_numeric($quantity) ||
            $quantity < 0
        ) {

            $message =
                "Quantity must be 0 or higher.";

        } elseif (
            !is_numeric($minimum_stock) ||
            $minimum_stock < 0
        ) {

            $message =
                "Minimum stock must be 0 or higher.";

        } else {


            // ==================================
            // UPDATE DATABASE
            // ==================================

            $stmt =
                $conn->prepare(
                    "UPDATE inventory

                     SET
                        item_name = ?,
                        quantity = ?,
                        unit = ?,
                        minimum_stock = ?

                     WHERE item_id = ?"
                );


            $stmt->bind_param(
                "sisii",
                $item_name,
                $quantity,
                $unit,
                $minimum_stock,
                $item_id
            );


            if ($stmt->execute()) {

                $stmt->close();

                header(
                    "Location: admin_inventory.php?updated=1"
                );

                exit();

            } else {

                $message =
                    "Unable to update inventory item.";

            }


            $stmt->close();

        }

    }

}


// ==========================================
// GET INVENTORY
// ==========================================

$inventory =
    $conn->query(
        "SELECT
            item_id,
            item_name,
            quantity,
            unit,
            minimum_stock,
            created_at

         FROM inventory

         ORDER BY item_name ASC"
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
        Inventory - MySalon
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
     INVENTORY
=========================================== -->

<section>


    <div class="section-title">

        <h2>
            Inventory Management
        </h2>

        <p>
            Manage salon products and stock levels
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

            Inventory item added successfully!

        </p>

    <?php endif; ?>


    <?php if (
        isset($_GET["updated"])
    ): ?>

        <p style="
            text-align:center;
            margin-bottom:25px;
        ">

            Inventory item updated successfully!

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
         ADD NEW ITEM
    ======================================= -->

    <div class="booking-form">


        <h3 style="margin-bottom:25px;">
            Add Inventory Item
        </h3>


        <form
            method="POST"
            action="admin_inventory.php"
        >


            <input
                type="hidden"
                name="action"
                value="add"
            >



            <!-- ITEM NAME -->

            <div class="form-group">

                <label>
                    Item Name
                </label>

                <input
                    type="text"
                    name="item_name"
                    placeholder="Example: Shampoo"
                    required
                >

            </div>



            <!-- QUANTITY -->

            <div class="form-group">

                <label>
                    Quantity
                </label>

                <input
                    type="number"
                    name="quantity"
                    min="0"
                    value="0"
                    required
                >

            </div>



            <!-- UNIT -->

            <div class="form-group">

                <label>
                    Unit
                </label>

                <input
                    type="text"
                    name="unit"
                    placeholder="Example: bottles"
                    required
                >

            </div>



            <!-- MINIMUM STOCK -->

            <div class="form-group">

                <label>
                    Minimum Stock
                </label>

                <input
                    type="number"
                    name="minimum_stock"
                    min="0"
                    value="5"
                    required
                >

            </div>



            <button
                type="submit"
                class="button confirm-button"
            >

                Add Item

            </button>


        </form>


    </div>



    <!-- ======================================
         INVENTORY LIST
    ======================================= -->

    <div
        class="section-title"
        style="margin-top:60px;"
    >

        <h2>
            Current Inventory
        </h2>

        <p>
            View and update current salon stock
        </p>

    </div>



    <div class="booking-details">


        <?php if (
            $inventory->num_rows > 0
        ): ?>


            <?php while (
                $item =
                $inventory->fetch_assoc()
            ): ?>


                <?php


                // ==================================
                // CHECK LOW STOCK
                // ==================================

                $isLowStock =
                    $item["quantity"]
                    <=
                    $item["minimum_stock"];


                ?>


                <div class="booking-card">


                    <form
                        method="POST"
                        action="admin_inventory.php"
                    >


                        <input
                            type="hidden"
                            name="action"
                            value="update"
                        >


                        <input
                            type="hidden"
                            name="item_id"
                            value="<?php
                            echo $item["item_id"];
                            ?>"
                        >



                        <!-- STOCK STATUS -->

                        <?php if (
                            $isLowStock
                        ): ?>


                            <p style="
                                color:#9c4444;
                                font-weight:bold;
                                margin-bottom:15px;
                            ">

                                ⚠ Low Stock

                            </p>


                        <?php else: ?>


                            <p style="
                                margin-bottom:15px;
                            ">

                                Stock Available

                            </p>


                        <?php endif; ?>



                        <!-- ITEM NAME -->

                        <div class="form-group">

                            <label>
                                Item Name
                            </label>

                            <input
                                type="text"
                                name="item_name"
                                value="<?php
                                echo htmlspecialchars(
                                    $item["item_name"]
                                );
                                ?>"
                                required
                            >

                        </div>



                        <!-- QUANTITY -->

                        <div class="form-group">

                            <label>
                                Quantity
                            </label>

                            <input
                                type="number"
                                name="quantity"
                                min="0"
                                value="<?php
                                echo $item["quantity"];
                                ?>"
                                required
                            >

                        </div>



                        <!-- UNIT -->

                        <div class="form-group">

                            <label>
                                Unit
                            </label>

                            <input
                                type="text"
                                name="unit"
                                value="<?php
                                echo htmlspecialchars(
                                    $item["unit"]
                                );
                                ?>"
                                required
                            >

                        </div>



                        <!-- MINIMUM STOCK -->

                        <div class="form-group">

                            <label>
                                Minimum Stock
                            </label>

                            <input
                                type="number"
                                name="minimum_stock"
                                min="0"
                                value="<?php
                                echo $item[
                                    "minimum_stock"
                                ];
                                ?>"
                                required
                            >

                        </div>



                        <button
                            type="submit"
                            class="button"
                        >

                            Update Stock

                        </button>


                    </form>


                </div>


            <?php endwhile; ?>


        <?php else: ?>


            <p style="
                text-align:center;
                width:100%;
            ">

                No inventory items available.

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