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
$today = date("Y-m-d");


// ==========================================
// CHECK ATTENDANCE HARI INI
// ==========================================

$stmt = $conn->prepare(
    "SELECT *
     FROM attendance
     WHERE staff_id = ?
     AND attendance_date = ?
     LIMIT 1"
);


$stmt->bind_param(
    "is",
    $staff_id,
    $today
);


$stmt->execute();

$todayResult = $stmt->get_result();

$todayAttendance = null;


if ($todayResult->num_rows === 1) {

    $todayAttendance =
        $todayResult->fetch_assoc();

}


$stmt->close();


// ==========================================
// CHECK IN / CHECK OUT
// ==========================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action =
        $_POST["action"] ?? "";


    // ======================================
    // CHECK IN
    // ======================================

    if ($action === "check_in") {


        // BELUM CHECK IN HARI INI

        if (!$todayAttendance) {

            $current_time =
                date("H:i:s");


            $stmt = $conn->prepare(
                "INSERT INTO attendance
                (
                    staff_id,
                    attendance_date,
                    check_in
                )
                VALUES (?, ?, ?)"
            );


            $stmt->bind_param(
                "iss",
                $staff_id,
                $today,
                $current_time
            );


            $stmt->execute();

            $stmt->close();


            header(
                "Location: staff_attendance.php?checkin=1"
            );

            exit();

        }

    }


    // ======================================
    // CHECK OUT
    // ======================================

    if ($action === "check_out") {

        if (
            $todayAttendance &&
            empty(
                $todayAttendance["check_out"]
            )
        ) {

            $current_time =
                date("H:i:s");


            $attendance_id =
                $todayAttendance[
                    "attendance_id"
                ];


            $stmt = $conn->prepare(
                "UPDATE attendance
                 SET check_out = ?
                 WHERE attendance_id = ?
                 AND staff_id = ?"
            );


            $stmt->bind_param(
                "sii",
                $current_time,
                $attendance_id,
                $staff_id
            );


            $stmt->execute();

            $stmt->close();


            header(
                "Location: staff_attendance.php?checkout=1"
            );

            exit();

        }

    }

}


// ==========================================
// AMBIL SEMULA ATTENDANCE HARI INI
// ==========================================

$stmt = $conn->prepare(
    "SELECT *
     FROM attendance
     WHERE staff_id = ?
     AND attendance_date = ?
     LIMIT 1"
);


$stmt->bind_param(
    "is",
    $staff_id,
    $today
);


$stmt->execute();

$todayResult =
    $stmt->get_result();


$todayAttendance = null;


if ($todayResult->num_rows === 1) {

    $todayAttendance =
        $todayResult->fetch_assoc();

}


$stmt->close();


// ==========================================
// ATTENDANCE HISTORY
// ==========================================

$stmt = $conn->prepare(
    "SELECT
        attendance_date,
        check_in,
        check_out

     FROM attendance

     WHERE staff_id = ?

     ORDER BY attendance_date DESC"
);


$stmt->bind_param(
    "i",
    $staff_id
);


$stmt->execute();

$history =
    $stmt->get_result();

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
        Staff Attendance - MySalon
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
     ATTENDANCE
=========================================== -->

