<?php

// ==========================================
// JANA RESIT PDF MINIMA (tanpa library)
// Satu muka surat A4, teks sahaja.
// ==========================================

function pdf_clean($text)
{
    $text = (string)$text;

    if (function_exists("iconv")) {
        $converted = @iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $text);
        if ($converted !== false) {
            $text = $converted;
        }
    }

    $text = preg_replace('/[^\x20-\x7E]/', '', $text);

    return str_replace(
        ["\\", "(", ")"],
        ["\\\\", "\\(", "\\)"],
        $text
    );
}


function build_receipt_pdf($title, $subtitle, array $rows, $total_label, $total_value, $footnote)
{
    $ops = [];

    // Tajuk
    $ops[] = "BT /F2 22 Tf 60 780 Td (" . pdf_clean($title) . ") Tj ET";
    $ops[] = "BT /F1 11 Tf 60 760 Td (" . pdf_clean($subtitle) . ") Tj ET";
    $ops[] = "0.8 g 60 745 475 1 re f 0 g";

    // Baris maklumat
    $y = 715;
    foreach ($rows as $row) {
        $ops[] = "BT /F1 11 Tf 0.4 g 60 $y Td (" . pdf_clean($row[0]) . ") Tj ET 0 g";
        $ops[] = "BT /F1 11 Tf 220 $y Td (" . pdf_clean($row[1]) . ") Tj ET";
        $y -= 24;
    }

    // Jumlah
    $y -= 6;
    $ops[] = "0.8 g 60 " . ($y + 18) . " 475 1 re f 0 g";
    $ops[] = "BT /F2 13 Tf 60 $y Td (" . pdf_clean($total_label) . ") Tj ET";
    $ops[] = "BT /F2 13 Tf 220 $y Td (" . pdf_clean($total_value) . ") Tj ET";

    // Nota kaki
    $ops[] = "BT /F1 9 Tf 0.45 g 60 60 Td (" . pdf_clean($footnote) . ") Tj ET";

    $stream = implode("\n", $ops);

    $objects = [];
    $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
    $objects[2] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
    $objects[3] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] "
                . "/Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>";
    $objects[4] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
    $objects[5] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";
    $objects[6] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";

    $pdf     = "%PDF-1.4\n";
    $offsets = [];

    foreach ($objects as $num => $body) {
        $offsets[$num] = strlen($pdf);
        $pdf .= $num . " 0 obj\n" . $body . "\nendobj\n";
    }

    $xref = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";

    foreach ($offsets as $off) {
        $pdf .= sprintf("%010d 00000 n \n", $off);
    }

    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n" . $xref . "\n%%EOF";

    return $pdf;
}



function receipt_find_autoload()
{
    $candidates = [
        __DIR__ . "/vendor/autoload.php",
        dirname(__DIR__) . "/vendor/autoload.php"
    ];

    foreach ($candidates as $file) {
        if (is_file($file)) {
            return $file;
        }
    }

    return null;
}


function receipt_make_pdf(array $data, $bank_name)
{
    $h = function ($v) {
        return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
    };

    $no      = "#" . (int)$data["payment_id"];
    $time    = date("h:i A", strtotime($data["booking_time"]));
    $paid_on = date("d M Y, h:i A", strtotime($data["payment_date"]));
    $amount  = "$" . number_format((float)$data["amount"], 2);
    $status  = ucfirst((string)$data["payment_status"]);

    // ---- 1. Dompdf ----
    $autoload = receipt_find_autoload();

    if ($autoload !== null) {

        try {

            require_once $autoload;

            if (class_exists("Dompdf\\Dompdf")) {

                $rows = [
                    ["Receipt No",        $no],
                    ["Customer",          $data["customer_name"]],
                    ["Service",           $data["service_name"]],
                    ["Appointment Date",  $data["booking_date"]],
                    ["Appointment Time",  $time],
                    ["Payment Method",    $data["payment_method"] . " (" . $bank_name . ")"],
                    ["Payment Date",      $paid_on],
                    ["Amount Paid",       $amount],
                    ["Payment Status",    $status]
                ];

                $tr = "";
                foreach ($rows as $r) {
                    $tr .= "<tr><td class='k'>" . $h($r[0]) . "</td><td>" . $h($r[1]) . "</td></tr>";
                }

                $html =
                    "<html><head><meta charset='UTF-8'><style>" .
                    "body{font-family:DejaVu Sans,Helvetica,Arial,sans-serif;font-size:13px;color:#222}" .
                    "h1{margin:0 0 4px;font-size:24px}" .
                    "p.sub{margin:0 0 24px;color:#777}" .
                    "table{width:100%;border-collapse:collapse}" .
                    "td{padding:9px 0;border-bottom:1px solid #e3d9d0}" .
                    "td.k{width:38%;color:#777}" .
                    "p.note{margin-top:28px;font-size:11px;color:#888}" .
                    "</style></head><body>" .
                    "<h1>MySalon</h1><p class='sub'>Payment Receipt</p>" .
                    "<table>" . $tr . "</table>" .
                    "<p class='note'>This is a simulated payment for the MySalon project. No real money was transferred.</p>" .
                    "</body></html>";

                $dompdf = new \Dompdf\Dompdf();
                $dompdf->loadHtml($html);
                $dompdf->setPaper("A5", "portrait");
                $dompdf->render();

                $output = $dompdf->output();

                if (is_string($output) && strlen($output) > 100) {
                    return $output;
                }
            }

        } catch (Throwable $e) {
            error_log("MySalon Dompdf gagal, guna PDF ringkas: " . $e->getMessage());
        }
    }

    // ---- 2. PDF ringkas (tanpa library) ----
    return build_receipt_pdf(
        "MySalon",
        "Payment Receipt",
        [
            ["Receipt No",       $no],
            ["Customer",         $data["customer_name"]],
            ["Service",          $data["service_name"]],
            ["Appointment Date", $data["booking_date"]],
            ["Appointment Time", $time],
            ["Payment Method",   $data["payment_method"] . " (" . $bank_name . ")"],
            ["Payment Date",     $paid_on],
            ["Payment Status",   $status]
        ],
        "Amount Paid",
        $amount,
        "This is a simulated payment for the MySalon project. No real money was transferred."
    );
}
