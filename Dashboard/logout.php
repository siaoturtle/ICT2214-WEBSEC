<?php
session_start();
session_destroy();
header("Location: ../honeypot.php");
exit;