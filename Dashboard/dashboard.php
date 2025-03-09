<?php
// dashboard.php - Adding Navigation Menu
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    session_regenerate_id(true);
}

// Redirect to login if not authenticated
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSRF Log Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            overflow-x: hidden;
        }

        /* Navigation Menu */
        .navbar {
            background-color: #333;
            overflow: hidden;
            display: flex;
            justify-content: space-between;
            padding: 10px 15px;
        }

        .navbar a {
            color: white;
            padding: 12px;
            text-decoration: none;
            text-align: center;
        }

        .navbar a:hover {
            background-color: #575757;
        }

        .navbar-right {
            display: flex;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
        }

        .logout-container button {
            background-color: red;
            color: white;
            padding: 8px 15px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            border-radius: 5px;
        }

        .logout-container button:hover {
            background-color: darkred;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
            vertical-align: top;
            white-space: normal;
        }

        th { background-color: #f2f2f2; }

        /* Highlight rows based on risk */
        .low { background-color: #fff3cd; }
        .medium { background-color: #ffeb99; }
        .high { background-color: #f8d7da; }
        .safe { background-color: #d4edda; }

        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            max-width: 100%;
            overflow-x: hidden;
        }

        /* Filter section */
        .filter-container {
            margin-bottom: 15px;
        }
        .filter-container label {
            margin-right: 10px;
        }
    </style>
</head>
<body>

    <!-- Navigation Menu -->
    <div class="navbar">
        <a href="dashboard.php">Dashboard</a>
        <a href="attack_trends.php">Attack Trends</a>
        <a href="statistics.php">Statistics</a>
        <a href="settings.php">Settings</a>
        <div class="navbar-right">
            <a href="logout.php" style="background-color: red;">Logout</a>
        </div>
    </div>

    <!-- Page Header -->
    <div class="header">
        <h2>SSRF Log Dashboard</h2>
    </div>

    <div>
        <button id="prevPage">Previous</button>
        <span id="currentPage">Page 1</span>
        <button id="nextPage">Next</button>
    </div>


<!-- Filter Section -->
    <div class="filter-container">
        <label>Filter by Likelihood:</label>
        <input type="checkbox" id="filter0" value="0" checked> <label for="filter0">Non-Vulnerable (0 out of 3)</label>
        <input type="checkbox" id="filter1" value="1" checked> <label for="filter1">1 out of 3</label>
        <input type="checkbox" id="filter2" value="2" checked> <label for="filter2">2 out of 3</label>
        <input type="checkbox" id="filter3" value="3" checked> <label for="filter3">3 out of 3</label>
        <button onclick="fetchLogs()">Apply Filter</button>
    </div>

    <table id="logTable">
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>Full Request</th>
                <th>SSRF Likelihood</th>
            </tr>
        </thead>
        <tbody id="logBody">
            <!-- Logs will be inserted here dynamically -->
        </tbody>
    </table>


    <script>
        let currentPage = 1;
        let totalPages = 1;
        //const logsPerPage = 30; // Number of logs to display per page

        document.getElementById('prevPage').addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                fetchLogs(currentPage);

            }
        });
        document.getElementById('nextPage').addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                fetchLogs(currentPage);

            }
        });
        document.addEventListener("DOMContentLoaded", function() {
            fetchLogs(currentPage);
        });

        function fetchLogs(page=1) {
            fetch(`getLogs.php?page=${page}`)
                .then(response => response.json())
                .then(data => {
                    totalPages = data.totalPages; // Store total pages
                    currentPage = data.currentPage; // Update current page

                    const logBody = document.getElementById('logBody');
                    logBody.innerHTML = '';

                    const selectedFilters = [];
                    if (document.getElementById('filter0').checked) selectedFilters.push(0);
                    if (document.getElementById('filter1').checked) selectedFilters.push(1);
                    if (document.getElementById('filter2').checked) selectedFilters.push(2);
                    if (document.getElementById('filter3').checked) selectedFilters.push(3);

                    data.logs.forEach(log => {
                        const likelihood = parseInt(log.ssrf_likelihood);

                        if (!selectedFilters.includes(likelihood)) return;

                        const row = document.createElement('tr');
                        if (likelihood === 3) {
                            row.classList.add("high");
                        } else if (likelihood === 2) {
                            row.classList.add("medium");
                        } else if (likelihood === 1) {
                            row.classList.add("low");
                        } else {
                            row.classList.add("safe");
                        }

                        row.innerHTML = `
                            <td>${log.timestamp}</td>
                            <td><pre>${log.request}</pre></td>
                            <td>${log.ssrf_likelihood}</td>
                        `;
                        logBody.appendChild(row);
                    });
                    updatePaginationControls(currentPage, totalPages); // Update pagination controls
                })
                .catch(error => console.error('Error fetching logs:', error));
        }

        document.addEventListener("DOMContentLoaded", function() {
            setInterval(() => fetchLogs(currentPage), 5000);
            fetchLogs(currentPage);
        });

        function updatePaginationControls(current, total) {
            document.getElementById('currentPage').textContent = `Page ${current} of ${total}`;

            document.getElementById('prevPage').disabled = (current === 1);
            document.getElementById('nextPage').disabled = (current === total);

            console.log(`Updated pagination: Page ${current} of ${total}`);
        }

    </script>
</body>
</html>