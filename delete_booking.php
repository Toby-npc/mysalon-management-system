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


// ==========================================
// CANCEL HANYA MELALUI POST
// ==========================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header(
        "Location: MySalon(MYBOOKING).php"
    );

    exit();

}


$customer_id =
    $_SESSION["user_id"];


$booking_id =
    $_POST["booking_id"] ?? "";


// ==========================================
// VALIDATE BOOKING ID
// ==========================================

if (
    empty($booking_id) ||
    !is_numeric($booking_id)
) {

    header(
        "Location: MySalon(MYBOOKING).php"
    );

    exit();

}


$booking_id =
    (int) $booking_id;


// ==========================================
// SEMAK BOOKING MILIK CUSTOMER
// ==========================================

$check = $conn->prepare(
    "SELECT
        booking_id,
        status

     FROM bookings

     WHERE booking_id = ?
     AND customer_id = ?"
);


$check->bind_param(
    "ii",
    $booking_id,
    $customer_id
);


$check->execute();


$result =
    $check->get_result();


// ==========================================
// BOOKING TIDAK WUJUD / BUKAN MILIK CUSTOMER
// ==========================================

if ($result->num_rows === 0) {

    $check->close();
    $conn->close();

    header(
        "Location: MySalon(MYBOOKING).php"
    );

    exit();

}


$booking =
    $result->fetch_assoc();


$check->close();


// ==========================================
// JANGAN CANCEL BOOKING COMPLETED
// ==========================================

if (
    $booking["status"] === "completed"
) {

    $conn->close();

    header(
        "Location: MySalon(MYBOOKING).php?cancel_error=completed"
    );

    exit();

}


// ==========================================
// JIKA SUDAH CANCELLED
// ==========================================

if (
    $booking["status"] === "cancelled"
) {

    $conn->close();

    header(
        "Location: MySalon(MYBOOKING).php?cancelled=1"
    );

    exit();

}


// ==========================================
// CANCEL BOOKING
// ==========================================

$stmt = $conn->prepare(
    "UPDATE bookings

     SET status = 'cancelled'

     WHERE booking_id = ?
     AND customer_id = ?"
);


$stmt->bind_param(
    "ii",
    $booking_id,
    $customer_id
);


$stmt->execute();


$stmt->close();
$conn->close();


// ==========================================
// KEMBALI KE MY BOOKING
// ==========================================

header(
    "Location: MySalon(MYBOOKING).php?cancelled=1"
);

exit();

?>