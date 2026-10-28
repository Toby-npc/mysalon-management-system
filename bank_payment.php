<?php

session_start();
include "db.php";
require_once __DIR__ . "/bank_common.php";


// ==========================================
// LANGKAH 1: PILIH BANK DEMO
// ==========================================

$ctx = bank_boot($conn);
$booking_id = $ctx["booking_id"];


// Sudah bayar, hanya tinggal emel -> sambung di sana
if ($ctx["step"] === "email") {
    bank_redirect($conn, bank_step_url("email", $booking_id));
}


// Mula dari awal
$_SESSION["bank_demo"]["step"] = "bank";
$_SESSION["bank_demo"]["bank"] = "";


$message = "";

if (($_GET["error"] ?? "") === "bank") {
    $message = "Sila pilih bank.";
}


bank_page_start($ctx, "Choose your bank", $message);

?>

        <form method="POST" action="bank_login_demo.php">

            <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
            <input type="hidden" name="action" value="choose_bank">

            <div class="form-group">
                <label for="bank">Bank</label>
                <select id="bank" name="bank" required>
                    <option value="">Choose bank</option>
                    <?php foreach (demo_banks() as $key => $name): ?>
                        <option value="<?php echo htmlspecialchars($key); ?>">
                            <?php echo htmlspecialchars($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="button confirm-button">Continue</button>

        </form>

<?php

bank_cancel_form($booking_id);
bank_page_end();

$conn->close();

?>
