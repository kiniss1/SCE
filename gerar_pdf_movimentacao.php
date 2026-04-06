<?php
require('fpdf/fpdf.php');
include 'conexao.php'; // se necessário

$id = $_GET['id'] ?? null;

if ($id) {
    // buscar os dados no banco
    $query = $conn->prepare("SELECT * FROM movimentacao WHERE id = ?");
    $query->execute([$id]);
    $movimentacao = $query->fetch();

    if ($movimentacao) {
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'Comprovante de Movimentação de EPI', 0, 1, 'C');
        $pdf->Ln(10);

        foreach ($movimentacao as $campo => $valor) {
            $pdf->Cell(0, 10, strtoupper($campo) . ': ' . $valor, 0, 1);
        }

        $pdf->Output();
        exit;
    } else {
        echo "Movimentação não encontrada.";
    }
} else {
    echo "ID não informado.";
}
?>
