<?php

session_start();
include "db.php";


// ==========================================
// ADMIN SAHAJA
// ==========================================

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: login.php");
    exit();
}


// ==========================================
// FILTER INPUTS
// ==========================================

$search        = trim($_GET["search"] ?? "");
$filter_date   = $_GET["date"] ?? "";
$filter_status = $_GET["status"] ?? "";

$allowed_status = ["pending", "confirmed", "completed", "cancelled"];

if (
    $filter_status !== "" &&
    !in_array($filter_status, $allowed_status, true)
) {
    $filter_status = "";
}

$currency = "$"; // tukar kepada "RM" jika perlu


// ==========================================
// QUERY
// ==========================================

$sql = "
    SELECT
        bookings.booking_id,
        bookings.customer_id,
        bookings.booking_date,
        bookings.booking_time,
        bookings.status,
        bookings.created_at,

        customers.name  AS customer_name,
        customers.email AS customer_email,

        services.service_name,
        services.price,
        services.discount_percent,
        services.promotion_status,

        staff.name AS staff_name,

        (
            SELECT payments.payment_status
            FROM payments
            WHERE payments.booking_id = bookings.booking_id
            ORDER BY payments.payment_id DESC
            LIMIT 1
        ) AS payment_status,

        (
            SELECT payments.payment_method
            FROM payments
            WHERE payments.booking_id = bookings.booking_id
            ORDER BY payments.payment_id DESC
            LIMIT 1
        ) AS payment_method,

        (
            SELECT payments.amount
            FROM payments
            WHERE payments.booking_id = bookings.booking_id
            AND payments.payment_status = 'paid'
            ORDER BY payments.payment_id DESC
            LIMIT 1
        ) AS paid_amount

    FROM bookings

    INNER JOIN users AS customers
        ON bookings.customer_id = customers.user_id

    INNER JOIN services
        ON bookings.service_id = services.service_id

    LEFT JOIN users AS staff
        ON bookings.staff_id = staff.user_id

    WHERE 1 = 1
";

$params = [];
$types  = "";


// ---- SEARCH (ID, nama, email, service, staff, status) ----

if ($search !== "") {

    $sql .= "
        AND (
            CAST(bookings.customer_id AS CHAR) LIKE ?
            OR CAST(bookings.booking_id AS CHAR) LIKE ?
            OR customers.name LIKE ?
            OR customers.email LIKE ?
            OR services.service_name LIKE ?
            OR staff.name LIKE ?
            OR bookings.status LIKE ?
            OR (
                SELECT p.payment_status
                FROM payments AS p
                WHERE p.booking_id = bookings.booking_id
                ORDER BY p.payment_id DESC
                LIMIT 1
            ) LIKE ?
        )
    ";

    $like = "%" . $search . "%";

    for ($i = 0; $i < 8; $i++) {
        $params[] = $like;
        $types   .= "s";
    }
}


// ---- DATE ----

if ($filter_date !== "") {
    $sql     .= " AND bookings.booking_date = ? ";
    $params[] = $filter_date;
    $types   .= "s";
}


// ---- STATUS ----

if ($filter_status !== "") {
    $sql     .= " AND bookings.status = ? ";
    $params[] = $filter_status;
    $types   .= "s";
}


$sql .= "
    ORDER BY
        bookings.booking_date DESC,
        bookings.booking_time DESC,
        bookings.booking_id DESC
