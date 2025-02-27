<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: login.php");
    exit;
}

// Read the log file
$logFile = __DIR__ . "/../ssrf_attempts.log";
$logs = file_exists($logFile) ? file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

$attackCounts = [];

foreach ($logs as $log) {
    preg_match('/\[(\d{4}-\d{2}-\d{2})/', $log, $matches);
    if (!empty($matches[1])) {
        $date = $matches[1];
        $attackCounts[$date] = isset($attackCounts[$date]) ? $attackCounts[$date] + 1 : 1;
    }
}

$dates = array_keys($attackCounts);
$counts = array_values($attackCounts);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SSRF Attack Trends</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h2>SSRF Attack Trends (Last 7 Days)</h2>
    <canvas id="attackChart"></canvas>

    <script>
        const ctx = document.getElementById('attackChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [{
                    label: 'SSRF Attacks',
                    data: <?php echo json_encode($counts); ?>,
                    borderColor: 'red',
                    borderWidth: 2,
                    fill: false
                }]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });
    </script>
</body>
</html>
