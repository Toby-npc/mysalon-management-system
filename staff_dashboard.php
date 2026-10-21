<!-- ======================================
     NOTIFICATIONS
======================================= -->

<div
    class="section-title"
    style="margin-top:40px;"
>

    <h2>
        Notifications
    </h2>

    <p>
        <?php echo $notifications->num_rows; ?>
        active notification(s)
    </p>

</div>


<?php if ($notifications->num_rows > 0): ?>

    <div class="booking-details" style="margin-top:30px;">

        <?php while ($notification = $notifications->fetch_assoc()): ?>

            <div class="booking-card">

                <h3>
                    <?php echo ($notification["status"] === "pending")
                        ? "New / Pending Appointment"
                        : "Confirmed Appointment"; ?>
                </h3>

                <p>
                    <strong>Service:</strong>
                    <?php echo htmlspecialchars($notification["service_name"]); ?>
                </p>

                <p>
                    <strong>Customer:</strong>
                    <?php echo htmlspecialchars($notification["customer_name"]); ?>
                </p>

                <p>
                    <strong>Date:</strong>
                    <?php echo date("d M Y", strtotime($notification["booking_date"])); ?>
                </p>

                <p>
                    <strong>Time:</strong>
                    <?php echo date("h:i A", strtotime($notification["booking_time"])); ?>
                </p>

                <p>
                    <strong>Status:</strong>
                    <?php echo ucfirst(htmlspecialchars($notification["status"])); ?>
                </p>

            </div>

        <?php endwhile; ?>

    </div>

<?php endif; ?>
