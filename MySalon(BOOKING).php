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

$message = "";


// ==========================================
// SERVICE YANG DIPILIH DARI SERVICES PAGE
// ==========================================

$selected_service_id =
    $_GET["service_id"] ?? "";


// Pastikan service_id ialah nombor

if (
    $selected_service_id !== "" &&
    !is_numeric($selected_service_id)
) {

    $selected_service_id = "";

}


// ==========================================
// PROSES BOOKING
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    $service_id =
        $_POST["service_id"] ?? "";

    $booking_date =
        $_POST["booking_date"] ?? "";

    $booking_time =
        $_POST["booking_time"] ?? "";


    // ======================================
    // VALIDATION
    // ======================================

    if (
        empty($service_id) ||
        empty($booking_date) ||
        empty($booking_time)
    ) {

        $message =
            "Please complete all booking information.";

    } elseif (
        !is_numeric($service_id)
    ) {

        $message =
            "Invalid service.";

    } elseif (
        $booking_date < date("Y-m-d")
    ) {

        $message =
            "Please select today or a future date.";

    } else {


        // ==================================
        // SEMAK SERVICE MASIH AVAILABLE
        // ==================================

        $check = $conn->prepare(
            "SELECT service_id
             FROM services
             WHERE service_id = ?
             AND status = 'available'"
        );


        $check->bind_param(
            "i",
            $service_id
        );


        $check->execute();


        $serviceResult =
            $check->get_result();


        // ==================================
        // SERVICE TAK AVAILABLE
        // ==================================

        if ($serviceResult->num_rows === 0) {

            $message =
                "Selected service is not available.";

            $check->close();

        } else {


            // ==================================
            // SEMAK SLOT ADA STAFF AVAILABLE
            // ==================================

            $availabilityCheck =
                $conn->prepare(
                    "SELECT COUNT(*) AS total

                     FROM staff_availability

                     INNER JOIN users
                        ON staff_availability.staff_id =
                           users.user_id

                     WHERE users.role = 'staff'

                     AND staff_availability.available_date = ?

                     AND staff_availability.status =
                         'available'

                     AND staff_availability.start_time <= ?

                     AND staff_availability.end_time >= ?"
                );


            $availabilityCheck->bind_param(
                "sss",
                $booking_date,
                $booking_time,
                $booking_time
            );


            $availabilityCheck->execute();


            $availabilityResult =
                $availabilityCheck->get_result();


            $availability =
                $availabilityResult->fetch_assoc();


            // ==================================
            // TIADA STAFF AVAILABLE
            // ==================================

            if (
                !$availability ||
                $availability["total"] <= 0
            ) {

                $message =
                    "The selected time slot is no longer available.";

                $availabilityCheck->close();
                $check->close();

            } else {


                // ==================================
                // SIMPAN BOOKING
                // ==================================

                /*
                    Staff masih NULL.

                    Admin akan assign staff selepas
                    customer membuat booking.
                */

                $stmt = $conn->prepare(
                    "INSERT INTO bookings
                    (
                        customer_id,
                        service_id,
                        staff_id,
                        booking_date,
                        booking_time,
                        status
                    )
                    VALUES (
                        ?,
                        ?,
                        NULL,
                        ?,
                        ?,
                        'pending'
                    )"
                );


                $stmt->bind_param(
                    "iiss",
                    $customer_id,
                    $service_id,
                    $booking_date,
                    $booking_time
                );


                // ==================================
                // BOOKING BERJAYA
                // ==================================

                if ($stmt->execute()) {


                    $stmt->close();

                    $availabilityCheck->close();

                    $check->close();


                    header(
                        "Location: MySalon(MYBOOKING).php?success=1"
                    );

                    exit();


                } else {


                    $message =
                        "Booking failed. Please try again.";


                    $stmt->close();

                    $availabilityCheck->close();

                    $check->close();

                }

            }

        }

    }

}


// ==========================================
// AMBIL SERVICE AVAILABLE DARI DATABASE
// ==========================================

