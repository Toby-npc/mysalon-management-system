<?php

session_start();
include "db.php";
require_once __DIR__ . "/bank_common.php";


// ==========================================
// LANGKAH 4 DAN 5:
// - POST approve : rekod payment sebagai "paid"
// - POST cancel  : batal sebelum bayaran direkod
// - GET          : borang emel penerima resit
// ==========================================

$ctx = bank_boot($conn);
$booking_id = $ctx["booking_id"];
$message = "";


// ---- BATAL (sebelum bayaran direkod) ----

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["action"] ?? "") === "cancel" &&
    $ctx["step"] !== "email"
) {
    unset($_SESSION["bank_demo"]);
    bank_redirect($conn, "payment.php?id=" . $booking_id);
}


// ---- APPROVE: REKOD PAYMENT ----

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["action"] ?? "") === "approve"
) {

    bank_require_step($conn, $ctx, "approve");

    // Amount datang daripada database, dikira di server
    $amount = $ctx["price"];
    $method = "Online Banking";

    $ins = $conn->prepare(
        "INSERT INTO payments
        (booking_id, amount, payment_method, payment_status)
        VALUES (?, ?, ?, 'paid')"
    );

    $ins->bind_param("ids", $booking_id, $amount, $method);

    if ($ins->execute()) {

        $_SESSION["bank_demo"]["payment_id"] = $ins->insert_id;
        $_SESSION["bank_demo"]["step"]       = "email";
        $ins->close();

        bank_redirect($conn, "bank_process.php?id=" . $booking_id);

    } else {

        $ins->close();
        $message = "Payment could not be processed.";
        bank_page_start($ctx, "Payment", $message);
        bank_cancel_form($booking_id);
        bank_page_end();
        $conn->close();
        exit();
    }
}


// ---- PAPAR BORANG EMEL ----

bank_require_step($conn, $ctx, "email");


function get_account_email($conn, $customer_id)
{
    try {
        $q = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
        if ($q) {
            $q->bind_param("i", $customer_id);
            $q->execute();
            $row = $q->get_result()->fetch_assoc();
            $q->close();
            if (!empty($row["email"])) {
                return $row["email"];
            }
        }
    } catch (Throwable $e) {
        // jadual/lajur berbeza: abaikan
    }

    return $_SESSION["email"] ?? "";
}


if (($_GET["error"] ?? "") === "email") {
    $message = "Masukkan emel sebenar yang sah untuk menerima resit.";
}

if (($_GET["error"] ?? "") === "send") {

    $why = $_GET["why"] ?? "";

    $reasons = [
        "nocfgfile"  => "Fail mail_config.php tiada di hosting. Muat naik fail itu ke folder yang sama.",
        "cfgerror"   => "mail_config.php ada ralat sintaks. Semak tanda petikan dan koma.",
        "nokey"      => "API key atau emel penghantar dalam mail_config.php belum diisi.",
        "nopdflib"   => "Fail receipt_pdf.php tiada di hosting. Muat naik fail itu ke folder yang sama.",
        "pdferror"   => "Resit PDF gagal dijana. Semak error log hosting.",
        "nohttp"     => "Hosting mematikan cURL dan allow_url_fopen, jadi tak boleh sambung ke perkhidmatan emel.",
        "network"    => "Hosting tak dapat sambung ke Brevo. Hosting percuma kadang-kadang menyekat sambungan keluar.",
        "http_401"   => "Brevo menolak API key. Semak key, atau matikan sekatan di Brevo (Security > Authorised IPs).",
        "http_400"   => "Brevo menolak permintaan. Pastikan emel penghantar sudah disahkan di Brevo (Senders).",
        "http_403"   => "Brevo tak benarkan akaun ini menghantar. Semak status akaun dan emel penghantar.",
        "exception"  => "Ralat dalaman semasa menghantar. Semak error log hosting.",
        "bademail"   => "Emel tak sah."
    ];

    if (isset($reasons[$why])) {
        $message = "Resit tak dapat dihantar: " . $reasons[$why];
    } elseif (preg_match("/^http_\\d{3}$/", $why)) {
        $message = "Resit tak dapat dihantar. Brevo menjawab dengan kod " . substr($why, 5) . ".";
    } else {
        $message = "Resit tak dapat dihantar.";
    }

    $message .= " Bayaran anda sudah direkodkan. Anda boleh cuba lagi atau langkau.";
}

$prefill = get_account_email($conn, $ctx["customer_id"]);

bank_page_start($ctx, "Payment approved", $message);

?>

        <p style="margin-bottom:20px;text-align:center;">
            Payment approved. Masukkan emel sebenar anda untuk menerima resit PDF.
        </p>

        <form method="POST" action="send_receipt_email.php">

            <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
            <input type="hidden" name="action" value="send">

            <div class="form-group">
                <label for="email">Receipt email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    autocomplete="email"
                    value="<?php echo htmlspecialchars($prefill); ?>"
                    required
                >
            </div>

            <button type="submit" class="button confirm-button">Send receipt</button>

        </form>

        <form method="POST" action="send_receipt_email.php" style="margin-top:20px;text-align:center;">

            <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
            <input type="hidden" name="action" value="skip">

            <button type="submit" class="button">Skip, view receipt</button>

        </form>

<?php

bank_page_end();

$conn->close();

?>
