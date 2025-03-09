<?php
session_start();
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: login.php");
    exit;
}

$settingsFile = __DIR__ . "/../settings.json";

// Load existing settings or set defaults
$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [
    "log_retention" => 30,
    "alert_threshold" => 3,
    "enable_notifications" => true,
    "notification_method" => "telegram",
    "webhook_url" => "",
    "honeypot_mode" => "log_only",
    "logging_level" => "detailed"
];

// Update settings if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings['log_retention'] = intval($_POST['log_retention']);
    $settings['alert_threshold'] = intval($_POST['alert_threshold']);
    $settings['enable_notifications'] = isset($_POST['enable_notifications']);
    $settings['notification_method'] = $_POST['notification_method'];
    $settings['webhook_url'] = trim($_POST['webhook_url']);
    $settings['honeypot_mode'] = $_POST['honeypot_mode'];
    $settings['logging_level'] = $_POST['logging_level'];

    file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SSRF Settings</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script>
        function toggleWebhookField() {
            let method = document.getElementById("notification_method").value;
            document.getElementById("webhook_section").style.display = (method === "webhook") ? "block" : "none";
        }
    </script>
</head>
<body class="bg-light">

<?php include 'navbar.php'; ?> <!-- Include the navigation bar -->

<div class="container mt-5">
    <h2 class="text-center">⚙️ Admin Settings</h2>
    <form method="POST" class="card p-4 shadow-sm bg-white">

        <!-- Log Retention -->
        <div class="mb-3">
            <label class="form-label">Log Retention (Days):</label>
            <input type="number" class="form-control" name="log_retention" value="<?php echo $settings['log_retention']; ?>">
        </div>

        <!-- Alert Threshold -->
        <div class="mb-3">
            <label class="form-label">SSRF Alert Threshold (Likelihood Level):</label>
            <input type="number" class="form-control" name="alert_threshold" min="1" max="3" value="<?php echo $settings['alert_threshold']; ?>">
        </div>

        <!-- Enable Notifications -->
        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" id="enable_notifications" name="enable_notifications" <?php echo $settings['enable_notifications'] ? 'checked' : ''; ?>>
            <label class="form-check-label" for="enable_notifications">Enable Notifications</label>
        </div>

        <!-- Notification Method -->
        <div class="mb-3">
            <label class="form-label">Notification Method:</label>
            <select class="form-select" name="notification_method" id="notification_method" onchange="toggleWebhookField()">
                <option value="telegram" <?php echo $settings['notification_method'] === "telegram" ? 'selected' : ''; ?>>Telegram</option>
                <option value="email" <?php echo $settings['notification_method'] === "email" ? 'selected' : ''; ?>>Email</option>
                <option value="webhook" <?php echo $settings['notification_method'] === "webhook" ? 'selected' : ''; ?>>Webhook</option>
            </select>
        </div>

        <!-- Webhook URL (only if webhook is selected) -->
        <div class="mb-3" id="webhook_section" style="display: <?php echo $settings['notification_method'] === 'webhook' ? 'block' : 'none'; ?>;">
            <label class="form-label">Webhook URL:</label>
            <input type="text" class="form-control" name="webhook_url" value="<?php echo htmlspecialchars($settings['webhook_url']); ?>">
        </div>

        <!-- Honeypot Mode -->
        <div class="mb-3">
            <label class="form-label">Honeypot Mode:</label>
            <select class="form-select" name="honeypot_mode">
                <option value="log_only" <?php echo $settings['honeypot_mode'] === "log_only" ? 'selected' : ''; ?>>Log Only</option>
                <option value="block_attack" <?php echo $settings['honeypot_mode'] === "block_attack" ? 'selected' : ''; ?>>Block Attack</option>
                <option value="deceptive_response" <?php echo $settings['honeypot_mode'] === "deceptive_response" ? 'selected' : ''; ?>>Deceptive Response</option>
            </select>
        </div>

        <!-- Logging Level -->
        <div class="mb-3">
            <label class="form-label">Logging Level:</label>
            <select class="form-select" name="logging_level">
                <option value="detailed" <?php echo $settings['logging_level'] === "detailed" ? 'selected' : ''; ?>>Detailed</option>
                <option value="basic" <?php echo $settings['logging_level'] === "basic" ? 'selected' : ''; ?>>Basic</option>
                <option value="minimal" <?php echo $settings['logging_level'] === "minimal" ? 'selected' : ''; ?>>Minimal</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary w-100">Save Settings</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>