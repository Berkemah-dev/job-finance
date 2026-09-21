<?php
$files = [
    'c:\\laragon\\www\\job-finance\\app\\Http\\Controllers\\ShippingInstructionController.php',
    'c:\\laragon\\www\\job-finance\\app\\Http\\Controllers\\OperationalDocumentController.php',
    'c:\\laragon\\www\\job-finance\\app\\Http\\Controllers\\JobController.php',
    'c:\\laragon\\www\\job-finance\\app\\Http\\Controllers\\InvoiceController.php',
    'c:\\laragon\\www\\job-finance\\app\\Http\\Controllers\\BookingConfirmationController.php',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $content = str_replace(
            "'Content-Disposition' => 'inline; filename=\"'..'\"'",
            "'Content-Disposition' => 'inline; filename=\"'.\$filename.'\"'",
            $content
        );
        file_put_contents($file, $content);
        echo "Fixed $file\n";
    }
}
