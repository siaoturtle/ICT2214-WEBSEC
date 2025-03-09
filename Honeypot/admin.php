<?php
require_once __DIR__ . '/logger.php';

logRequest('admin');

?>
<!DOCTYPE html>
<html>
  <head>
      <title>Admin Panel - SSRF Honeypot</title>
  </head>
  <body>
      <h1>Welcome to the Admin Panel</h1>
      <p>This is a fake admin interface to lure SSRF attempts.</p>
  </body>
</html>
