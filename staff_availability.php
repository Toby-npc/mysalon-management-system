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

        $available_date = $_POST["available_date"] ?? "";
        $start_time = $_POST["start_time"] ?? "";
        $end_time = $_POST["end_time"] ?? "";

        if (
            empty($available_date) ||
            empty($start_time) ||
            empty($end_time)
        ) {

            $message = "Please complete all availability information.";

        } elseif ($available_date < date("Y-m-d")) {

            $message = "Please select today or a future date.";

        } elseif ($start_time >= $end_time) {

            $message = "End time must be later than start time.";

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

                header("Location: staff_availability.php?added=1");
                exit();

            } else {

                $message = "Unable to save availability.";
                $stmt->close();

            }

        }

    }


    // ======================================
    // UPDATE AVAILABILITY
    // ======================================

    elseif ($action === "update") {

        $availability_id = $_POST["availability_id"] ?? "";
        $available_date = $_POST["available_date"] ?? "";
        $start_time = $_POST["start_time"] ?? "";
        $end_time = $_POST["end_time"] ?? "";
        $status = $_POST["status"] ?? "";

        $allowed_status = [
            "available",
            "unavailable"
        ];

        if (
            !is_numeric($availability_id) ||
            empty($available_date) ||
            empty($start_time) ||
            empty($end_time) ||
            !in_array($status, $allowed_status, true)
        ) {

            $message = "Please complete all availability information.";

        } elseif ($available_date < date("Y-m-d")) {

            $message = "Please select today or a future date.";

        } elseif ($start_time >= $end_time) {

            $message = "End time must be later than start time.";

        } else {

            // ==================================
            // UPDATE HANYA MILIK STAFF SENDIRI
            // ==================================

            $availability_id = (int)$availability_id;

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

                header("Location: staff_availability.php?updated=1");
                exit();

            } else {

                $message = "Unable to update availability.";
                $stmt->close();

            }

        }

    }


    // ======================================
    // DELETE AVAILABILITY
    // ======================================

    elseif ($action === "delete") {

        $availability_id = $_POST["availability_id"] ?? "";

        if (!is_numeric($availability_id)) {

            $message = "Invalid availability.";

        } else {

            // ==================================
            // DELETE HANYA MILIK STAFF SENDIRI
            // ==================================

            $availability_id = (int)$availability_id;

            $stmt = $conn->prepare(
                "DELETE FROM staff_availability

                 WHERE availability_id = ?
                 AND staff_id = ?"
            );

            $stmt->bind_param("ii", $availability_id, $staff_id);

            if ($stmt->execute()) {

                $stmt->close();

                header("Location: staff_availability.php?deleted=1");
                exit();

            } else {

                $message = "Unable to delete availability.";
                $stmt->close();

            }

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

$stmt->bind_param("i", $staff_id);
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

    <!-- FONTS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,400;0,500;0,600;1,500&family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="MySalon(CSS).css?v=2"
    >

    <!-- Same page styles as staff_dashboard.php so every staff page matches -->
    <style>

        .section-title p {
            font-size: 16px;
            font-weight: 500;
        }

        .notice {
            font-size: 15px;
            font-weight: 500;
            color: var(--cream);
            background: rgba(35, 58, 52, 0.88);
            border: 1px solid var(--line-dark);
            border-left: 4px solid var(--sage);
            border-radius: 6px;
            padding: 14px 18px;
            margin-bottom: 25px;
            text-align: center;
            width: 100%;
            backdrop-filter: blur(6px);
        }

        .notice.notice-error {
            color: #e8a3a3;
            border-left-color: var(--danger);
        }

        .booking-card {
            margin-bottom: 24px;
        }

        .booking-card h3 {
            font-size: 24px;
        }

        .booking-card p {
            font-size: 15.5px;
        }

        .booking-card p strong {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 12px;
            font-weight: 500;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-right: 8px;
        }

        .booking-card select {
            font-size: 15px;
            padding: 14px 16px;
        }

        .booking-card select:focus {
            box-shadow: 0 0 0 3px rgba(191, 122, 74, 0.25);
        }

        .booking-card form .button {
            margin-top: 4px;
        }

        .booking-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
        }

        .booking-actions .button {
            padding: 12px 24px;
            font-size: 12.5px;
        }

    </style>

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
                src="asset/Salon3.png"
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

                <?php echo htmlspecialchars($_SESSION["name"]); ?>

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

                <?php echo htmlspecialchars($_SESSION["name"]); ?>

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

        <p class="notice" style="margin-top:30px;">
            Availability added successfully!
        </p>

    <?php endif; ?>


    <?php if (isset($_GET["updated"])): ?>

        <p class="notice" style="margin-top:30px;">
            Availability updated successfully!
        </p>

    <?php endif; ?>


    <?php if (isset($_GET["deleted"])): ?>

        <p class="notice" style="margin-top:30px;">
            Availability deleted successfully!
        </p>

    <?php endif; ?>



    <!-- ======================================
         ERROR MESSAGE
    ======================================= -->

    <?php if (!empty($message)): ?>

        <p class="notice notice-error" style="margin-top:30px;">
            <?php echo htmlspecialchars($message); ?>
        </p>

    <?php endif; ?>



    <!-- ======================================
         ADD AVAILABILITY
    ======================================= -->

    <div class="booking-form" style="margin-top:30px;">


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
        style="margin-top:70px;"
    >

        <h2>
            My Availability
        </h2>

        <p>
            Update or remove your working schedule
        </p>

    </div>


    <div class="booking-details" style="margin-top:30px;">


        <?php if ($result->num_rows > 0): ?>


            <?php while ($availability = $result->fetch_assoc()): ?>


                <div class="booking-card">


                    <!-- UPDATE FORM -->

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
                            value="<?php echo (int)$availability["availability_id"]; ?>"
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
                                value="<?php echo htmlspecialchars($availability["available_date"]); ?>"
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
                                value="<?php echo htmlspecialchars(substr($availability["start_time"], 0, 5)); ?>"
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
                                value="<?php echo htmlspecialchars(substr($availability["end_time"], 0, 5)); ?>"
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
                                    <?php if ($availability["status"] === "available") { echo "selected"; } ?>
                                >
                                    Available
                                </option>

                                <option
                                    value="unavailable"
                                    <?php if ($availability["status"] === "unavailable") { echo "selected"; } ?>
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
                        onsubmit="return confirm('Delete this availability?');"
                    >

                        <input
                            type="hidden"
                            name="action"
                            value="delete"
                        >

                        <input
                            type="hidden"
                            name="availability_id"
                            value="<?php echo (int)$availability["availability_id"]; ?>"
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


            <p class="notice">
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

const logoTrigger = document.getElementById("mysalonLogoTrigger");
const dropdown = document.getElementById("mysalonDropdown");


function toggleDropdown() {

    dropdown.classList.toggle("mysalon-dropdown-open");

    const isOpen = dropdown.classList.contains("mysalon-dropdown-open");

    logoTrigger.setAttribute("aria-expanded", isOpen ? "true" : "false");

}


function closeDropdown() {

    dropdown.classList.remove("mysalon-dropdown-open");

    logoTrigger.setAttribute("aria-expanded", "false");

}


logoTrigger.addEventListener("click", function (event) {

    event.stopPropagation();
    toggleDropdown();

});


logoTrigger.addEventListener("keydown", function (event) {

    if (event.key === "Enter" || event.key === " ") {

        event.preventDefault();
        event.stopPropagation();
        toggleDropdown();

    }

});


dropdown.addEventListener("click", function (event) {

    event.stopPropagation();

});


document.addEventListener("click", function () {

    closeDropdown();

});


document.addEventListener("keydown", function (event) {

    if (event.key === "Escape") {

        closeDropdown();

    }

});

</script>


</body>

</html>


<?php

$stmt->close();
$conn->close();

?>
