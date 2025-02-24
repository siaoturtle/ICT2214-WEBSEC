<?php
// honeypot.php
// A simple SSRF honeypot page using a common logger

session_start();

$loggedIn = isset($_SESSION['authenticated']);

require_once __DIR__ . '/logger.php';
// ^ Adjust the path if logger.php is in a subdirectory

// Process form submission (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = $_POST['url'] ?? '';

    if (!empty($url)) {
        // Log the attempt
        logRequest($url, 'URL_REQUEST_POST');

        // Basic URL validation
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $message = "Request logged for URL: " . htmlspecialchars($url);
        } else {
            $message = "Invalid URL format";
        }
    }
}

// Process direct GET parameter (e.g. ?url=...)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['url'])) {
    $url = $_GET['url'];
    logRequest($url, 'URL_REQUEST_GET');
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>URL Checker</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            cursor: pointer;
        }
        .message {
            margin-top: 15px;
            padding: 10px;
            background-color: #f8f8f8;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <h1>URL Checker</h1>

    <form method="POST" action="">
        <div class="form-group">
            <label for="url">Enter URL to check:</label>
            <input type="text" id="url" name="url" required>
        </div>
        <input type="submit" value="Check URL">
    </form>

    <?php if (isset($message)): ?>
    <div class="message">
        <?php echo $message; ?>
    </div>
    <?php endif; ?>
</body>
</html>