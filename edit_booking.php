<?php

session_start();
include "db.php";


// ==========================================
// CUSTOMER MESTI LOGIN
// ==========================================

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "customer"
) {
    header("Location: login.php");
    exit();
}


$customer_id = $_SESSION["user_id"];


// ==========================================
// PASTIKAN BOOKING ID ADA
// ==========================================

if (
    !isset($_GET["id"]) ||
    !is_numeric($_GET["id"])
) {
    header("Location: MySalon(MYBOOKING).php");
    exit();
}


$booking_id = (int) $_GET["id"];
$message = "";


// ==========================================
// AMBIL BOOKING
// ==========================================

$stmt = $conn->prepare(
    "SELECT
        booking_id,
        service_id,
        staff_id,
        booking_date,
        booking_time,
        status

     FROM bookings

     WHERE booking_id = ?
     AND customer_id = ?"
);


$stmt->bind_param(
    "ii",
    $booking_id,
    $customer_id
);


$stmt->execute();

$result = $stmt->get_result();


// Booking bukan milik customer / tak wujud

if ($result->num_rows === 0) {

    $stmt->close();
    $conn->close();

    header("Location: MySalon(MYBOOKING).php");
    exit();
}


$booking = $result->fetch_assoc();

$stmt->close();


// ==========================================
// CANCELLED / COMPLETED TAK BOLEH EDIT
// ==========================================

if (
    $booking["status"] === "cancelled" ||
    $booking["status"] === "completed"
) {

    $conn->close();

    header(
        "Location: MySalon(MYBOOKING).php?edit_error=locked"
    );

    exit();
}


// ==========================================
// UPDATE BOOKING
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $service_id =
        $_POST["service_id"] ?? "";

    $booking_date =
        $_POST["booking_date"] ?? "";

    $booking_time =
        $_POST["booking_time"] ?? "";


    // ======================================
    // VALIDATION ASAS
    // ======================================

    if (
        empty($service_id) ||
        empty($booking_date) ||
        empty($booking_time)
    ) {

        $message =
            "Please complete all booking information.";

    } elseif (!is_numeric($service_id)) {

        $message =
            "Invalid service selected.";

    } elseif ($booking_date < date("Y-m-d")) {

        $message =
            "Please select today or a future date.";

    } else {

        $service_id =
            (int) $service_id;


        // ==================================
        // SEMAK SERVICE
        // ==================================

        $checkService =
            $conn->prepare(
                "SELECT service_id

                 FROM services

                 WHERE service_id = ?
                 AND status = 'available'"
            );


        $checkService->bind_param(
            "i",
            $service_id
        );


        $checkService->execute();

        $serviceResult =
            $checkService->get_result();


        if ($serviceResult->num_rows === 0) {

            $message =
                "Selected service is not available.";

            $checkService->close();

        } else {

            $checkService->close();


            // ==================================
            // KIRA STAFF AVAILABLE
            // ==================================

            $availableStmt =
                $conn->prepare(
                    "SELECT COUNT(DISTINCT sa.staff_id)
                     AS total_staff

                     FROM staff_availability AS sa

                     INNER JOIN users AS u
                        ON sa.staff_id = u.user_id

                     WHERE u.role = 'staff'

                     AND sa.available_date = ?

                     AND sa.status = 'available'

                     AND sa.start_time <= ?

                     AND sa.end_time >= ?"
                );


            $availableStmt->bind_param(
                "sss",
                $booking_date,
                $booking_time,
                $booking_time
            );


            $availableStmt->execute();

            $availableResult =
                $availableStmt->get_result();

            $availableData =
                $availableResult->fetch_assoc();

            $totalStaff =
                (int) $availableData["total_staff"];

            $availableStmt->close();


            // ==================================
            // KIRA BOOKING PADA SLOT SAMA
            //
            // Booking semasa dikecualikan supaya
            // customer boleh simpan slot asal.
            // ==================================

            $bookingCountStmt =
                $conn->prepare(
                    "SELECT COUNT(*) AS total_bookings

                     FROM bookings

                     WHERE booking_date = ?

                     AND booking_time = ?

                     AND status != 'cancelled'

                     AND booking_id != ?"
                );


            $bookingCountStmt->bind_param(
                "ssi",
                $booking_date,
                $booking_time,
                $booking_id
            );


            $bookingCountStmt->execute();

            $bookingCountResult =
                $bookingCountStmt->get_result();

            $bookingCountData =
                $bookingCountResult->fetch_assoc();

            $totalBookings =
                (int)
                $bookingCountData["total_bookings"];

            $bookingCountStmt->close();


            // ==================================
            // SLOT TAK AVAILABLE
            // ==================================

            if (
                $totalStaff === 0 ||
                $totalBookings >= $totalStaff
            ) {

                $message =
                    "Selected time slot is no longer available.";

            } else {

                // ==================================
                // CHECK ADA PERUBAHAN SLOT/SERVICE
                // ==================================

                $bookingChanged =
                    (
                        (int)$booking["service_id"]
                        !==
                        $service_id
                    )
                    ||
                    (
                        $booking["booking_date"]
                        !==
                        $booking_date
                    )
                    ||
                    (
                        $booking["booking_time"]
                        !==
                        $booking_time
                    );


                // ==================================
                // UPDATE
                //
                // Jika booking berubah,
                // staff assignment lama dibuang.
                // Admin assign semula kemudian.
                // ==================================

                if ($bookingChanged) {

                    $update =
                        $conn->prepare(
                            "UPDATE bookings

                             SET
                                service_id = ?,
                                booking_date = ?,
                                booking_time = ?,
                                staff_id = NULL,
                                status = 'pending'

                             WHERE booking_id = ?
                             AND customer_id = ?"
                        );


                    $update->bind_param(
                        "issii",
                        $service_id,
                        $booking_date,
                        $booking_time,
                        $booking_id,
                        $customer_id
                    );

                } else {

                    $update =
                        $conn->prepare(
                            "UPDATE bookings

                             SET
                                service_id = ?,
                                booking_date = ?,
                                booking_time = ?

                             WHERE booking_id = ?
                             AND customer_id = ?"
                        );


                    $update->bind_param(
                        "issii",
                        $service_id,
                        $booking_date,
                        $booking_time,
                        $booking_id,
                        $customer_id
                    );

                }


                if ($update->execute()) {

                    $update->close();
                    $conn->close();

                    header(
                        "Location: MySalon(MYBOOKING).php?updated=1"
                    );

                    exit();

                } else {

                    $message =
                        "Unable to update appointment. Please try again.";

                    $update->close();

                }

            }

        }

    }

}


