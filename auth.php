<?php
session_start();

// ── Credenciais (altere aqui quando quiser) ───────────────────────────
define('AUTH_USER', 'gem');
define('AUTH_PASS', 'gem123');
define('AUTH_SESSION_KEY', 'gem_autenticado');
define('LOGIN_PAGE', '/login.php');

function requerLogin() {
    if (empty($_SESSION[AUTH_SESSION_KEY])) {
        $redirect = urlencode($_SERVER['REQUEST_URI']);
        header('Location: ' . LOGIN_PAGE . '?redirect=' . $redirect);
        exit;
    }
}
?>
