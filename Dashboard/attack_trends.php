<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: login.php");
    exit;
}

$logFile = __DIR__ . '/../ssrf_attempts.log';
$logs = file_exists($logFile) ? array_reverse(file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) : [];

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SSRF Attack Trends</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php include 'navbar.php'; ?> <!-- Keep the navigation bar -->

<h2>SSRF Attack Trends</h2>
<canvas id="heatmap"></canvas>

<script>
    const ctx = document.getElementById('heatmap').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_keys($topEndpoints)); ?>,
            datasets: [{
                label: 'Attack Count',
                data: <?php echo json_encode(array_values($topEndpoints)); ?>,
                backgroundColor: 'red'
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true } }
        }
    });
</script>

</body>
</html>