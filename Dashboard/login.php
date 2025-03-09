<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Only start session if not already started
}

// Hardcoded credentials (Replace with a secure method)
$root_user = 'root';
$root_pass = '2214_Root#@!';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['username'] === $root_user && $_POST['password'] === $root_pass) {
        session_regenerate_id(true);
        $_SESSION['authenticated'] = true;
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>
    <h2>Login</h2>
    <?php if (isset($error)) echo "<p style='color: red;'>$error</p>"; ?>
    <form method="post">
        Username: <input type="text" name="username" required><br>
        Password: <input type="password" name="password" required><br>
        <button type="submit">Login</button>
    </form>
</body>
</html>