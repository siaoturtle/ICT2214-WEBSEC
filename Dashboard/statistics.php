<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: login.php");
    exit;
}

$logFile = __DIR__ . '/../ssrf_attempts.log';
$logs = file_exists($logFile) ? array_reverse(file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) : [];

$totalAttacks = count($logs);
$highRisk = count(array_filter($logs, fn($log) => strpos($log, '3 out of 3') !== false));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SSRF Attack Statistics</title>
</head>
<body>

<?php include 'navbar.php'; ?> <!-- Keep the navigation bar -->

<h2>SSRF Attack Statistics</h2>

<ul>
    <li><strong>Total SSRF Attempts:</strong> <?php echo $totalAttacks; ?></li>
    <li><strong>High-Risk Attacks (3/3 Likelihood):</strong> <?php echo $highRisk; ?></li>
</ul>

<h3>Recent SSRF Attempts</h3>
<ul>
    <?php foreach (array_slice($logs, 0, 5) as $log): ?>
        <li><?php echo htmlspecialchars($log); ?></li>
    <?php endforeach; ?>
</ul>

</body>
</html>