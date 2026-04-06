<?php
require 'conexao.php';
require('fpdf/fpdf.php'); // certifique-se que fpdf/ está presente

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) { echo "ID da ficha não informado."; exit; }

// Buscar ficha e linhas
$stmt = $pdo->prepare("SELECT f.*, c.nome AS colaborador_nome, c.matricula, c.funcao, c.area FROM fichas_colaborador f JOIN colaboradores c ON f.colaborador_id = c.id WHERE f.id = ?");
$stmt->execute([$id]);
$ficha = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$ficha) { echo "Ficha não encontrada."; exit; }

$linesStmt = $pdo->prepare("SELECT fl.*, i.nome AS item_nome, i.numero_item FROM ficha_linhas fl LEFT JOIN itens i ON fl.item_id = i.id WHERE fl.ficha_id = ? ORDER BY fl.id");
$linesStmt->execute([$id]);
$linhas = $linesStmt->fetchAll(PDO::FETCH_ASSOC);

// Criar PDF
$pdf = new FPDF('P','mm','A4');
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 12);

// Cabeçalho
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,8,'RECIBO DE ENTREGA DE EPI',0,1,'C');
$pdf->Ln(2);

// Informações do colaborador (linha superior)
$pdf->SetFont('Arial','',10);
$pdf->Cell(105,7,'Nome: ' . ($ficha['colaborador_nome'] ?? ''),1,0);
$pdf->Cell(25,7,'Matricula: ' . ($ficha['matricula'] ?? ''),1,0);
$pdf->Cell(40,7,'Funcao: ' . ($ficha['funcao'] ?? ''),1,0);
$pdf->Cell(0,7,'Area: ' . ($ficha['area'] ?? ''),1,1);

// Declaração (texto)
$pdf->SetFont('Arial','',9);
$txt = "Declaro ter recebido, sem ônus, os Equipamentos de Proteção Individual – EPI’s, abaixo especificado, e o treinamento relativo à sua utilização adequada.";
$pdf->MultiCell(0,5,$txt,1);
$pdf->Ln(2);

// Local / data / assinatura
$pdf->Cell(140,7,'Local: ____________________________',1,0);
$pdf->Cell(50,7,'Data: ' . date('Y-m-d', strtotime($ficha['data_ficha'] ?? 'now')),1,1);
$pdf->Cell(0,18,'Assinatura do Empregado: ________________________________',1,1);
$pdf->Ln(4);

// Tabela de itens
$pdf->SetFont('Arial','B',9);
$pdf->SetFillColor(230,230,230);
$pdf->Cell(70,8,'DESCRICAO DO EPI',1,0,'C',true);
$pdf->Cell(24,8,'Nº C.A.',1,0,'C',true);
$pdf->Cell(30,8,'DATA',1,0,'C',true);
$pdf->Cell(20,8,'ENTREGA',1,0,'C',true);
$pdf->Cell(20,8,'DEVOLUCAO',1,0,'C',true);
$pdf->Cell(26,8,'MOTIVO / RUBRICA',1,1,'C',true);

$pdf->SetFont('Arial','',9);
foreach ($linhas as $ln) {
    $descricao = $ln['descricao'] ?: ($ln['item_nome'] ?: '');
    $numero_ca = $ln['numero_ca'] ?: $ln['numero_item'] ?: '';
    $data = $ln['criado_em'] ? date('Y-m-d', strtotime($ln['criado_em'])) : '';
    $entrega = intval($ln['quantidade']) ?: 0;
    // assumimos entregas registradas como quantidade
    $devolucao = 0;
    $motivo = $ln['motivo'] ?: '';
    $pdf->Cell(70,7,substr($descricao,0,60),1,0);
    $pdf->Cell(24,7,$numero_ca,1,0);
    $pdf->Cell(30,7,$data,1,0);
    $pdf->Cell(20,7,$entrega,1,0,'C');
    $pdf->Cell(20,7,$devolucao,1,0,'C');
    $pdf->Cell(26,7,substr($motivo,0,26),1,1);
}

// Rodapé / observações
$pdf->Ln(6);
$pdf->SetFont('Arial','I',8);
$pdf->MultiCell(0,5,'Observacoes: ' . ($ficha['observacao'] ?? ''),0);
$pdf->Output('I', "ficha_ficha_{$id}.pdf");
exit;
?>