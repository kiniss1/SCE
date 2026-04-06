<?php
// fichas_epi.php
// Lista todas as fichas de EPI e fornece preview / download / impressão.
// Requisitos: conexao.php, gerar_pdf_ficha.php e tabelas fichas_colaborador, ficha_linhas, colaboradores existentes.

session_start();
require 'conexao.php';

// --- Segurança (sugestão): verificar se usuário está logado/permissão ---
// if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

// Buscar fichas (mais recentes primeiro)
try {
    $stmt = $pdo->query("
        SELECT f.id, f.colaborador_id, f.data_ficha, f.status, f.observacao, c.nome AS colaborador_nome, c.matricula, c.funcao, c.area
        FROM fichas_colaborador f
        JOIN colaboradores c ON f.colaborador_id = c.id
        ORDER BY f.data_ficha DESC, f.id DESC
    ");
    $fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $fichas = [];
    error_log("Erro ao buscar fichas: " . $e->getMessage());
}

// Função utilitária para escapar
function e($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// Função que renderiza o HTML da ficha (layout semelhante ao PDF/imagem)
function renderFichaHtml($ficha, $linhas) {
    // monta o HTML da ficha: estrutura para impressão (A4-like)
    $colaborador = $ficha['colaborador_nome'] ?? '';
    $matricula = $ficha['matricula'] ?? '';
    $funcao = $ficha['funcao'] ?? '';
    $area = $ficha['area'] ?? '';
    $dataFicha = $ficha['data_ficha'] ? date('d/m/Y', strtotime($ficha['data_ficha'])) : '';
    $observacao = $ficha['observacao'] ?? '';

    ob_start();
    ?>
    <div class="ficha-print">
        <div class="ficha-header">
            <div class="ficha-logo"> <!-- logo placeholder -->
                <div style="font-weight:700;font-size:14px;">Light</div>
            </div>
            <div class="ficha-title">
                <div class="titulo-central">RECIBO DE ENTREGA DE EPI</div>
            </div>
            <div class="ficha-boxes">
                <!-- right small boxes if needed -->
            </div>
        </div>

        <div class="ficha-meta">
            <table class="meta-table">
                <tr>
                    <td class="meta-label">Nome</td>
                    <td class="meta-value"><?= e($colaborador) ?></td>
                    <td class="meta-label">Matrícula</td>
                    <td class="meta-value small"><?= e($matricula) ?></td>
                    <td class="meta-label">Função</td>
                    <td class="meta-value"><?= e($funcao) ?></td>
                    <td class="meta-label">Área</td>
                    <td class="meta-value"><?= e($area) ?></td>
                </tr>
            </table>
        </div>

        <div class="ficha-declaracao">
            <div class="declar-titulo">Declaração do Empregado</div>
            <div class="declar-text">
                Declaro ter recebido, sem ônus, os Equipamentos de Proteção Individual – EPI’s, abaixo especificado, e o treinamento relativo à sua utilização adequada, além de:
                <ul>
                    <li>Estar ciente da obrigatoriedade do seu uso;</li>
                    <li>Usá-lo apenas para a finalidade a que se destina;</li>
                    <li>Responsabilizar-me pela guarda e conservação;</li>
                    <li>Solicitar a sua substituição sempre que o mesmo se torne impróprio para o uso;</li>
                    <li>Não alterar as características originais do equipamento;</li>
                    <li>Restituir à Empresa o prejuízo decorrente do extravio ou danos causados no EPI por uso ou acondicionamento indevido.</li>
                </ul>
            </div>
        </div>

        <div class="ficha-tabela">
            <table class="linha-table">
                <thead>
                    <tr>
                        <th style="width:46%;">DESCRIÇÃO DO EPI CONFORME CATÁLOGO</th>
                        <th style="width:10%;">Nº C.A.</th>
                        <th style="width:12%;">DATA</th>
                        <th style="width:8%;">ENTREGA</th>
                        <th style="width:8%;">DEVOLUÇÃO</th>
                        <th style="width:8%;">MOTIVO</th>
                        <th style="width:8%;">RUBRICA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Sempre renderizar 12-20 linhas visuais para manter layout parecido
                    $maxLines = max(12, count($linhas));
                    for ($i = 0; $i < $maxLines; $i++):
                        $ln = $linhas[$i] ?? null;
                        $descricao = $ln['descricao'] ?: ($ln['item_nome'] ?? '');
                        $numero_ca = $ln['numero_ca'] ?: ($ln['numero_item'] ?? '');
                        $data = $ln['criado_em'] ? date('d/m/Y', strtotime($ln['criado_em'])) : ($ln['data'] ? date('d/m/Y', strtotime($ln['data'])) : '');
                        $entrega = intval($ln['quantidade'] ?? 0);
                        $devolucao = 0; // se seu sistema tem devolução, ajuste aqui
                        $motivo = $ln['motivo'] ?? '';
                        $rubrica = $ln['rubrica'] ?? '';
                    ?>
                    <tr>
                        <td><?= e($descricao) ?></td>
                        <td><?= e($numero_ca) ?></td>
                        <td><?= e($data) ?></td>
                        <td style="text-align:center;"><?= $entrega > 0 ? e($entrega) : '' ?></td>
                        <td style="text-align:center;"><?= $devolucao > 0 ? e($devolucao) : '' ?></td>
                        <td><?= e($motivo) ?></td>
                        <td><?= e($rubrica) ?></td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>

        <div class="ficha-footer">
            <div class="local-data">Local e Data: ______________________  / ____ / ______</div>
            <div class="assinatura">Assinatura do Empregado: ___________________________</div>
            <?php if ($observacao): ?>
                <div class="obs">Observações: <?= e($observacao) ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Fichas de EPI's</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="style.css">
<style>
/* Estilos específicos para a listagem e preview de fichas */
.page-wrap { max-width: 1200px; margin: 20px auto; padding: 10px; }
.top-actions { display:flex; gap:12px; justify-content:space-between; align-items:center; margin-bottom:12px; }
.fichas-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap:16px; }
.ficha-card { background:#fff; border-radius:10px; box-shadow:0 6px 20px rgba(0,0,0,0.06); padding:12px; min-height:180px; display:flex; flex-direction:column; gap:8px; }
.ficha-card .meta { font-size:0.95rem; color:#333; }
.ficha-card .meta .date { color:#1976d2; font-weight:700; }
.ficha-actions { margin-top:auto; display:flex; gap:8px; justify-content:flex-end; }
.btn { background:#1976d2; color:#fff; padding:8px 12px; border-radius:8px; border:none; cursor:pointer; }
.btn.ghost { background:#fff; color:#1976d2; border:1px solid #d7eafc; }
.preview-box { border:1px solid #eee; padding:6px; border-radius:6px; background:#fafbfd; max-height:260px; overflow:auto; }
.ficha-print { width:780px; max-width:100%; margin: 6px auto; padding:8px; background:#fff; border:1px solid #bbb; }
.ficha-header { display:flex; align-items:center; gap:8px; border-bottom:2px solid #000; padding-bottom:6px; margin-bottom:8px; }
.ficha-logo { width:90px; text-align:center; border-right:1px solid #000; padding-right:8px; font-size:14px; font-weight:700; }
.ficha-title { flex:1; text-align:center; }
.titulo-central { font-weight:700; font-size:18px; }
.meta-table { width:100%; border-collapse:collapse; margin-bottom:8px; }
.meta-table td { padding:6px 8px; border:1px solid #ddd; font-size:0.95rem; }
.declar-titulo { font-weight:700; color:#1976d2; margin-bottom:4px; }
.declar-text ul { margin:6px 0 6px 18px; padding:0; font-size:0.92rem; }
.linha-table { width:100%; border-collapse:collapse; margin-top:6px; }
.linha-table th, .linha-table td { border:1px solid #bbb; padding:6px 8px; font-size:0.92rem; }
.linha-table thead th { background:#efefef; font-weight:700; }
.ficha-footer { margin-top:8px; display:flex; gap:18px; align-items:center; justify-content:space-between; font-size:0.95rem; }
@media (max-width:900px) {
    .ficha-print { width:100%; }
    .preview-box { max-height:200px; }
}
</style>
</head>
<body>
<?php // Page header with nav (keeps your site nav style) ?>
<header style="background:#115293;color:#fff;padding:18px 0;text-align:center;">
    <h1 style="margin:0;">Fichas de EPI's</h1>
</header>
<nav style="background:#fff;padding:10px;border-bottom:1px solid #e6eef6;text-align:center;">
    <a href="index.html" style="margin-right:12px;color:#1976d2;text-decoration:none">Início</a>
    <a href="estoque.php" style="margin-right:12px;color:#1976d2;text-decoration:none">Estoque Atual</a>
    <a href="historico.php" style="margin-right:12px;color:#1976d2;text-decoration:none">Histórico</a>
    <a href="fichas_epi.php" style="font-weight:700;color:#0954b8;text-decoration:none">Fichas de EPI</a>
</nav>

<div class="page-wrap">
    <div class="top-actions">
        <div>
            <strong>Total de fichas:</strong> <?= count($fichas) ?>
        </div>
        <div>
            <a class="btn" href="ficha_colaborador_form.php">Criar nova ficha</a>
        </div>
    </div>

    <?php if (count($fichas) === 0): ?>
        <div style="padding:18px;background:#fff;border-radius:10px;box-shadow:0 6px 20px #0001;text-align:center;">
            <p>Nenhuma ficha encontrada. Crie uma nova ficha clicando em "Criar nova ficha".</p>
        </div>
    <?php else: ?>
        <div class="fichas-grid">
            <?php foreach ($fichas as $f): 
                // fetch linhas desta ficha (limit to full set)
                $stmt = $pdo->prepare("SELECT fl.*, i.nome AS item_nome, i.numero_item FROM ficha_linhas fl LEFT JOIN itens i ON fl.item_id = i.id WHERE fl.ficha_id = ? ORDER BY fl.id ASC");
                $stmt->execute([$f['id']]);
                $linhas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $previewHtml = renderFichaHtml($f, $linhas);
            ?>
            <div class="ficha-card" id="card-<?= (int)$f['id'] ?>">
                <div class="meta">
                    <div><strong><?= e($f['colaborador_nome']) ?></strong></div>
                    <div class="date"><?= e(date('d/m/Y H:i', strtotime($f['data_ficha'] ?? $f['created_at'] ?? 'now'))) ?></div>
                    <div style="color:#666;font-size:.95rem;margin-top:6px;">Status: <strong><?= e($f['status']) ?></strong></div>
                </div>

                <div class="preview-box" aria-hidden="true">
                    <?= $previewHtml // already escaped inside render function ?>
                </div>

                <div class="ficha-actions">
                    <button class="btn ghost" onclick="openPreviewModal(<?= (int)$f['id'] ?>)">Visualizar</button>
                    <a class="btn" href="gerar_pdf_ficha.php?id=<?= (int)$f['id'] ?>" target="_blank" rel="noopener">Baixar PDF</a>
                    <button class="btn" onclick="printFicha(<?= (int)$f['id'] ?>)">Imprimir</button>
                </div>

                <!-- store preview HTML as data attribute for modal/print usage -->
                <div style="display:none;" id="ficha-html-<?= (int)$f['id'] ?>"><?= str_replace(["\n", "\r"], '', $previewHtml) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal visualização -->
<div id="preview-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.22); z-index:99999; align-items:center; justify-content:center;">
    <div style="background:#fff; max-width:900px; width:94%; max-height:92vh; overflow:auto; padding:18px; border-radius:10px; position:relative;">
        <button onclick="closePreview()" style="position:absolute; right:12px; top:8px; font-size:20px; border:none; background:none; cursor:pointer;">&times;</button>
        <div id="preview-content"></div>
        <div style="display:flex; gap:8px; justify-content:flex-end; margin-top:10px;">
            <button class="btn ghost" onclick="closePreview()">Fechar</button>
            <button class="btn" id="modal-print-btn">Imprimir</button>
            <button class="btn" id="modal-pdf-btn">Baixar PDF</button>
        </div>
    </div>
</div>

<script>
// funções JS para modal / impressão
function openPreviewModal(id) {
    const htmlDiv = document.getElementById('ficha-html-' + id);
    if (!htmlDiv) return alert('Ficha não encontrada');
    const content = htmlDiv.innerHTML;
    document.getElementById('preview-content').innerHTML = content;
    document.getElementById('preview-modal').style.display = 'flex';

    // configurar botões PDF e print
    document.getElementById('modal-pdf-btn').onclick = function() {
        window.open('gerar_pdf_ficha.php?id=' + id, '_blank', 'noopener');
    };
    document.getElementById('modal-print-btn').onclick = function() {
        // abrir janela de impressão com o HTML da ficha
        const w = window.open('', '_blank', 'width=900,height=1100');
        w.document.write('<html><head><title>Imprimir Ficha</title>');
        // importar style.css para manter layout na impressão
        w.document.write('<link rel="stylesheet" href="style.css">');
        w.document.write('<style>body{margin:18px;} .ficha-print{border:1px solid #000;padding:8px;} table{border-collapse:collapse;} table th, table td{border:1px solid #bbb;padding:6px;}</style>');
        w.document.write('</head><body>');
        w.document.write(content);
        w.document.write('</body></html>');
        w.document.close();
        w.focus();
        setTimeout(function(){ w.print(); /*w.close();*/ }, 500);
    };
}
function closePreview() {
    document.getElementById('preview-modal').style.display = 'none';
    document.getElementById('preview-content').innerHTML = '';
}

function printFicha(id) {
    const htmlDiv = document.getElementById('ficha-html-' + id);
    if (!htmlDiv) return alert('Ficha não encontrada');
    const content = htmlDiv.innerHTML;
    const w = window.open('', '_blank', 'width=900,height=1100');
    w.document.write('<html><head><title>Imprimir Ficha</title>');
    w.document.write('<link rel="stylesheet" href="style.css">');
    w.document.write('<style>body{margin:18px;} .ficha-print{border:1px solid #000;padding:8px;} table{border-collapse:collapse;} table th, table td{border:1px solid #bbb;padding:6px;}</style>');
    w.document.write('</head><body>');
    w.document.write(content);
    w.document.write('</body></html>');
    w.document.close();
    w.focus();
    setTimeout(function(){ w.print(); /*w.close();*/ }, 500);
}
</script>
</body>
</html>