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
// AMBIL PAYMENT ID
// ==========================================

$payment_id = $_GET["id"] ?? "";

if (
    empty($payment_id) ||
    !is_numeric($payment_id)
) {
    header("Location: MySalon(MYBOOKING).php");
    exit();
}

$payment_id = (int)$payment_id;


// ==========================================
// AMBIL PAYMENT + BOOKING + SERVICE
// ==========================================

$stmt = $conn->prepare(
    "SELECT
        payments.payment_id,
        payments.amount,
        payments.payment_method,
        payments.payment_status,
        payments.payment_date,

        bookings.booking_id,
        bookings.booking_date,
        bookings.booking_time,
        bookings.status AS booking_status,

        services.service_name,

        users.name AS customer_name,
        users.email AS customer_email

     FROM payments

     INNER JOIN bookings
        ON payments.booking_id =
           bookings.booking_id

     INNER JOIN services
        ON bookings.service_id =
           services.service_id

     INNER JOIN users
        ON bookings.customer_id =
           users.user_id

     WHERE payments.payment_id = ?
     AND bookings.customer_id = ?"
);


$stmt->bind_param(
    "ii",
    $payment_id,
    $customer_id
);


$stmt->execute();

$result = $stmt->get_result();


// ==========================================
// PAYMENT TAK WUJUD / BUKAN MILIK CUSTOMER
// ==========================================

if ($result->num_rows === 0) {

    $stmt->close();
    $conn->close();

    header("Location: MySalon(MYBOOKING).php");
    exit();
}


$receipt = $result->fetch_assoc();


// ==========================================
// HANYA RECEIPT PAYMENT PAID
// ==========================================

if ($receipt["payment_status"] !== "paid") {

    $stmt->close();
    $conn->close();

    header("Location: MySalon(MYBOOKING).php");
    exit();
}


// ==========================================
// LOAD DOMPDF
// ==========================================

require_once __DIR__ . "/vendor/autoload.php";


use Dompdf\Dompdf;
use Dompdf\Options;


// ==========================================
// DOMPDF OPTIONS
// ==========================================

$options = new Options();

$options->set(
    "isRemoteEnabled",
    false
);


$dompdf = new Dompdf($options);


// ==========================================
// ESCAPE DATA
// ==========================================

$receiptNumber =
    (int)$receipt["payment_id"];


$customerName =
    htmlspecialchars(
        $receipt["customer_name"],
        ENT_QUOTES,
        "UTF-8"
    );


$customerEmail =
    htmlspecialchars(
        $receipt["customer_email"],
        ENT_QUOTES,
        "UTF-8"
    );


$serviceName =
    htmlspecialchars(
        $receipt["service_name"],
        ENT_QUOTES,
        "UTF-8"
    );


$appointmentDate =
    date(
        "d M Y",
        strtotime(
            $receipt["booking_date"]
        )
    );


$appointmentTime =
    date(
        "h:i A",
        strtotime(
            $receipt["booking_time"]
        )
    );


$paymentMethod =
    htmlspecialchars(
        $receipt["payment_method"],
        ENT_QUOTES,
        "UTF-8"
    );


$paymentDate =
    date(
        "d M Y, h:i A",
        strtotime(
            $receipt["payment_date"]
        )
    );


$amount =
    number_format(
        (float)$receipt["amount"],
        2
    );


$paymentStatus =
    ucfirst(
        htmlspecialchars(
            $receipt["payment_status"],
            ENT_QUOTES,
            "UTF-8"
        )
    );


// ==========================================
// PDF HTML
// ==========================================

$html = '
<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<style>

    @page {
        margin: 35px;
    }

    body {
        font-family: DejaVu Sans, sans-serif;
        color: #2b2526;
        font-size: 14px;
        background: #ffffff;
    }

    .receipt {
        border: 1px solid #ebdcd0;
        padding: 35px;
    }

    .header {
        text-align: center;
        margin-bottom: 30px;
    }

    .header h1 {
        margin: 0;
        font-size: 28px;
        color: #b0717a;
    }

    .header p {
        margin-top: 8px;
        color: #6d6263;
    }

    .receipt-number {
        text-align: center;
        margin-bottom: 30px;
        font-weight: bold;
    }

    .row {
        padding: 10px 0;
        border-bottom: 1px solid #f2e9e1;
    }

    .label {
        display: inline-block;
        width: 180px;
        font-weight: bold;
    }

    .amount {
        margin-top: 25px;
        padding: 18px;
        text-align: center;
        background: #faf6f0;
        font-size: 20px;
        font-weight: bold;
    }

    .status {
        text-align: center;
        margin-top: 15px;
    }

    .footer {
        text-align: center;
        margin-top: 35px;
        font-size: 11px;
        color: #777777;
    }

</style>

</head>


<body>

<div class="receipt">


    <div class="header">

        <h1>
            MySalon
        </h1>

        <p>
            Payment Receipt
        </p>

    </div>


    <div class="receipt-number">

        Receipt No: #' . $receiptNumber . '

    </div>


    <div class="row">

        <span class="label">
            Customer:
        </span>

        ' . $customerName . '

    </div>


    <div class="row">

        <span class="label">
            Email:
        </span>

        ' . $customerEmail . '

    </div>


    <div class="row">

        <span class="label">
            Service:
        </span>

        ' . $serviceName . '

    </div>


    <div class="row">

        <span class="label">
            Appointment Date:
        </span>

        ' . $appointmentDate . '

    </div>


    <div class="row">

        <span class="label">
            Appointment Time:
        </span>

        ' . $appointmentTime . '

    </div>


    <div class="row">

        <span class="label">
            Payment Method:
        </span>

        ' . $paymentMethod . '

    </div>


    <div class="row">

        <span class="label">
            Payment Date:
        </span>

        ' . $paymentDate . '

    </div>


    <div class="amount">

        Amount Paid: $' . $amount . '

    </div>


    <div class="status">

        Payment Status:
        <strong>
            ' . $paymentStatus . '
        </strong>

    </div>


    <div class="footer">

        Thank you for choosing MySalon.<br>

        This receipt was generated electronically
        by MySalon Management System.

    </div>


</div>

</body>

</html>
';


// ==========================================
// GENERATE PDF
// ==========================================

$dompdf->loadHtml($html);

$dompdf->setPaper(
    "A4",
    "portrait"
);

$dompdf->render();


// ==========================================
// DOWNLOAD PDF
// ==========================================

$fileName =
    "MySalon_Receipt_"
    . $receiptNumber
    . ".pdf";


$dompdf->stream(
    $fileName,
    [
        "Attachment" => true
    ]
);


// ==========================================
// CLOSE
// ==========================================

$stmt->close();
$conn->close();

exit();

?>