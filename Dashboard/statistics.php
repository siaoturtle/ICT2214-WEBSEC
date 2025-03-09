<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: login.php");
    exit;
}

// Load logs
$logFile = __DIR__ . '/../ssrf_attempts.log';
$logs = file_exists($logFile) ? array_reverse(file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) : [];

// Initialize stats
$totalAttacks = count($logs);
$highRisk = $mediumRisk = $lowRisk = $noRisk = 0;
$attacksPerDay = []; // for attack trends graph
$targetedEndpoints = []; // for most attacked endpoints graph

// Process logs for insights
foreach ($logs as $log) {
    if (preg_match('/\[(.*?)\]/', $log, $match)) {
        $timestamp = strtotime($match[1]);
        if ($timestamp) {
            $day = date('l', $timestamp);
            $attacksPerDay[$day] = ($attacksPerDay[$day] ?? 0) + 1;
        }
    }

    if (strpos($log, 'ssrf_likelihood: 3') !== false) {
        $highRisk++;
    } elseif (strpos($log, 'ssrf_likelihood: 2') !== false) {
        $mediumRisk++;
    } elseif (strpos($log, 'ssrf_likelihood: 1') !== false) {
        $lowRisk++;
    } elseif (strpos($log, 'ssrf_likelihood: 0') !== false) {
        $noRisk++;
    }

    // Extract attacked endpoints (More Flexible Regex)
    if (preg_match('/(Requested URL|Target URL|SSRF Attempt):\s*(\S+)/i', $log, $match)) {
        $endpoint = trim($match[2]); // Get the extracted URL
        $targetedEndpoints[$endpoint] = ($targetedEndpoints[$endpoint] ?? 0) + 1;
    }
}




// Sort endpoints by most attacked
arsort($targetedEndpoints);

// Ensure Chart.js receives at least dummy data if empty
if (empty($targetedEndpoints)) {
    $targetedEndpoints = ['No Data' => 1];
}

// Prepare data for charts
$daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$attackCountsByDay = json_encode(array_map(fn($day) => $attacksPerDay[$day] ?? 0, $daysOfWeek));
$topEndpoints = array_slice($targetedEndpoints, 0, 5, true);
$endpointLabels = json_encode(array_keys($topEndpoints));
$endpointCounts = json_encode(array_values($topEndpoints));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSRF Statistics</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .container {
            display: flex;
            flex-direction: column; /* Stack elements vertically */
            align-items: center; /* Center-align both the legend and chart */
            width: 80%;
            max-width: 1000px;
            margin: auto;
        }

        .stats-container {
            width: 100%; /* Increase width for better visibility */
            display: flex;
            flex-direction: column;
            align-items: center; /* Center items */
            gap: 10px; /* Adds spacing between the boxes */
        }

        .chart-container {
            width: 100%;
            max-width: 800px; /* Limit max width to prevent overflow */
            margin-top: 100;
        }

        .stats-box {
            width: 80%; /* Ensures alignment with the chart */
            max-width: 800px; /* Adjust for better responsiveness */
            padding: 15px;
            font-size: 22px;
            font-weight: bold;
            text-align: center;
            border-radius: 5px;
        }

        .high { background-color: #f8d7da; }
        .medium { background-color: #ffeb99; }
        .low { background-color: #fff3cd; }
        .safe { background-color: #d4edda; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container">
        <div class="stats-container">
            <h2>SSRF Likelihood Statistics</h2>
            <div class="stats-box high">High Risk: <span id="highRisk">0</span></div>
            <div class="stats-box medium">Medium Risk: <span id="mediumRisk">0</span></div>
            <div class="stats-box low">Low Risk: <span id="lowRisk">0</span></div>
            <div class="stats-box safe">Non-Vulnerable: <span id="noRisk">0</span></div>
        </div>
        <div class="chart-container">
            <canvas id="ssrfChart"></canvas>
        </div>
    </div>
    <canvas id="ssrfChart"></canvas>

    <script>
        function fetchStatistics() {
            fetch('getLogs.php')
                .then(response => response.json())
                .then(data => {
                    // Update statistics dynamically
                    document.getElementById('highRisk').textContent = data.ssrfCounts['High'];
                    document.getElementById('mediumRisk').textContent = data.ssrfCounts['Medium'];
                    document.getElementById('lowRisk').textContent = data.ssrfCounts['Low'];
                    document.getElementById('noRisk').textContent = data.ssrfCounts['Non-Vulnerable'];

                    // Update Chart.js graph with new data
                    updateSSRFChart(data.ssrfCounts);
                })
                .catch(error => console.error('Error fetching statistics:', error));
        }

        // Chart.js instance variable (global)
        let ssrfChart;

        function updateSSRFChart(ssrfCounts) {
            if (ssrfChart) {
                // If chart exists, update its data dynamically
                ssrfChart.data.datasets[0].data = [
                    ssrfCounts['High'],
                    ssrfCounts['Medium'],
                    ssrfCounts['Low'],
                    ssrfCounts['Non-Vulnerable']
                ];
                ssrfChart.update(); // Refresh chart
            } else {
                // Create chart if it doesn't exist
                const ctx = document.getElementById('ssrfChart').getContext('2d');
                ssrfChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['High', 'Medium', 'Low', 'Non-Vulnerable'],
                        datasets: [{
                            label: 'SSRF Likelihood Count',
                            data: [
                                ssrfCounts['High'],
                                ssrfCounts['Medium'],
                                ssrfCounts['Low'],
                                ssrfCounts['Non-Vulnerable']
                            ],
                            backgroundColor: ['#f8d7da', '#ffeb99', '#fff3cd', '#d4edda']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false, // Allow flexibility in size

                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            }
        }

        // Fetch statistics on page load
        document.addEventListener("DOMContentLoaded", function () {
            fetchStatistics(); // Initial fetch
            setInterval(fetchStatistics, 5000); // Auto-refresh every 5 seconds
        });
    </script>
</body>
</html>