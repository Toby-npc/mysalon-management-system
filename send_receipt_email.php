<?php

session_start();
include "db.php";
require_once __DIR__ . "/bank_common.php";


// ==========================================
// LANGKAH 6 DAN 7: HANTAR RESIT PDF KE EMEL
// Menggunakan Brevo API melalui HTTPS.
// Tetapan dalam mail_config.php
//
// Fail ini tidak akan menyebabkan HTTP 500.
// Jika gagal, sebab ditunjukkan di halaman emel
// dan pelanggan boleh cuba lagi atau langkau.
// ==========================================


// ---- Muat tetapan dengan selamat ----

function receipt_load_config()
{
    $file = __DIR__ . "/mail_config.php";

    if (!is_file($file)) {
        return [null, "nocfgfile"];
    }

    try {
        $cfg = include $file;
    } catch (Throwable $e) {
        error_log("MySalon mail_config.php ralat: " . $e->getMessage());
        return [null, "cfgerror"];
    }

    if (
        !is_array($cfg) ||
        empty($cfg["api_key"]) ||
        empty($cfg["sender_email"]) ||
        strpos($cfg["api_key"], "ISI_API_KEY") !== false ||
        strpos($cfg["sender_email"], "contoh.com") !== false
    ) {
        return [null, "nokey"];
    }

    return [$cfg, ""];
}


// ---- POST HTTPS: cURL, atau stream jika cURL dimatikan ----

function receipt_http_post($url, array $headers, $body)
{
    if (function_exists("curl_init")) {

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => $headers
        ]);

        $response = curl_exec($ch);
        $code     = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            error_log("MySalon cURL ralat: " . $error);
            return [0, "", "network"];
        }

        return [$code, (string)$response, ""];
    }

    if (ini_get("allow_url_fopen")) {

        $context = stream_context_create([
            "http" => [
                "method"        => "POST",
                "header"        => implode("\r\n", $headers),
                "content"       => $body,
                "timeout"       => 20,
                "ignore_errors" => true
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return [0, "", "network"];
        }

        $code = 0;
        if (
            isset($http_response_header[0]) &&
            preg_match("#HTTP/\S+\s+(\d{3})#", $http_response_header[0], $m)
        ) {
            $code = (int)$m[1];
        }

        return [$code, (string)$response, ""];
    }

    return [0, "", "nohttp"];
}


// ---- Bina PDF + hantar. Pulangkan [berjaya, kod sebab] ----

function send_receipt_email($to, array $data, $bank_name)
{
    list($cfg, $why) = receipt_load_config();

    if ($cfg === null) {
        return [false, $why];
    }

    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return [false, "bademail"];
    }

    // ---- PDF ----
    if (!function_exists("receipt_make_pdf")) {

        $lib = __DIR__ . "/receipt_pdf.php";

        if (!is_file($lib)) {
            return [false, "nopdflib"];
        }

        require_once $lib;
    }

    $payment_id = (int)$data["payment_id"];
    $amount     = (float)$data["amount"];
    $ref        = "#" . $payment_id;
    $booking    = ["service_name" => $data["service_name"]];

    try {

        $pdf = receipt_make_pdf($data, $bank_name);

    } catch (Throwable $e) {
        error_log("MySalon PDF ralat: " . $e->getMessage());
        return [false, "pdferror"];
    }

    // ---- Emel ----
    $h = function ($v) {
        return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
    };

    $html =
        "<div style='font-family:Arial,sans-serif;max-width:480px'>" .
        "<h2>Terima kasih daripada MySalon</h2>" .
        "<p>Pembayaran anda untuk <b>" . $h($booking["service_name"]) . "</b> " .
        "berjumlah <b>$" . number_format($amount, 2) . "</b> telah direkodkan.</p>" .
        "<p>Resit PDF (no. resit <b>" . $h($ref) . "</b>) dilampirkan bersama emel ini.</p>" .
        "<p style='color:#888;font-size:12px'>Simulasi untuk projek MySalon. Tiada wang sebenar dipindahkan.</p>" .
        "</div>";

    $payload = json_encode([
        "sender" => [
            "name"  => isset($cfg["sender_name"]) ? $cfg["sender_name"] : "MySalon",
            "email" => $cfg["sender_email"]
        ],
        "to"          => [["email" => $to]],
        "subject"     => "Resit pembayaran MySalon " . $ref,
        "htmlContent" => $html,
        "attachment"  => [[
            "name"    => "Resit-MySalon-" . $payment_id . ".pdf",
            "content" => base64_encode($pdf)
        ]]
    ]);

    list($code, $response, $transport_error) = receipt_http_post(
        "https://api.brevo.com/v3/smtp/email",
        [
            "accept: application/json",
            "content-type: application/json",
            "api-key: " . $cfg["api_key"]
        ],
        $payload
    );

    if ($transport_error !== "") {
        return [false, $transport_error];
    }

    if ($code < 200 || $code >= 300) {
        error_log("MySalon Brevo HTTP $code: " . $response);
        return [false, "http_" . $code];
    }

    return [true, ""];
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

$bank_key  = isset($_SESSION["bank_demo"]["bank"]) ? $_SESSION["bank_demo"]["bank"] : "";
$banks     = demo_banks();
$bank_name = isset($banks[$bank_key]) ? $banks[$bank_key] : "DemoBank";

// ---- Data resit daripada database (sama seperti receipt.php) ----

$data = null;

try {

    $q = $conn->prepare(
        "SELECT
            payments.payment_id,
            payments.amount,
            payments.payment_method,
            payments.payment_status,
            payments.payment_date,
            bookings.booking_date,
            bookings.booking_time,
            services.service_name
         FROM payments
         INNER JOIN bookings
            ON payments.booking_id = bookings.booking_id
         INNER JOIN services
            ON bookings.service_id = services.service_id
         WHERE payments.payment_id = ?
         AND bookings.customer_id = ?"
    );

    $q->bind_param("ii", $payment_id, $ctx["customer_id"]);
    $q->execute();
    $row = $q->get_result()->fetch_assoc();
    $q->close();

    if ($row) {
        $data = $row;
    }

} catch (Throwable $e) {
    error_log("MySalon ambil resit gagal: " . $e->getMessage());
}

if ($data === null) {

    $data = [
        "payment_id"     => $payment_id,
        "amount"         => $ctx["price"],
        "payment_method" => "Online Banking",
        "payment_status" => "paid",
        "payment_date"   => date("Y-m-d H:i:s"),
        "booking_date"   => $ctx["booking"]["booking_date"],
        "booking_time"   => $ctx["booking"]["booking_time"],
        "service_name"   => $ctx["booking"]["service_name"]
    ];
}

$data["customer_name"] = isset($_SESSION["name"]) ? $_SESSION["name"] : "Customer";

try {

    list($sent, $why) = send_receipt_email(
        $receipt_to,
        $data,
        $bank_name
    );

} catch (Throwable $e) {

    error_log("MySalon send_receipt_email ralat: " . $e->getMessage());
    $sent = false;
    $why  = "exception";
}

if ($sent) {

    unset($_SESSION["bank_demo"]);

    bank_redirect(
        $conn,
        "receipt.php?id=" . $payment_id . "&mail=sent"
    );
}

// Gagal: kekal di langkah emel supaya boleh cuba lagi atau langkau
bank_redirect(
    $conn,
    "bank_process.php?id=" . $booking_id . "&error=send&why=" . urlencode($why)
);

?>
