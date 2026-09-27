<?php

session_start();
include "db.php";


// ==========================================
// STAFF SAHAJA
// ==========================================

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "staff"
) {

    header("Location: login.php");
    exit();

}


$staff_id = $_SESSION["user_id"];
$message = "";


// ==========================================
// PROCESS FORM
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";


    // ======================================
    // ADD AVAILABILITY
    // ======================================

    if ($action === "add") {

        $available_date =
            $_POST["available_date"] ?? "";

        $start_time =
            $_POST["start_time"] ?? "";

        $end_time =
            $_POST["end_time"] ?? "";


        if (
            empty($available_date) ||
            empty($start_time) ||
            empty($end_time)
        ) {

            $message =
                "Please complete all availability information.";

        } elseif (
            $available_date < date("Y-m-d")
        ) {

            $message =
                "Please select today or a future date.";

        } elseif (
            $start_time >= $end_time
        ) {

            $message =
                "End time must be later than start time.";

        } else {


            // ==================================
            // INSERT AVAILABILITY
            // ==================================

            $stmt = $conn->prepare(
                "INSERT INTO staff_availability
                (
                    staff_id,
                    available_date,
                    start_time,
                    end_time,
                    status
                )
                VALUES (?, ?, ?, ?, 'available')"
            );


            $stmt->bind_param(
                "isss",
                $staff_id,
                $available_date,
                $start_time,
                $end_time
            );


            if ($stmt->execute()) {

                $stmt->close();

                header(
                    "Location: staff_availability.php?added=1"
                );

                exit();

            } else {

                $message =
                    "Unable to save availability.";

            }


            $stmt->close();

        }

    }


    // ======================================
    // UPDATE AVAILABILITY
    // ======================================

    elseif ($action === "update") {

        $availability_id =
            $_POST["availability_id"] ?? "";

        $available_date =
            $_POST["available_date"] ?? "";

        $start_time =
            $_POST["start_time"] ?? "";

        $end_time =
            $_POST["end_time"] ?? "";

        $status =
            $_POST["status"] ?? "";


        $allowed_status = [
            "available",
            "unavailable"
        ];


        if (
            !is_numeric($availability_id) ||
            empty($available_date) ||
            empty($start_time) ||
            empty($end_time) ||
            !in_array(
                $status,
                $allowed_status,
                true
            )
        ) {

            $message =
                "Please complete all availability information.";

        } elseif (
            $available_date < date("Y-m-d")
        ) {

            $message =
                "Please select today or a future date.";

        } elseif (
            $start_time >= $end_time
        ) {

            $message =
                "End time must be later than start time.";

        } else {


            // ==================================
            // UPDATE HANYA MILIK STAFF SENDIRI
            // ==================================

            $stmt = $conn->prepare(
                "UPDATE staff_availability

                 SET
                    available_date = ?,
                    start_time = ?,
                    end_time = ?,
                    status = ?

                 WHERE availability_id = ?
                 AND staff_id = ?"
            );


            $stmt->bind_param(
                "ssssii",
                $available_date,
                $start_time,
                $end_time,
                $status,
                $availability_id,
                $staff_id
            );


            if ($stmt->execute()) {

                $stmt->close();

                header(
                    "Location: staff_availability.php?updated=1"
                );

                exit();

            } else {

                $message =
                    "Unable to update availability.";

            }


            $stmt->close();

        }

    }


    // ======================================
    // DELETE AVAILABILITY
    // ======================================

    elseif ($action === "delete") {

        $availability_id =
            $_POST["availability_id"] ?? "";


        if (!is_numeric($availability_id)) {

            $message =
                "Invalid availability.";

        } else {


            // ==================================
            // DELETE HANYA MILIK STAFF SENDIRI
            // ==================================

            $stmt = $conn->prepare(
                "DELETE FROM staff_availability

                 WHERE availability_id = ?
                 AND staff_id = ?"
            );


            $stmt->bind_param(
                "ii",
                $availability_id,
                $staff_id
            );


            if ($stmt->execute()) {

                $stmt->close();

                header(
                    "Location: staff_availability.php?deleted=1"
                );

                exit();

            } else {

                $message =
                    "Unable to delete availability.";

            }


            $stmt->close();

        }

    }

}


// ==========================================
// AMBIL AVAILABILITY STAFF
// ==========================================

$stmt = $conn->prepare(
    "SELECT
        availability_id,
        available_date,
        start_time,
        end_time,
        status

     FROM staff_availability

     WHERE staff_id = ?

     ORDER BY
        available_date ASC,
        start_time ASC"
);


$stmt->bind_param(
    "i",
    $staff_id
);


$stmt->execute();

$result = $stmt->get_result();

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
        Staff Availability - MySalon
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
            MySalon Staff
        </div>


        <!-- DROPDOWN -->

        <div
            class="mysalon-dropdown"
            id="mysalonDropdown"
        >

            <a href="staff_dashboard.php">
                Dashboard
            </a>

            <a href="staff_availability.php">
                Availability
            </a>

            <a href="staff_attendance.php">
                Attendance
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
     AVAILABILITY SECTION
=========================================== -->