$services = $conn->query(
    "SELECT
        service_id,
        service_name,
        price,
        discount_percent,
        promotion_status

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
        Booking - MySalon
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


    <!-- ======================================
         LEFT BRAND
    ======================================= -->

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
             DROPDOWN
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



    <!-- ======================================
         RIGHT NAVIGATION
    ======================================= -->

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
     BOOKING SECTION
=========================================== -->

<section>


    <div class="section-title">


        <h2>
            Book Your Appointment
        </h2>


        <p>
            Fill out your parameters to secure your salon allocation space
        </p>


    </div>



    <div class="booking-form">


        <!-- ==================================
             ERROR MESSAGE
        =================================== -->

        <?php if (!empty($message)): ?>


            <p style="
                text-align:center;
                margin-bottom:20px;
                color:#9c4444;
            ">

                <?php
                echo htmlspecialchars($message);
                ?>

            </p>


        <?php endif; ?>



        <!-- ==================================
             BOOKING FORM
        =================================== -->

        <form
            method="POST"
            action="MySalon(BOOKING).php"
            id="bookingForm"
        >



            <!-- ==================================
                 CUSTOMER INFORMATION
            =================================== -->

            <div class="form-row">


                <!-- FULL NAME -->

                <div class="form-group">


                    <label for="name">
                        Full Name
                    </label>


                    <input
                        type="text"
                        id="name"
                        value="<?php

                        echo htmlspecialchars(
                            $_SESSION["name"]
                        );

                        ?>"
                        readonly
                    >


                </div>



                <!-- PHONE NUMBER -->

                <div class="form-group">


                    <label for="phone">
                        Phone Number
                    </label>


                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        placeholder="012-3456789"
                        required
                    >


                </div>


            </div>



            <!-- ==================================
                 SERVICE + DATE
            =================================== -->

            <div class="form-row">


                <!-- SERVICE -->

                <div class="form-group">


                    <label for="service_id">
                        Select Service
                    </label>


                    <select
                        id="service_id"
                        name="service_id"
                        required
                    >


                        <option value="">
                            Choose a service
                        </option>



                        <?php while (
                            $service =
                            $services->fetch_assoc()
                        ): ?>


                            <option

                                value="<?php

                                echo $service["service_id"];

                                ?>"

                                <?php

                                // AUTO SELECT SERVICE

                                if (
                                    (string)$selected_service_id ===
                                    (string)$service["service_id"]
                                ) {

                                    echo "selected";

                                }

                                ?>

                            >


                                <?php

                                $display_price =
                                    (float)$service["price"];

                                $discount =
                                    (float)$service[
                                        "discount_percent"
                                    ];


                                if (
                                    $service["promotion_status"] ===
                                    "active"
                                    &&
                                    $discount > 0
                                ) {

                                    $display_price =
                                        $display_price -
                                        (
                                            $display_price *
                                            ($discount / 100)
                                        );

                                }


                                echo htmlspecialchars(
                                    $service["service_name"]
                                );

                                ?>

                                - $<?php

                                echo number_format(
                                    $display_price,
                                    2
                                );

                                ?>


                                <?php if (
                                    $service["promotion_status"] ===
                                    "active"
                                    &&
                                    $discount > 0
                                ): ?>

                                    (<?php
                                    echo number_format(
                                        $discount,
                                        0
                                    );
                                    ?>% OFF)

                                <?php endif; ?>


                            </option>


                        <?php endwhile; ?>


                    </select>


                </div>



                <!-- DATE -->

                <div class="form-group">


                    <label for="booking_date">
                        Preferred Date
                    </label>


                    <input
                        type="date"
                        id="booking_date"
                        name="booking_date"
                        min="<?php
                        echo date("Y-m-d");
                        ?>"
                        required
                    >


                </div>


            </div>



            <!-- ==================================
                 TIME SLOT
            =================================== -->

            <div class="form-group">


                <label>
                    Available Time Slot
                </label>


                <p
                    id="slotMessage"
                    style="margin-bottom:15px;"
                >
                    Please select a date first.
                </p>


                <div
                    class="time-slots"
                    id="timeSlots"
                >
                </div>


                <!-- SELECTED TIME -->

                <input
                    type="hidden"
                    id="booking_time"
                    name="booking_time"
                >


            </div>



            <!-- ==================================
                 CONFIRM BOOKING
            =================================== -->

            <button
                type="submit"
                class="button confirm-button"
            >

                Confirm Appointment

            </button>


        </form>


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
     JAVASCRIPT
=========================================== -->

<script>


// ==========================================
// LOGO DROPDOWN
// ==========================================

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



// ==========================================
// BOOKING ELEMENTS
// ==========================================

const bookingDate =
    document.getElementById(
        "booking_date"
    );


const timeSlotsContainer =
    document.getElementById(
        "timeSlots"
    );


const bookingTime =
    document.getElementById(
        "booking_time"
    );


const slotMessage =
    document.getElementById(
        "slotMessage"
    );



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
        hour
            .toString()
            .padStart(2, "0")
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

bookingDate.addEventListener(
    "change",
    function() {


        const selectedDate =
            this.value;


        // RESET SLOT LAMA

        bookingTime.value = "";

        timeSlotsContainer.innerHTML = "";


        if (selectedDate === "") {


            slotMessage.textContent =
                "Please select a date first.";


            return;

        }


        slotMessage.textContent =
            "Checking available time slots...";



        // AMBIL SLOT DARIPADA PHP

        fetch(
            "get_available_slots.php?date="
            +
            encodeURIComponent(
                selectedDate
            )
        )


        .then(function(response) {


            if (!response.ok) {

                throw new Error(
                    "Unable to load available slots."
                );

            }


            return response.json();

        })


        .then(function(slots) {


            // TIADA SLOT AVAILABLE

            if (
                !Array.isArray(slots) ||
                slots.length === 0
            ) {


                slotMessage.textContent =
                    "No available time slots for this date.";


                return;

            }


            // ADA SLOT AVAILABLE

            slotMessage.textContent =
                "Select one of the available times:";



            slots.forEach(
                function(time) {


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



                    // CUSTOMER PILIH SLOT

                    button.addEventListener(
                        "click",
                        function() {


                            document
                                .querySelectorAll(
                                    ".time-slot"
                                )
                                .forEach(
                                    function(slot) {


                                        slot
                                            .classList
                                            .remove(
                                                "selected"
                                            );

                                    }
                                );


                            this.classList.add(
                                "selected"
                            );


                            bookingTime.value =
                                this.dataset.time;


                        }
                    );


                    timeSlotsContainer
                        .appendChild(
                            button
                        );


                }
            );


        })


        .catch(function(error) {


            console.error(error);


            slotMessage.textContent =
                "Unable to load available time slots.";


        });


    }
);



// ==========================================
// CHECK TIME BEFORE SUBMIT
// ==========================================

document
    .getElementById(
        "bookingForm"
    )
    .addEventListener(
        "submit",
        function(event) {


            if (
                bookingTime.value === ""
            ) {


                event.preventDefault();


                alert(
                    "Please select an available time slot."
                );


            }


        }
    );


</script>


</body>
</html>


<?php

$conn->close();

?>