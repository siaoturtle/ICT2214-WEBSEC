<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: login.php");
    exit;
}

$settingsFile = __DIR__ . "/../settings.json";

// Load existing settings
$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [
    "log_retention" => 30,
    "alert_threshold" => 3
];

// Update settings if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings['log_retention'] = intval($_POST['log_retention']);
    $settings['alert_threshold'] = intval($_POST['alert_threshold']);
    file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SSRF Settings</title>
</head>
<body>
    <?php include 'navbar.php'; ?> <!-- Include the navigation bar -->
    <h2>Admin Settings</h2>
    <form method="POST">
        <label>Log Retention (Days):</label>
        <input type="number" name="log_retention" value="<?php echo $settings['log_retention']; ?>">
        <br>

        <label>SSRF Alert Threshold (Likelihood Level):</label>
        <input type="number" name="alert_threshold" min="1" max="3" value="<?php echo $settings['alert_threshold']; ?>">
        <br>

        <input type="submit" value="Save Settings">
    </form>
</body>
</html>