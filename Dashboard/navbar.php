<!-- navbar.php -->
<div class="navbar">
    <div class="navbar-left">
        <a href="dashboard.php">Dashboard</a>
        <a href="attack_trends.php">Attack Trends</a>
        <a href="statistics.php">Statistics</a>
        <a href="settings.php">Settings</a>
    </div>
    <div class="navbar-right">
        <a href="logout.php" class="logout">Logout</a>
    </div>
</div>

<style>
    /* Navigation Menu */
    .navbar {
        font-family: Arial, sans-serif;
        background-color: #333;
        overflow: hidden;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 40px; /* Match spacing from dashboard.php */
    }

    .navbar-left {
        display: flex;
        gap: 250px; /* Ensures equal spacing between nav items */
    }

    .navbar-right {
        display: flex;
        align-items: center;
    }

    .navbar a {
        color: white;
        padding: 10px 20px; /* Uniform padding */
        text-decoration: none;
        text-align: center;
        font-size: 16px;
    }

    .navbar a:hover {
        background-color: #575757;
    }

    .logout {
        background: red;
        padding: 10px 20px;
        border-radius: 5px;
        font-weight: bold;
    }
</style>