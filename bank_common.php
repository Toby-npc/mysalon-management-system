<?php

// =========================================
// BANK DEMO - KOD KONGSI
//
// Digunakan oleh: bank_payment.php, bank_login_demo.php,
// bank_approve.php, bank_process.php, send_receipt_email.php
//
// Bank-bank di sini ialah bank REKAAN. Tiada transaksi bank
// sebenar. Jangan guna kata laluan sebenar.
// ==========================================

const DEMO_USER     = "demo.user";
const DEMO_PASSWORD = "demo1234";


function demo_banks()
{
    return [
        "biru"   => "DemoBank Biru",
        "hijau"  => "DemoBank Hijau",
        "jingga" => "DemoBank Jingga"
    ];
}


function bank_redirect($conn, $url)
{
    $conn->close();
    header("Location: " . $url);
    exit();
}


// Halaman yang sepadan dengan setiap langkah
function bank_step_url($step, $booking_id)
{
    $pages = [
        "bank"    => "bank_payment.php",
        "login"   => "bank_login_demo.php",
        "approve" => "bank_approve.php",
        "email"   => "bank_process.php"
    ];

    return ($pages[$step] ?? "bank_payment.php") . "?id=" . (int)$booking_id;
}


// Pastikan customer berada pada langkah yang betul
function bank_require_step($conn, $ctx, $expected)
{
    if ($ctx["step"] !== $expected) {
        bank_redirect($conn, bank_step_url($ctx["step"], $ctx["booking_id"]));
    }
}


// ==========================================
// BOOT: login, booking, harga, state, semakan paid
// ==========================================

function bank_boot($conn)
{
    // ---- CUSTOMER MESTI LOGIN ----
    if (
        !isset($_SESSION["user_id"]) ||
        !isset($_SESSION["role"]) ||
        $_SESSION["role"] !== "customer"
    ) {
        bank_redirect($conn, "login.php");
    }

    $customer_id = (int)$_SESSION["user_id"];

    // ---- BOOKING ID ----
    $booking_id = $_GET["id"] ?? $_POST["booking_id"] ?? "";

    if (empty($booking_id) || !is_numeric($booking_id)) {
        bank_redirect($conn, "MySalon(MYBOOKING).php");
    }

    $booking_id = (int)$booking_id;

    // ---- BOOKING (milik customer sahaja) ----
    $stmt = $conn->prepare(
        "SELECT
            bookings.booking_id,
            bookings.booking_date,
            bookings.booking_time,
            bookings.status,
            services.service_name,
            services.price,
            services.discount_percent,
            services.promotion_status
         FROM bookings
         INNER JOIN services
            ON bookings.service_id = services.service_id
         WHERE bookings.booking_id = ?
         AND bookings.customer_id = ?"
    );

    $stmt->bind_param("ii", $booking_id, $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        bank_redirect($conn, "MySalon(MYBOOKING).php");
    }

    $booking = $result->fetch_assoc();
    $stmt->close();

    // ---- HARGA DIKIRA DI SERVER ----
    $original = (float)$booking["price"];
    $discount = (float)$booking["discount_percent"];
    $promo    = ($booking["promotion_status"] === "active" && $discount > 0);

    $final_price = $original;
    if ($promo) {
        $final_price = $original - ($original * ($discount / 100));
    }
    $final_price = round($final_price, 2);

    // ---- STATE DEMO (dalam session) ----
    if (
        !isset($_SESSION["bank_demo"]) ||
        !is_array($_SESSION["bank_demo"]) ||
        ($_SESSION["bank_demo"]["booking_id"] ?? null) !== $booking_id
    ) {
        $_SESSION["bank_demo"] = [
            "booking_id" => $booking_id,
            "step"       => "bank",
            "bank"       => "",
            "payment_id" => 0
        ];
    }

    $step = $_SESSION["bank_demo"]["step"];

    // ---- PAYMENT SEDIA ADA ----
    $chk = $conn->prepare(
        "SELECT payment_id, payment_status
         FROM payments
         WHERE booking_id = ?
         ORDER BY payment_id DESC
         LIMIT 1"
    );
    $chk->bind_param("i", $booking_id);
    $chk->execute();
    $existing = $chk->get_result()->fetch_assoc();
    $chk->close();

    $paid_now = (
        $existing &&
        $existing["payment_status"] === "paid" &&
        $step === "email" &&
        (int)$_SESSION["bank_demo"]["payment_id"] === (int)$existing["payment_id"]
    );

    // Dah dibayar sebelum ini -> terus ke resit
    if ($existing && $existing["payment_status"] === "paid" && !$paid_now) {
        unset($_SESSION["bank_demo"]);
        bank_redirect($conn, "receipt.php?id=" . (int)$existing["payment_id"]);
    }

    // Cancelled / completed tak boleh bayar
    if (
        !$paid_now &&
        ($booking["status"] === "cancelled" || $booking["status"] === "completed")
    ) {
        bank_redirect($conn, "MySalon(MYBOOKING).php?payment_error=locked");
    }

    return [
        "booking_id"  => $booking_id,
        "customer_id" => $customer_id,
        "booking"     => $booking,
        "price"       => $final_price,
        "step"        => $step
    ];
}


// ==========================================
// PAPARAN: pembuka dan penutup halaman
// ==========================================

function bank_page_start($ctx, $subtitle, $message = "")
{
    $booking = $ctx["booking"];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Banking - MySalon</title>
    <link rel="stylesheet" href="MySalon(CSS).css">
</head>
<body>

<nav>
    <div class="logo">MySalon</div>
    <ul>
        <li><a href="MySalon(HOME).php">Home</a></li>
        <li><a href="MySalon(MYBOOKING).php">My Booking</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>

<section>

    <div class="section-title">
        <h2>Online Banking</h2>
        <p><?php echo htmlspecialchars($subtitle); ?></p>
    </div>

    <div class="booking-form">

        <p style="text-align:center;margin-bottom:20px;padding:10px;background:#fff3d6;color:#6b4700;border-radius:6px;">
            Mod demo. Bank-bank ini rekaan dan bukan bank sebenar. Jangan masukkan kata laluan bank sebenar.
        </p>

        <?php if ($message !== ""): ?>
            <p style="text-align:center;margin-bottom:20px;color:#9c4444;">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>

        <h3 style="margin-bottom:10px;">
            <?php echo htmlspecialchars($booking["service_name"]); ?>
        </h3>

        <p style="margin-bottom:20px;">
            <strong>Amount:</strong>
            $<?php echo number_format($ctx["price"], 2); ?>
        </p>
<?php
}


function bank_cancel_form($booking_id)
{
    ?>
        <form method="POST" action="bank_process.php" style="margin-top:20px;text-align:center;">
            <input type="hidden" name="booking_id" value="<?php echo (int)$booking_id; ?>">
            <input type="hidden" name="action" value="cancel">
            <button type="submit" class="button">Cancel</button>
        </form>
<?php
}


function bank_page_end()
{
    ?>
    </div>

</section>

<footer>
    <p>&copy; 2026 MySalon Management System. All rights reserved.</p>
</footer>

</body>
</html>
<?php
}
