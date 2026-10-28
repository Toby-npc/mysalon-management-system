<?php

session_start();
include "db.php";
require_once __DIR__ . "/bank_common.php";


// ==========================================
// LANGKAH 3: APPROVE PAYMENT
// ==========================================

$ctx = bank_boot($conn);
$booking_id = $ctx["booking_id"];

bank_require_step($conn, $ctx, "approve");

$bank_name = demo_banks()[$_SESSION["bank_demo"]["bank"]] ?? "DemoBank";

bank_page_start($ctx, $bank_name);

?>

        <p style="margin-bottom:5px;">
            <strong>Pay to:</strong> MySalon
        </p>

        <p style="margin-bottom:5px;">
            <strong>From account:</strong> Savings ••• 4821 (demo)
        </p>

        <p style="margin-bottom:20px;">
            <strong>Total:</strong>
            $<?php echo number_format($ctx["price"], 2); ?>
        </p>

        <form method="POST" action="bank_process.php">

            <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
            <input type="hidden" name="action" value="approve">

            <button type="submit" class="button confirm-button">Approve Payment</button>

        </form>

<?php

bank_cancel_form($booking_id);
bank_page_end();

$conn->close();

?>
