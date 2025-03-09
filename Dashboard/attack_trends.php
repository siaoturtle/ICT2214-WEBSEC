<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: login.php");
    exit;
}

$logFile = __DIR__ . '/../ssrf_attempts.log';
$logs = file_exists($logFile) ? array_reverse(file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) : [];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SSRF Attack Trends</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; }
        canvas {
            width: 90% !important;  /* Make it responsive */
            max-width: 1000px;      /* Increase max width */
            height: 500px !important; /* Increase height */
            margin: auto;
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?> <!-- Keep the navigation bar -->

<h2>📊 SSRF Attack Trends by Severity (Per Day)</h2>
<canvas id="stackedBarChart"></canvas>

<script>
    function fetchAttackTrends() {
        fetch('getLogs.php')
            .then(response => response.json())
            .then(data => {
                const attackCounts = data.attackCounts;

                // Extract data for each severity level
                const days = Object.keys(attackCounts);
                const nonVulnData = days.map(day => attackCounts[day]['Non-Vulnerable']);
                const lowData = days.map(day => attackCounts[day]['Low']);
                const mediumData = days.map(day => attackCounts[day]['Medium']);
                const highData = days.map(day => attackCounts[day]['High']);

                // Update Chart.js
                updateChart(days, nonVulnData, lowData, mediumData, highData);
            })
            .catch(error => console.error('Error fetching attack trends:', error));
    }

    let attackChart;

    function updateChart(days, nonVulnData, lowData, mediumData, highData) {
        const ctx = document.getElementById('stackedBarChart').getContext('2d');

        if (attackChart) {
            attackChart.destroy();
        }

        attackChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: days,
                datasets: [
                    { label: 'Non-Vulnerable', data: nonVulnData, backgroundColor: 'green'},
                    { label: 'Low Severity', data: lowData, backgroundColor: 'blue' },
                    { label: 'Medium Severity', data: mediumData, backgroundColor: 'yellow' },
                    { label: 'High Severity', data: highData, backgroundColor: 'red' }
                ]
            },
            options: {
                responsive: true,
                scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } }
            }
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        fetchAttackTrends();
        setInterval(fetchAttackTrends, 60000); // Update every minute
    });
</script>

</body>
</html>