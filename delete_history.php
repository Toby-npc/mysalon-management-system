<?php

session_start();
include "db.php";


// CUSTOMER SAHAJA
if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "customer"
) {
    header("Location: login.php");
    exit();
}


// MESTI POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: MySalon(MYBOOKING).php");
    exit();
}


$customer_id = $_SESSION["user_id"];
$booking_id = $_POST["booking_id"] ?? "";


// VALIDATE ID
if (
    empty($booking_id) ||
    !is_numeric($booking_id)
) {
    header("Location: MySalon(MYBOOKING).php");
    exit();
}


$booking_id = (int)$booking_id;


// HIDE DARIPADA CUSTOMER HISTORY
// HANYA CANCELLED / COMPLETED

$stmt = $conn->prepare(
    "UPDATE bookings
     SET customer_hidden = 1
     WHERE booking_id = ?
     AND customer_id = ?
     AND status IN ('cancelled', 'completed')"
);


$stmt->bind_param(
    "ii",
    $booking_id,
    $customer_id
);


$stmt->execute();

$stmt->close();
$conn->close();


header(
    "Location: MySalon(MYBOOKING).php?history_deleted=1"
);

exit();

?>