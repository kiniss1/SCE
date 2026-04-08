<?php
$host = getenv('MYSQL_HOST') ?: null;
$port = getenv('MYSQL_PORT') ?: '3306';
$db   = getenv('MYSQL_DATABASE') ?: null;
$user = getenv('MYSQL_USER') ?: null;
$pass = getenv('MYSQL_PASSWORD') ?: null;

if (!$host || !$db || !$user || $pass === false) {
    http_response_code(500);
    die("Erro de configuração: variáveis de ambiente do banco de dados não definidas.");
}

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    die("Erro de conexão: " . $e->getMessage());
}

// Automatically create tables on first run (safe to call on every request —
// MySQL's IF NOT EXISTS makes it a no-op once the tables already exist).
require_once __DIR__ . '/init_db.php';
try {
    initializeDatabase($pdo);
} catch (PDOException $e) {
    http_response_code(500);
    die("Erro ao inicializar banco de dados: " . $e->getMessage());
}
?>
