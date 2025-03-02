<!-- nav.php -->
<div class="nav">
    <a href="dashboard.php">Dashboard</a>
    <a href="attack_trends.php">Attack Trends</a>
    <a href="statistics.php">Statistics</a>
    <a href="settings.php">Settings</a>
    <a href="logout.php" class="logout">Logout</a>
</div>

<style>
    .nav {
        background: #333;
        padding: 10px;
        text-align: center;
    }
    .nav a {
        color: white;
        margin: 0 15px;
        text-decoration: none;
        font-weight: bold;
    }
    .logout {
        float: right;
        background: red;
        padding: 8px;
        border-radius: 5px;
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
</style>