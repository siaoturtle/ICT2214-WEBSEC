<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" type="text/css" href="styles.css">
</head>
<body>
    <h1>Log Dashboard</h1>
    <!-- Button to trigger log fetching -->
    <button onclick="fetchLogs()">Get Latest Logs</button>
    <a href="logout.php">Logout</a>
    <div class="log-container" id="logContainer">
        <?php foreach ($logs as $log): ?>
            <div class="log-entry"><?php echo htmlspecialchars($log); ?></div>
        <?php endforeach; ?>
    </div>

    <script>
        // Function to fetch logs from the server when the button is clicked
        function fetchLogs() {
            fetch('getLogs.php')  // Updated URL to get logs via PHP endpoint
                .then(response => response.json())
                .then(data => {
                    const logContainer = document.getElementById('logContainer');
                    // Clear current logs
                    logContainer.innerHTML = '';
                    // Add new log entries
                    data.forEach(log => {
                        const logDiv = document.createElement('div');
                        logDiv.className = 'log-entry';
                        logDiv.textContent = log;
                        logContainer.appendChild(logDiv);
                    });
                    // Auto-scroll to the bottom
                    logContainer.scrollTop = logContainer.scrollHeight;
                })
                .catch(error => console.error('Error fetching logs:', error));
        }
    </script>
</body>
</html>