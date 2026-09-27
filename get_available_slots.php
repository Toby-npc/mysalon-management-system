<?php

session_start();
include "db.php";

header("Content-Type: application/json");

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "customer"
) {
    echo json_encode([]);
    exit();
}


$date = $_GET["date"] ?? "";


if (empty($date)) {
    echo json_encode([]);
    exit();
}


if ($date < date("Y-m-d")) {
    echo json_encode([]);
    exit();
}


// Slot yang digunakan oleh sistem
$slots = [
    "09:00:00",
    "11:00:00",
    "13:00:00",
    "15:00:00",
    "17:00:00"
];


$availableSlots = [];


foreach ($slots as $slot) {

    /*
        Cari sekurang-kurangnya seorang staff yang:

        1. Available pada tarikh tersebut
        2. Status = available
        3. Waktu slot berada dalam working hours staff
    */

    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total

         FROM staff_availability

         INNER JOIN users
            ON staff_availability.staff_id = users.user_id

         WHERE users.role = 'staff'

         AND staff_availability.available_date = ?

         AND staff_availability.status = 'available'

         AND staff_availability.start_time <= ?

         AND staff_availability.end_time >= ?"
    );


    $stmt->bind_param(
        "sss",
        $date,
        $slot,
        $slot
    );


    $stmt->execute();

    $result = $stmt->get_result();

    $row = $result->fetch_assoc();


    if ($row["total"] > 0) {

        $availableSlots[] = $slot;

    }


    $stmt->close();
}


echo json_encode($availableSlots);

$conn->close();

?>