<section>


    <div class="section-title">

        <h2>
            Staff Attendance
        </h2>

        <p>
            Record your daily check-in and check-out
        </p>

    </div>



    <!-- ======================================
         SUCCESS MESSAGES
    ======================================= -->

    <?php if (
        isset($_GET["checkin"])
    ): ?>

        <p style="
            text-align:center;
            margin-bottom:25px;
        ">

            Check-in recorded successfully!

        </p>

    <?php endif; ?>



    <?php if (
        isset($_GET["checkout"])
    ): ?>

        <p style="
            text-align:center;
            margin-bottom:25px;
        ">

            Check-out recorded successfully!

        </p>

    <?php endif; ?>



    <!-- ======================================
         TODAY ATTENDANCE CARD
    ======================================= -->

    <div class="booking-details">


        <div class="booking-card">


            <h3>
                Today's Attendance
            </h3>



            <!-- DATE -->

            <p>

                <strong>
                    Date:
                </strong>

                <?php

                echo date(
                    "d M Y",
                    strtotime($today)
                );

                ?>

            </p>



            <!-- CHECK IN -->

            <p>

                <strong>
                    Check In:
                </strong>


                <?php

                if (
                    $todayAttendance &&
                    !empty(
                        $todayAttendance[
                            "check_in"
                        ]
                    )
                ) {

                    echo date(
                        "h:i A",
                        strtotime(
                            $todayAttendance[
                                "check_in"
                            ]
                        )
                    );

                } else {

                    echo "Not checked in";

                }

                ?>

            </p>



            <!-- CHECK OUT -->

            <p>

                <strong>
                    Check Out:
                </strong>


                <?php

                if (
                    $todayAttendance &&
                    !empty(
                        $todayAttendance[
                            "check_out"
                        ]
                    )
                ) {

                    echo date(
                        "h:i A",
                        strtotime(
                            $todayAttendance[
                                "check_out"
                            ]
                        )
                    );

                } else {

                    echo "Not checked out";

                }

                ?>

            </p>



            <!-- ==================================
                 BELUM CHECK IN
            =================================== -->

            <?php if (
                !$todayAttendance
            ): ?>


                <form
                    method="POST"
                    action="staff_attendance.php"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="check_in"
                    >

                    <button
                        type="submit"
                        class="button"
                    >
                        Check In
                    </button>

                </form>



            <!-- ==================================
                 SUDAH CHECK IN,
                 BELUM CHECK OUT
            =================================== -->

            <?php elseif (
                empty(
                    $todayAttendance[
                        "check_out"
                    ]
                )
            ): ?>


                <form
                    method="POST"
                    action="staff_attendance.php"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="check_out"
                    >

                    <button
                        type="submit"
                        class="button"
                    >
                        Check Out
                    </button>

                </form>



            <!-- ==================================
                 SUDAH SELESAI
            =================================== -->

            <?php else: ?>


                <p>
                    Attendance completed for today.
                </p>


            <?php endif; ?>


        </div>


    </div>



    <!-- ======================================
         ATTENDANCE HISTORY
    ======================================= -->

    <div
        class="section-title"
        style="margin-top:60px;"
    >

        <h2>
            Attendance History
        </h2>

        <p>
            Review your previous attendance records
        </p>

    </div>



    <div class="booking-details">


        <?php if (
            $history->num_rows > 0
        ): ?>


            <?php while (
                $attendance =
                $history->fetch_assoc()
            ): ?>


                <div class="booking-card">


                    <h3>

                        <?php

                        echo date(
                            "d M Y",
                            strtotime(
                                $attendance[
                                    "attendance_date"
                                ]
                            )
                        );

                        ?>

                    </h3>



                    <!-- HISTORY CHECK IN -->

                    <p>

                        <strong>
                            Check In:
                        </strong>

                        <?php

                        if (
                            !empty(
                                $attendance[
                                    "check_in"
                                ]
                            )
                        ) {

                            echo date(
                                "h:i A",
                                strtotime(
                                    $attendance[
                                        "check_in"
                                    ]
                                )
                            );

                        } else {

                            echo "-";

                        }

                        ?>

                    </p>



                    <!-- HISTORY CHECK OUT -->

                    <p>

                        <strong>
                            Check Out:
                        </strong>

                        <?php

                        if (
                            !empty(
                                $attendance[
                                    "check_out"
                                ]
                            )
                        ) {

                            echo date(
                                "h:i A",
                                strtotime(
                                    $attendance[
                                        "check_out"
                                    ]
                                )
                            );

                        } else {

                            echo "-";

                        }

                        ?>

                    </p>


                </div>


            <?php endwhile; ?>


        <?php else: ?>


            <p style="
                text-align:center;
                width:100%;
            ">

                No attendance records yet.

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