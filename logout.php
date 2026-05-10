<?php
session_start();
session_destroy();
$expired = isset($_GET['expired']) ? '?expired=1' : '';
header('Location: /index.php' . $expired);
exit;
?>
