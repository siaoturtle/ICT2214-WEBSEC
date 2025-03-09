                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             contact.php                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 <?php
session_start();
$loggedIn = isset($_SESSION['authenticated']);

require_once __DIR__ . '/logger.php'; 

$response = [];
$ssrfLikelihood = 0;

// Define known SSRF targets (honeypot endpoints)
$ssrfTargets = [
    'http://localhost',
    'http://127.0.0.1',
    'http://169.254.169.254', // AWS Metadata
    'http://metadata.google.internal', // Google Cloud Metadata
    'file://',
    'gopher://',
    'ftp://',
    '/etc/passwd',
    'robots.txt' // Testing retrieval of disallowed files
];

// Process POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = $_POST['url'] ?? '';

    if (!empty($url)) {
        logRequest($url, 'URL_REQUEST_POST');

        // Validate the URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $response = [
                'status' => 'error',
                'message' => 'Message Sent!'
            ];
        } else {
            // Analyze likelihood of SSRF
            foreach ($ssrfTargets as $target) {
                if (stripos($url, $target) !== false) {
                    $ssrfLikelihood++;
                }
            }

            // Simulate responses
            if (stripos($url, 'robots.txt') !== false) {
                $responseData = "User-agent: *\nDisallow: /admin/\nDisallow: /private/";
            } elseif (stripos($url, 'metadata.google.internal') !== false) {
                $responseData = "Metadata-Flavor: Google\ncomputeMetadata: true";
            } elseif (stripos($url, '/etc/passwd') !== false) {
                $responseData = "root:x:0:0:root:/root:/bin/bash\nwww-data:x:33:33:www-data:/var/www:/usr/sbin/nologin";
            } else {
                $responseData = "Response from: " . htmlspecialchars($url);
            }

            $response = [
                'status' => 'success',
                'message' => "Message Sent! Fetched URL: " . htmlspecialchars($url),
                'likelihood' => $ssrfLikelihood,
                'data' => $responseData
            ];
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - ShopEase</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>

        .header-container { display: flex; justify-content: space-between; align-items: center; }
        .header { background: #ffffff; padding: 1rem 0; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; }
        .logo { font-weight: 600; font-size: 1.8rem; color: #333; }
        .nav a { margin-left: 20px; text-decoration: none; color: #555; font-weight: 400; transition: color 0.3s; }
        .nav a:hover { color: #007bff; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; color: #333; line-height: 1.6; overflow-x: hidden; }
        .container { width: 90%; max-width: 1200px; margin: 0 auto; }
        .contact-hero { background: url('https://source.unsplash.com/1600x500/?contact,desk') center/cover; color: white; text-align: center; padding: 80px 20px; position: relative; z-index: 1; }
        .contact-hero::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.6); z-index: -1; }
        .contact-hero h2 { font-size: 2.5rem; margin-bottom: 0.5rem; }
        .contact-hero p { font-size: 1.2rem; max-width: 700px; margin: 0 auto; }
        .contact-form-section { background: #ffffff; padding: 60px 20px; text-align: center; }
        .contact-form-section h3 { font-size: 2rem; margin-bottom: 2rem; }
        .contact-form { max-width: 600px; margin: 0 auto; text-align: left; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; font-size: 1rem; border: 1px solid #ccc; border-radius: 5px; }
        .form-group textarea { resize: vertical; height: 120px; }
        .cta-button { display: inline-block; padding: 12px 30px; background-color: #007bff; color: white; border: none; font-size: 1rem; cursor: pointer; border-radius: 5px; transition: background-color 0.3s; }
        .cta-button:hover { background-color: #0056b3; }
        .footer { background: #333; color: white; text-align: center; padding: 1rem 0; }
        .footer p { margin-bottom: 0.5rem; }
        .footer-nav a { color: #bbb; margin: 0 10px; text-decoration: none; font-size: 0.9rem; }
        .footer-nav a:hover { color: white; }

        #message {
            margin-bottom: 1rem;
            font-size: 1.2rem;
            color: green;
            display: none;
        }
    </style>

    <script>
        function checkURL() {
            // Get the form element
            const form = document.getElementById('contactForm');
            // Create FormData object from the form inputs
            const formData = new FormData(form);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const messageBox = document.getElementById('message');

                if (data.status === 'success') {
                    messageBox.style.display = 'block';
                    messageBox.textContent = "Message has been sent!";
                    form.reset();
                } else {
                    messageBox.style.display = 'block';
                    messageBox.textContent = data.message;
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>

</head>
<body>

<header class="header">
    <div class="container header-container">
        <h1 class="logo">ShopEase</h1>
        <nav class="nav">
            <a href="honeypot.php">Home</a>
        </nav>
    </div>
</header>

<section class="contact-hero">
    <div class="container">
        <h2>Contact Us</h2>
        <p>Have questions? Reach out to us and our team will get back to you as soon as possible.</p>
    </div>
</section>

<section class="contact-form-section">
    <div class="container">
        <h3>Get in Touch</h3>
        <div id="message"></div>
        <form id="contactForm" onsubmit="event.preventDefault(); checkURL();" method="POST" class="contact-form">
            <div class="form-group">
                <label for="url">Name</label>
                <input type="text" id="url" name="url" required placeholder="Your Name">
            </div>

            <div class="form-group">
                <label for="url">Email</label>
                <input type="text" id="url" name="url" required placeholder="Your Email">
            </div>

            <div class="form-group">
                <label for="url">Message</label>
                <textarea id="url" name="url" required placeholder="Your Message"></textarea>
            </div>

            <button type="submit" class="cta-button">Send Message</button>
        </form>
    </div>
</section>

<footer class="footer">
    <div class="container">
        <p>&copy; 2025 ShopEase HoneySSRF - All rights reserved.</p>
        <nav class="footer-nav">
            <a href="#">Privacy Policy</a>
            <a href="#">Terms of Service</a>
        </nav>
    </div>
</footer>

</body>
</html>