<section>


    <div class="section-title">

        <h2>
            Staff Availability
        </h2>

        <p>
            Set and manage your available working hours
        </p>

    </div>



    <!-- ======================================
         SUCCESS MESSAGES
    ======================================= -->

    <?php if (isset($_GET["added"])): ?>

        <p style="
            text-align:center;
            margin-bottom:25px;
        ">
            Availability added successfully!
        </p>

    <?php endif; ?>


    <?php if (isset($_GET["updated"])): ?>

        <p style="
            text-align:center;
            margin-bottom:25px;
        ">
            Availability updated successfully!
        </p>

    <?php endif; ?>


    <?php if (isset($_GET["deleted"])): ?>

        <p style="
            text-align:center;
            margin-bottom:25px;
        ">
            Availability deleted successfully!
        </p>

    <?php endif; ?>



    <!-- ======================================
         ERROR MESSAGE
    ======================================= -->

    <?php if (!empty($message)): ?>

        <p style="
            text-align:center;
            margin-bottom:25px;
            color:#9c4444;
        ">

            <?php
            echo htmlspecialchars($message);
            ?>

        </p>

    <?php endif; ?>



    <!-- ======================================
         ADD AVAILABILITY
    ======================================= -->

    <div class="booking-form">


        <h3 style="margin-bottom:25px;">
            Add Availability
        </h3>


        <form
            method="POST"
            action="staff_availability.php"
        >


            <input
                type="hidden"
                name="action"
                value="add"
            >


            <!-- DATE -->

            <div class="form-group">

                <label for="available_date">
                    Available Date
                </label>

                <input
                    type="date"
                    id="available_date"
                    name="available_date"
                    min="<?php echo date("Y-m-d"); ?>"
                    required
                >

            </div>



            <!-- START TIME -->

            <div class="form-group">

                <label for="start_time">
                    Start Time
                </label>

                <input
                    type="time"
                    id="start_time"
                    name="start_time"
                    required
                >

            </div>



            <!-- END TIME -->

            <div class="form-group">

                <label for="end_time">
                    End Time
                </label>

                <input
                    type="time"
                    id="end_time"
                    name="end_time"
                    required
                >

            </div>



            <button
                type="submit"
                class="button confirm-button"
            >
                Add Availability
            </button>


        </form>


    </div>



    <!-- ======================================
         MY AVAILABILITY
    ======================================= -->

    <div
        class="section-title"
        style="margin-top:60px;"
    >

        <h2>
            My Availability
        </h2>

        <p>
            Update or remove your working schedule
        </p>

    </div>



    <div class="booking-details">


        <?php if ($result->num_rows > 0): ?>


            <?php while (
                $availability =
                $result->fetch_assoc()
            ): ?>


                <div class="booking-card">


                    <form
                        method="POST"
                        action="staff_availability.php"
                    >


                        <input
                            type="hidden"
                            name="action"
                            value="update"
                        >


                        <input
                            type="hidden"
                            name="availability_id"
                            value="<?php
                            echo $availability["availability_id"];
                            ?>"
                        >



                        <!-- DATE -->

                        <div class="form-group">

                            <label>
                                Available Date
                            </label>

                            <input
                                type="date"
                                name="available_date"
                                min="<?php echo date("Y-m-d"); ?>"
                                value="<?php
                                echo htmlspecialchars(
                                    $availability["available_date"]
                                );
                                ?>"
                                required
                            >

                        </div>



                        <!-- START TIME -->

                        <div class="form-group">

                            <label>
                                Start Time
                            </label>

                            <input
                                type="time"
                                name="start_time"
                                value="<?php
                                echo htmlspecialchars(
                                    substr(
                                        $availability["start_time"],
                                        0,
                                        5
                                    )
                                );
                                ?>"
                                required
                            >

                        </div>



                        <!-- END TIME -->

                        <div class="form-group">

                            <label>
                                End Time
                            </label>

                            <input
                                type="time"
                                name="end_time"
                                value="<?php
                                echo htmlspecialchars(
                                    substr(
                                        $availability["end_time"],
                                        0,
                                        5
                                    )
                                );
                                ?>"
                                required
                            >

                        </div>



                        <!-- STATUS -->

                        <div class="form-group">

                            <label>
                                Status
                            </label>

                            <select
                                name="status"
                                required
                            >


                                <option
                                    value="available"

                                    <?php

                                    if (
                                        $availability["status"]
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
                                        $availability["status"]
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



                        <!-- UPDATE -->

                        <button
                            type="submit"
                            class="button"
                        >
                            Save Changes
                        </button>


                    </form>



                    <!-- DELETE -->

                    <form
                        method="POST"
                        action="staff_availability.php"
                        style="margin-top:10px;"
                        onsubmit="return confirm(
                            'Delete this availability?'
                        );"
                    >


                        <input
                            type="hidden"
                            name="action"
                            value="delete"
                        >


                        <input
                            type="hidden"
                            name="availability_id"
                            value="<?php
                            echo $availability["availability_id"];
                            ?>"
                        >


                        <button
                            type="submit"
                            class="button delete-button"
                        >
                            Delete
                        </button>


                    </form>


                </div>


            <?php endwhile; ?>


        <?php else: ?>


            <p style="
                text-align:center;
                width:100%;
            ">

                No availability has been added yet.

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

        if (event.key === "Escape") {

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