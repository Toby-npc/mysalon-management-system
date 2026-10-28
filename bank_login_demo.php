<?php

session_start();
include "db.php";
require_once __DIR__ . "/bank_common.php";


// ==========================================
// LANGKAH 2: LOG MASUK BANK (AKAUN DEMO SAHAJA)
// ==========================================

$ctx = bank_boot($conn);
$booking_id = $ctx["booking_id"];

$message = "";


// ---- Terima pilihan bank dari bank_payment.php ----

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["action"] ?? "") === "choose_bank" &&
    $ctx["step"] === "bank"
) {

    $bank = $_POST["bank"] ?? "";

    if (isset(demo_banks()[$bank])) {

        $_SESSION["bank_demo"]["bank"] = $bank;
        $_SESSION["bank_demo"]["step"] = "login";
        $ctx["step"] = "login";

    } else {

        bank_redirect(
            $conn,
            "bank_payment.php?id=" . $booking_id . "&error=bank"
        );
    }
}


bank_require_step($conn, $ctx, "login");


// ---- Semak log masuk ----

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["action"] ?? "") === "login"
) {

    $uid = trim($_POST["uid"] ?? "");
    $pw  = $_POST["pw"] ?? "";

    if (hash_equals(DEMO_USER, $uid) && hash_equals(DEMO_PASSWORD, $pw)) {

        $_SESSION["bank_demo"]["step"] = "approve";

        bank_redirect(
            $conn,
            "bank_approve.php?id=" . $booking_id
        );

    } else {

        $message = "ID atau kata laluan salah. Gunakan akaun demo yang tertera.";
    }
}


$bank_name = demo_banks()[$_SESSION["bank_demo"]["bank"]] ?? "DemoBank";

bank_page_start($ctx, $bank_name, $message);

?>

        <p style="margin-bottom:15px;font-size:.9em;">
            Akaun demo: <strong><?php echo DEMO_USER; ?></strong> /
            <strong><?php echo DEMO_PASSWORD; ?></strong>
        </p>

        <form method="POST" action="bank_login_demo.php">

            <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
            <input type="hidden" name="action" value="login">

            <div class="form-group">
                <label for="uid">User ID</label>
                <input type="text" id="uid" name="uid" autocomplete="off" required>
            </div>

            <div class="form-group">
                <label for="pw">Password</label>
                <input type="password" id="pw" name="pw" autocomplete="off" required>
            </div>

            <button type="submit" class="button confirm-button">Log in</button>

        </form>

<?php

bank_cancel_form($booking_id);
bank_page_end();

$conn->close();

?>