";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$records = $stmt->get_result();

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Booking Records - MySalon</title>

    <link rel="stylesheet" href="MySalon(CSS).css">

    <style>

        /* Filter box: full width, same gray-green look as .booking-form */

        .booking-form.records-box {
            max-width: none;
            margin: 0;
            padding: 30px 34px;
        }

        .records-filter {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 16px;
        }

        .records-filter .form-group {
            margin: 0;
        }

        .records-filter .search-group {
            flex: 1 1 300px;
        }

        .records-filter .button {
            padding: 13px 26px;
        }


        /* Table: matches the booking cards */

        .records-table-wrap {
            overflow-x: auto;
            background: rgba(35, 58, 52, 0.88);
            border: 1px solid var(--line-dark);
            border-radius: 4px;
            backdrop-filter: blur(6px);
            box-shadow: 0 12px 30px -15px rgba(28, 43, 39, 0.5);
        }

        .records-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            color: #dbe4df;
        }

        .records-table th,
        .records-table td {
            padding: 14px 16px;
            text-align: left;
            vertical-align: top;
            white-space: nowrap;
            border-bottom: 1px solid var(--line-dark);
        }

        .records-table th {
            background: rgba(24, 36, 32, 0.92);
            color: #b7c4bd;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 11.5px;
            font-weight: 500;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .records-table tbody tr:last-child td {
            border-bottom: none;
        }

        .records-table tbody tr:hover {
            background: rgba(191, 122, 74, 0.12);
        }

        .records-table .strike {
            text-decoration: line-through;
            opacity: 0.6;
        }

        .records-table small {
            display: block;
            color: #b7c4bd;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 11px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 20px;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 11.5px;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            border: 1px solid var(--line-dark);
            background: rgba(24, 36, 32, 0.9);
            color: #dbe4df;
        }

        .badge-paid,
        .badge-completed {
            background: rgba(124, 148, 130, 0.35);
            border-color: var(--sage);
        }

        .badge-pending {
            background: rgba(191, 122, 74, 0.25);
            border-color: var(--copper);
            color: #e3a77a;
        }

        .badge-confirmed {
            background: rgba(124, 148, 130, 0.18);
            border-color: var(--sage);
        }

        .badge-failed,
        .badge-cancelled {
            background: rgba(156, 68, 68, 0.3);
            border-color: #e8a3a3;
            color: #e8a3a3;
        }

        .no-records {
            text-align: center !important;
            padding: 40px !important;
            color: #b7c4bd;
        }

    </style>

</head>


<body>


<!-- ==========================================
     NAVBAR
=========================================== -->

<nav>

    <div class="mysalon-brand">

        <div
            class="mysalon-logo-trigger"
            id="mysalonLogoTrigger"
            role="button"
            tabindex="0"
            aria-label="Open menu"
            aria-expanded="false"
        >
            <img src="asset/Salon3.png" alt="MySalon Logo">
        </div>

        <div class="logo">MySalon Admin</div>

        <div class="mysalon-dropdown" id="mysalonDropdown">

            <a href="admin.php">Appointments</a>
            <a href="admin_staff.php">Staff</a>
            <a href="admin_services.php">Services</a>
            <a href="admin_inventory.php">Inventory</a>
            <a href="admin_payments.php">Payments</a>
            <a href="admin_customers.php">Customers</a>
            <a href="admin_schedule.php">Schedule</a>
            <a href="admin_records.php">Records</a>

            <div class="mysalon-dropdown-divider"></div>

            <div class="mysalon-dropdown-user">
                Hi, <?php echo htmlspecialchars($_SESSION["name"]); ?>
            </div>

            <a href="logout.php" class="mysalon-dropdown-logout">
                Logout
            </a>

        </div>

    </div>

    <ul>
        <li>
            <span>Hi, <?php echo htmlspecialchars($_SESSION["name"]); ?></span>
        </li>
        <li>
            <a href="logout.php">Logout</a>
        </li>
    </ul>

</nav>


<!-- ==========================================
     BOOKING RECORDS
=========================================== -->

<section>

    <div class="section-title">
        <h2>Booking Records</h2>
        <p>Search and track customer booking history</p>
    </div>


    <!-- ======================================
         FILTER
    ======================================= -->

    <div class="booking-form records-box">

        <form method="GET" action="admin_records.php" class="records-filter">

            <div class="form-group search-group">
                <label for="search">Filter</label>
                <input
                    type="text"
                    id="search"
                    name="search"
                    placeholder="Customer ID, name, email, service, staff..."
                    value="<?php echo htmlspecialchars($search); ?>"
                >
            </div>

            <div class="form-group">
                <label for="date">Date</label>
                <input
                    type="date"
                    id="date"
                    name="date"
                    value="<?php echo htmlspecialchars($filter_date); ?>"
                >
            </div>

            <div class="form-group">
                <label for="status">Booking Status</label>
                <select id="status" name="status">
                    <option value="">All Status</option>
                    <?php foreach ($allowed_status as $status): ?>
                        <option
                            value="<?php echo $status; ?>"
                            <?php echo ($filter_status === $status) ? "selected" : ""; ?>
                        >
                            <?php echo ucfirst($status); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="button">Apply Filter</button>
            <a href="admin_records.php" class="button">Clear</a>

        </form>

    </div>


    <!-- ======================================
         RESULT TABLE
    ======================================= -->

    <div class="section-title" style="margin-top:40px;">
        <h2>Appointment History</h2>
        <p><?php echo $records->num_rows; ?> record(s) found</p>
    </div>


    <div class="records-table-wrap">

        <table class="records-table">

            <thead>
                <tr>
                    <th>Booking ID</th>
                    <th>Customer ID</th>
                    <th>Customer Name</th>
                    <th>Email</th>
                    <th>Service</th>
                    <th>Staff</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Original Price</th>
                    <th>Amount</th>
                    <th>Booking Status</th>
                    <th>Payment Status</th>
                    <th>Payment Method</th>
                    <th>Created</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($records->num_rows > 0): ?>

                <?php while ($record = $records->fetch_assoc()): ?>

                    <?php

                    // ---- harga semasa ----
                    $original_price = (float)$record["price"];
                    $discount       = (float)$record["discount_percent"];

                    $promotion_active = (
                        $record["promotion_status"] === "active" &&
                        $discount > 0
                    );

                    $current_price = $original_price;

                    if ($promotion_active) {
                        $current_price = round(
                            $original_price - ($original_price * ($discount / 100)),
                            2
                        );
                    }

                    // ---- payment ----
                    $is_paid = (
                        $record["payment_status"] === "paid" &&
                        $record["paid_amount"] !== null
                    );

                    $payment_label = !empty($record["payment_status"])
                        ? $record["payment_status"]
                        : "not paid";

                    $payment_class = preg_replace(
                        "/[^a-z]/",
                        "",
                        strtolower($payment_label)
                    );

                    $status_class = preg_replace(
                        "/[^a-z]/",
                        "",
                        strtolower($record["status"])
                    );

                    ?>

                    <tr>

                        <td>#<?php echo (int)$record["booking_id"]; ?></td>

                        <td><?php echo (int)$record["customer_id"]; ?></td>

                        <td><?php echo htmlspecialchars($record["customer_name"]); ?></td>

                        <td><?php echo htmlspecialchars($record["customer_email"]); ?></td>

                        <td><?php echo htmlspecialchars($record["service_name"]); ?></td>

                        <td>
                            <?php
                            echo !empty($record["staff_name"])
                                ? htmlspecialchars($record["staff_name"])
                                : "Not assigned";
                            ?>
                        </td>

                        <td>
                            <?php echo date("d M Y", strtotime($record["booking_date"])); ?>
                        </td>

                        <td>
                            <?php echo date("h:i A", strtotime($record["booking_time"])); ?>
                        </td>

                        <!-- ORIGINAL PRICE -->
                        <td>
                            <?php if (!$is_paid && $promotion_active): ?>
                                <span class="strike">
                                    <?php echo $currency . number_format($original_price, 2); ?>
                                </span>
                                <small><?php echo number_format($discount, 0); ?>% OFF</small>
                            <?php else: ?>
                                <?php echo $currency . number_format($original_price, 2); ?>
                            <?php endif; ?>
                        </td>

                        <!-- AMOUNT -->
                        <td>
                            <?php if ($is_paid): ?>
                                <?php echo $currency . number_format((float)$record["paid_amount"], 2); ?>
                                <small>paid</small>
                            <?php else: ?>
                                <?php echo $currency . number_format($current_price, 2); ?>
                                <small>due</small>
                            <?php endif; ?>
                        </td>

                        <!-- BOOKING STATUS -->
                        <td>
                            <span class="badge badge-<?php echo $status_class; ?>">
                                <?php echo ucfirst(htmlspecialchars($record["status"])); ?>
                            </span>
                        </td>

                        <!-- PAYMENT STATUS -->
                        <td>
                            <span class="badge badge-<?php echo $payment_class; ?>">
                                <?php echo ucfirst(htmlspecialchars($payment_label)); ?>
                            </span>
                        </td>

                        <!-- PAYMENT METHOD -->
                        <td>
                            <?php
                            echo !empty($record["payment_method"])
                                ? htmlspecialchars($record["payment_method"])
                                : "-";
                            ?>
                        </td>

                        <!-- CREATED -->
                        <td>
                            <?php echo date("d M Y h:i A", strtotime($record["created_at"])); ?>
                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="14" class="no-records">
                        No booking records found.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>


<!-- ==========================================
     FOOTER
=========================================== -->

<footer>
    <p>&copy; 2026 MySalon Management System. All rights reserved.</p>
</footer>


<!-- ==========================================
     LOGO DROPDOWN JAVASCRIPT
=========================================== -->

<script>

const logoTrigger = document.getElementById("mysalonLogoTrigger");
const dropdown    = document.getElementById("mysalonDropdown");

function toggleDropdown() {
    dropdown.classList.toggle("mysalon-dropdown-open");

    const isOpen = dropdown.classList.contains("mysalon-dropdown-open");
    logoTrigger.setAttribute("aria-expanded", isOpen ? "true" : "false");
}

function closeDropdown() {
    dropdown.classList.remove("mysalon-dropdown-open");
    logoTrigger.setAttribute("aria-expanded", "false");
}

logoTrigger.addEventListener("click", function (event) {
    event.stopPropagation();
    toggleDropdown();
});

logoTrigger.addEventListener("keydown", function (event) {
    if (event.key === "Enter" || event.key === " ") {
        event.preventDefault();
        event.stopPropagation();
        toggleDropdown();
    }
});

dropdown.addEventListener("click", function (event) {
    event.stopPropagation();
});

document.addEventListener("click", closeDropdown);

document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
        closeDropdown();
    }
});

</script>


</body>

</html>


<?php

$stmt->close();
$conn->close();

?>