// ==========================================
// AMBIL AVAILABLE SERVICES
// ==========================================

$services =
    $conn->query(
        "SELECT
            service_id,
            service_name,
            price

         FROM services

         WHERE status = 'available'

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
        Edit Appointment - MySalon
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

    <div class="logo">
        MySalon
    </div>


    <ul>

        <li>
            <a href="MySalon(HOME).php">
                Home
            </a>
        </li>


        <li>
            <a href="MySalon(SERVICES).php">
                Services
            </a>
        </li>


        <li>
            <a href="MySalon(BOOKING).php">
                Booking
            </a>
        </li>


        <li>

            <a
                href="MySalon(MYBOOKING).php"
                class="active"
            >
                My Booking
            </a>

        </li>


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
     EDIT APPOINTMENT
=========================================== -->

<section>


    <div class="section-title">

        <h2>
            Edit Appointment
        </h2>

        <p>
            Update your service, date or preferred time
        </p>

    </div>



    <div class="booking-form">


        <!-- ERROR -->

        <?php if (!empty($message)): ?>

            <p style="
                text-align:center;
                margin-bottom:20px;
                color:#9c4444;
            ">

                <?php

                echo htmlspecialchars(
                    $message
                );

                ?>

            </p>

        <?php endif; ?>



        <form
            method="POST"
            action="edit_booking.php?id=<?php
            echo $booking_id;
            ?>"
            id="editBookingForm"
        >


            <!-- ==============================
                 SERVICE
            =============================== -->

            <div class="form-group">


                <label for="service_id">
                    Select Service
                </label>


                <select
                    id="service_id"
                    name="service_id"
                    required
                >


                    <?php while (
                        $service =
                        $services->fetch_assoc()
                    ): ?>


                        <option

                            value="<?php
                            echo $service["service_id"];
                            ?>"

                            <?php

                            if (
                                $service["service_id"]
                                ==
                                $booking["service_id"]
                            ) {

                                echo "selected";

                            }

                            ?>

                        >

                            <?php

                            echo htmlspecialchars(
                                $service["service_name"]
                            );

                            ?>

                            -

                            $<?php

                            echo number_format(
                                $service["price"],
                                2
                            );

                            ?>

                        </option>


                    <?php endwhile; ?>


                </select>


            </div>



            <!-- ==============================
                 DATE
            =============================== -->

            <div class="form-group">


                <label for="booking_date">
                    Preferred Date
                </label>


                <input
                    type="date"
                    id="booking_date"
                    name="booking_date"

                    value="<?php
                    echo htmlspecialchars(
                        $booking["booking_date"]
                    );
                    ?>"

                    min="<?php
                    echo date("Y-m-d");
                    ?>"

                    required
                >


            </div>



            <!-- ==============================
                 TIME
            =============================== -->

            <div class="form-group">


                <label>
                    Available Time Slot
                </label>


                <div
                    class="time-slots"
                    id="timeSlots"
                >

                    <p id="slotMessage">
                        Loading available slots...
                    </p>

                </div>


                <input
                    type="hidden"
                    id="booking_time"
                    name="booking_time"

                    value="<?php
                    echo htmlspecialchars(
                        $booking["booking_time"]
                    );
                    ?>"
                >


            </div>



            <!-- ==============================
                 SAVE
            =============================== -->

            <button
                type="submit"
                class="button confirm-button"
            >
                Save Changes
            </button>


            <br><br>


            <a
                href="MySalon(MYBOOKING).php"
                class="button"
            >
                Back to My Bookings
            </a>


        </form>


    </div>


</section>



<footer>

    <p>
        &copy; 2026 MySalon Management System.
        All rights reserved.
    </p>

</footer>



<!-- ==========================================
     DYNAMIC AVAILABLE SLOT
=========================================== -->

<script>

const bookingDate =
    document.getElementById(
        "booking_date"
    );

const bookingTime =
    document.getElementById(
        "booking_time"
    );

const timeSlots =
    document.getElementById(
        "timeSlots"
    );

const editBookingForm =
    document.getElementById(
        "editBookingForm"
    );


// Masa booking asal

const originalTime =
    "<?php
    echo htmlspecialchars(
        $booking["booking_time"]
    );
    ?>";


// ==========================================
// FORMAT TIME
// ==========================================

function formatTime(time) {

    const parts =
        time.split(":");

    let hour =
        parseInt(parts[0]);

    const minute =
        parts[1];

    const period =
        hour >= 12
        ? "PM"
        : "AM";


    hour =
        hour % 12;

    if (hour === 0) {
        hour = 12;
    }


    return (
        String(hour).padStart(2, "0")
        +
        ":"
        +
        minute
        +
        " "
        +
        period
    );

}


// ==========================================
// LOAD AVAILABLE SLOT
// ==========================================

function loadAvailableSlots() {

    const date =
        bookingDate.value;


    if (!date) {

        timeSlots.innerHTML =
            "<p>Please select a date.</p>";

        bookingTime.value = "";

        return;

    }


    timeSlots.innerHTML =
        "<p>Loading available slots...</p>";


    fetch(
        "get_available_slots.php?date="
        +
        encodeURIComponent(date)
    )

    .then(function(response) {

        return response.json();

    })

    .then(function(slots) {


        timeSlots.innerHTML = "";


        if (slots.length === 0) {

            timeSlots.innerHTML =
                "<p>No available time slots for this date.</p>";

            bookingTime.value = "";

            return;

        }


        let selectedStillAvailable =
            false;


        slots.forEach(function(time) {


            const button =
                document.createElement(
                    "button"
                );


            button.type =
                "button";

            button.className =
                "time-slot";

            button.dataset.time =
                time;

            button.textContent =
                formatTime(time);


            // Highlight current booking time

            if (
                bookingTime.value === time
            ) {

                button.classList.add(
                    "selected"
                );

                selectedStillAvailable =
                    true;

            }


            button.addEventListener(
                "click",
                function() {


                    document
                    .querySelectorAll(
                        ".time-slot"
                    )
                    .forEach(
                        function(item) {

                            item.classList.remove(
                                "selected"
                            );

                        }
                    );


                    button.classList.add(
                        "selected"
                    );


                    bookingTime.value =
                        time;

                }
            );


            timeSlots.appendChild(
                button
            );

        });


        // Jika masa lama tidak available lagi,
        // paksa customer pilih slot baru.

        if (!selectedStillAvailable) {

            bookingTime.value = "";

        }

    })

    .catch(function() {

        timeSlots.innerHTML =
            "<p>Unable to load available time slots.</p>";

        bookingTime.value = "";

    });

}


// ==========================================
// DATE BERUBAH
// ==========================================

bookingDate.addEventListener(
    "change",
    function() {

        bookingTime.value = "";

        loadAvailableSlots();

    }
);


// ==========================================
// VALIDATE SUBMIT
// ==========================================

editBookingForm.addEventListener(
    "submit",
    function(event) {

        if (!bookingTime.value) {

            event.preventDefault();

            alert(
                "Please select an available time slot."
            );

        }

    }
);


// Load slot tarikh booking semasa

loadAvailableSlots();

</script>


</body>

</html>


<?php

$conn->close();

?>