<?php
$host = getenv('MYSQL_HOST');
$db   = getenv('MYSQL_DATABASE');
$user = getenv('MYSQL_USER');
$pass = getenv('MYSQL_PASSWORD');
$port = getenv('MYSQL_PORT') ?: 3306;

echo "Host: $host<br>";
echo "DB: $db<br>";
echo "User: $user<br>";
echo "Port: $port<br>";

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
    echo "Conexão OK!";
} catch(Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>
