<?php

session_start();
include "db.php";
require_once __DIR__ . "/bank_common.php";
require_once __DIR__ . "/receipt_pdf.php";


// ==========================================
// LANGKAH 6 DAN 7: HANTAR RESIT PDF KE EMEL
// Menggunakan Brevo API melalui HTTPS.
// Tetapan dalam mail_config.php
// ==========================================

function send_receipt_email($to, $payment_id, $booking, $amount, $bank_name)
{
    $cfg = @include __DIR__ . "/mail_config.php";

    if (
        !is_array($cfg) ||
        empty($cfg["api_key"]) ||
        empty($cfg["sender_email"])
    ) {
        error_log("MySalon receipt: mail_config.php tidak lengkap");
        return false;
    }

    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $ref  = "MS-" . str_pad((string)$payment_id, 6, "0", STR_PAD_LEFT);
    $time = date("h:i A", strtotime($booking["booking_time"]));
    $paid = date("d M Y, h:i A");

    $pdf = build_receipt_pdf(
        "MySalon",
        "Resit pembayaran",
        [
            ["No. rujukan",      $ref],
            ["Tarikh bayaran",   $paid],
            ["Servis",           $booking["service_name"]],
            ["Tarikh temujanji", $booking["booking_date"] . ", " . $time],
            ["Kaedah",           "Online Banking"],
            ["Bank (demo)",      $bank_name],
            ["Status",           "PAID"]
        ],
        "Jumlah dibayar",
        "$" . number_format($amount, 2),
        "Pembayaran ini ialah simulasi untuk projek MySalon. Tiada wang sebenar dipindahkan."
    );

    $h = function ($v) {
        return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
    };

    $html =
        "<div style='font-family:Arial,sans-serif;max-width:480px'>" .
        "<h2>Terima kasih daripada MySalon</h2>" .
        "<p>Pembayaran anda untuk <b>" . $h($booking["service_name"]) . "</b> " .
        "berjumlah <b>$" . number_format($amount, 2) . "</b> telah direkodkan.</p>" .
        "<p>Resit PDF (no. rujukan <b>" . $h($ref) . "</b>) dilampirkan bersama emel ini.</p>" .
        "<p style='color:#888;font-size:12px'>Simulasi untuk projek MySalon. Tiada wang sebenar dipindahkan.</p>" .
        "</div>";

    $payload = json_encode([
        "sender" => [
            "name"  => $cfg["sender_name"] ?? "MySalon",
            "email" => $cfg["sender_email"]
        ],
        "to"          => [["email" => $to]],
        "subject"     => "Resit pembayaran " . $ref,
        "htmlContent" => $html,
        "attachment"  => [[
            "name"    => "Resit-" . $ref . ".pdf",
            "content" => base64_encode($pdf)
        ]]
    ]);

    $ch = curl_init("https://api.brevo.com/v3/smtp/email");

    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => [
            "accept: application/json",
            "content-type: application/json",
            "api-key: " . $cfg["api_key"]
        ]
    ]);

    $response = curl_exec($ch);
    $code     = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code < 200 || $code >= 300) {
        error_log("MySalon receipt gagal (HTTP $code): " . $response);
        return false;
    }

    return true;
}


// ==========================================
// ENDPOINT
// ==========================================

$ctx = bank_boot($conn);
$booking_id = $ctx["booking_id"];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    bank_redirect($conn, bank_step_url($ctx["step"], $booking_id));
}

bank_require_step($conn, $ctx, "email");

$payment_id = (int)$_SESSION["bank_demo"]["payment_id"];
$action     = $_POST["action"] ?? "";


// ---- Langkau emel ----

if ($action === "skip") {
    unset($_SESSION["bank_demo"]);
    bank_redirect($conn, "receipt.php?id=" . $payment_id);
}


// ---- Hantar emel ----

$receipt_to = trim($_POST["email"] ?? "");

if (!filter_var($receipt_to, FILTER_VALIDATE_EMAIL)) {
    bank_redirect(
        $conn,
        "bank_process.php?id=" . $booking_id . "&error=email"
    );
}

$bank_name = demo_banks()[$_SESSION["bank_demo"]["bank"] ?? ""] ?? "DemoBank";

$sent = send_receipt_email(
    $receipt_to,
    $payment_id,
    $ctx["booking"],
    $ctx["price"],
    $bank_name
);

unset($_SESSION["bank_demo"]);

bank_redirect(
    $conn,
    "receipt.php?id=" . $payment_id . "&mail=" . ($sent ? "sent" : "failed")
);

?>
