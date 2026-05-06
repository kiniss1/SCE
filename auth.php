<?php
session_start();

define('AUTH_USER', 'gem');
define('AUTH_PASS', 'gem123');
define('AUTH_SESSION_KEY', 'gem_autenticado');

function requerLogin() {
    if (empty($_SESSION[AUTH_SESSION_KEY])) {
        // Evitar loop: só redirecionar se não estiver já no login
        $current = $_SERVER['PHP_SELF'] ?? '';
        if (strpos($current, 'login.php') === false) {
            header('Location: /login.php');
            exit;
        }
    }
}
?>
