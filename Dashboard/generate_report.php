<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    session_regenerate_id(true);
}
require_once __DIR__ . '/vendor/autoload.php'; // Load TCPDF
require_once __DIR__ . '/logger.php';

use TCPDF;

// Ensure authentication
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: login.php");
    exit;
}

// Load logs
$logFile = __DIR__ . "/../ssrf_attempts.log";
$logs = file_exists($logFile) ? array_reverse(file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) : [];

// Extract attack statistics
$totalAttacks = count($logs);
$highRisk = count(array_filter($logs, fn($log) => strpos($log, '3 out of 3') !== false));

// Extract most attacked endpoints
$endpointCounts = [];
foreach ($logs as $log) {
    if (preg_match('/(GET|POST) (.*?) HTTP/', $log, $match)) {
        $endpoint = $match[2];
        $endpointCounts[$endpoint] = isset($endpointCounts[$endpoint]) ? $endpointCounts[$endpoint] + 1 : 1;
    }
}
arsort($endpointCounts);
$topEndpoints = array_slice($endpointCounts, 0, 5, true);

// Create PDF
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('SSRF Honeypot');
$pdf->SetTitle('SSRF Attack Report');
$pdf->SetHeaderData('', 0, 'SSRF Attack Report', 'Generated on: ' . date("Y-m-d H:i:s"));
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
$pdf->AddPage();

// Report Header
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'SSRF Attack Report', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 12);
$pdf->Ln(5);

// Summary Section
$pdf->Cell(0, 10, "Total SSRF Attempts: $totalAttacks", 0, 1);
$pdf->Cell(0, 10, "High-Risk Attacks (3/3 Likelihood): $highRisk", 0, 1);
$pdf->Ln(5);

// Most Attacked Endpoints
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Most Attacked Endpoints:', 0, 1);
$pdf->SetFont('helvetica', '', 10);
foreach ($topEndpoints as $endpoint => $count) {
    $pdf->Cell(0, 10, "$endpoint ($count times)", 0, 1);
}
$pdf->Ln(5);

// Recent Attack Logs
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Recent SSRF Attempts:', 0, 1);
$pdf->SetFont('helvetica', '', 10);
foreach (array_slice($logs, 0, 10) as $log) {
    $pdf->MultiCell(0, 8, $log, 0, 'L');
}

// Output the PDF
$pdf->Output('SSRF_Report_' . date("Ymd_His") . '.pdf', 'D'); // 'D' forces download
?>