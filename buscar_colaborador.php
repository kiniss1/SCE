<?php
require 'conexao.php';
header('Content-Type: application/json; charset=utf-8');

$matricula = trim($_GET['matricula'] ?? '');

if (!$matricula) {
    echo json_encode(['status' => 'error', 'mensagem' => 'Matrícula não informada']);
    exit;
}

$stmt = $pdo->prepare("SELECT matricula, nome FROM colaboradores WHERE matricula = ?");
$stmt->execute([$matricula]);
$colaborador = $stmt->fetch(PDO::FETCH_ASSOC);

if ($colaborador) {
    echo json_encode(['status' => 'ok', 'colaborador' => $colaborador]);
} else {
    echo json_encode(['status' => 'nao_encontrado']);
}
?>
