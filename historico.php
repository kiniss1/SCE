<?php
session_start();
// Gera tokens para operações críticas de limpeza/desfazer histórico
if (!isset($_SESSION['limpar_historico_token'])) $_SESSION['limpar_historico_token'] = bin2hex(random_bytes(16));
if (!isset($_SESSION['desfazer_historico_token'])) $_SESSION['desfazer_historico_token'] = bin2hex(random_bytes(16));
$limpar_token = $_SESSION['limpar_historico_token'];
$desfazer_token = $_SESSION['desfazer_historico_token'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Histórico de Movimentação - EPI</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,500,700" rel="stylesheet">
    <style>
    :root {
        --primary: #115293;
        --primary-light: #1976d2;
        --accent: #e3f2fd;
        --danger: #ff5252;
        --warn: #ffb74d;
        --background: #f8fafc;
        --text: #212121;
        --container-bg: #fff;
        --border: #e0e7ef;
        --shadow: 0 4px 24px #0002;
        --radius: 14px;
        --zebra: #f4fafd;
    }
    body {
        font-family: 'Roboto', Arial, sans-serif;
        margin: 0;
        background: var(--background);
        color: var(--text);
        min-height: 100vh;
    }
    header {
        background: var(--primary);
        color: #fff;
        padding: 22px 0 15px 0;
        text-align: center;
        box-shadow: var(--shadow);
    }
    header h1 {
        margin: 0;
        font-size: 2.2rem;
        font-weight: 700;
        letter-spacing: 1.5px;
        text-shadow: 0 2px 8px #0002;
    }
    nav.menu {
        background: var(--container-bg);
        padding: 10px 0;
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: center;
        gap: 24px;
    }
    nav.menu a {
        text-decoration: none;
        color: var(--primary);
        font-weight: 500;
        font-size: 1.09rem;
        padding: 7px 18px;
        border-radius: 8px;
        transition: all .16s;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    nav.menu a:hover, nav.menu .active {
        background: var(--accent);
        color: var(--primary-light);
    }
    .container {
        background: var(--container-bg);
        padding: 34px 34px 22px 34px;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        margin: 36px auto;
        max-width: 1220px;
        overflow-x: auto;
    }
    h2 {
        color: var(--primary);
        margin-top: 0;
        margin-bottom: 18px;
        font-size: 1.17rem;
        font-weight: 700;
    }
    /* FILTROS */
    .filtros-historico {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 18px;
        align-items: flex-end;
        background: #f4fafd;
        border-radius: 9px;
        padding: 18px 20px 8px 20px;
        box-shadow: 0 2px 12px #0001;
        font-size: 1.01rem;
    }
    .filtros-historico label {
        margin: 0;
        color: var(--primary);
        font-size: .98rem;
        font-weight: 600;
        display: flex;
        flex-direction: column;
        gap: 1px;
    }
    .filtros-historico input, .filtros-historico select {
        padding: 5px 8px;
        font-size: 1.01rem;
        border-radius: 6px;
        border: 1px solid var(--border);
        background: #fff;
        transition: border .16s;
    }
    .filtros-historico input:focus, .filtros-historico select:focus {
        border: 1.5px solid var(--primary-light);
        outline: none;
    }
    .filtros-historico button {
        background: var(--primary-light);
        color: #fff;
        border: none;
        border-radius: 7px;
        padding: 10px 22px;
        font-size: 1.06rem;
        font-weight: 600;
        box-shadow: 0 1px 4px #0001;
        transition: background .15s, transform .13s;
        cursor: pointer;
        margin-left: 8px;
        margin-top: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .filtros-historico button:hover {
        background: var(--primary);
        transform: translateY(-1px) scale(1.03);
    }
    .filtros-historico .busca-rapida { min-width: 190px; }
    .filtros-actions { margin-left:auto; display:flex; gap:8px; align-items:center; }
    .btn-primary { background: var(--primary-light); color:#fff; border:none; border-radius:7px; padding:10px 18px; font-weight:600; cursor:pointer; display:flex; gap:8px; align-items:center; }
    .btn-secondary { background:#e0e8f1; color:var(--primary); border:none; border-radius:7px; padding:10px 14px; font-weight:600; cursor:pointer; display:flex; gap:8px; align-items:center; }
    .btn-danger { background: var(--danger); color:#fff; border:none; border-radius:7px; padding:9px 14px; font-weight:600; cursor:pointer; display:flex; gap:8px; align-items:center; }
    .btn-warn { background: var(--warn); color:#111; border:none; border-radius:7px; padding:9px 14px; font-weight:600; cursor:pointer; display:flex; gap:8px; align-items:center; }

    /* TABELA */
    table {
        border-collapse: collapse;
        width: 100%;
        margin-top: 18px;
        background: #fafafa;
        border-radius: 9px;
        overflow: hidden;
        box-shadow: 0 1px 6px #0001;
        font-size: .99rem;
    }
    th, td {
        border: 1px solid var(--border);
        padding: 8px 6px;
        text-align: left;
    }
    th {
        background: var(--accent);
        color: var(--primary);
        font-weight: 600;
        font-size: 1.03rem;
    }
    tr:nth-child(even) { background: var(--zebra); }
    tr:nth-child(odd) { background: #fff; }
    .pdf-row-button {
        background: var(--primary-light);
        color: #fff;
        border: none;
        font-weight: bold;
        padding: 4px 10px;
        border-radius: 5px;
        font-size: 0.97rem;
        cursor: pointer;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 4px;
        transition: background .12s;
    }
    .no-results {
        text-align: center;
        color: var(--danger);
        font-weight: 600;
        font-size: 1.04rem;
        padding: 22px 0;
        background: #fffbe4;
        border-radius: 9px;
    }

    /* Modais de confirmação */
    .modal-bg { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.22); z-index:9999; align-items:center; justify-content:center; }
    .modal-card { background:#fff; border-radius:10px; padding:16px; width:460px; max-width:94vw; box-shadow:0 10px 26px #0002; }
    .modal-card h3 { margin:0 0 8px 0; color:#b71c1c; }
    .modal-card p { margin:6px 0 12px 0; color:#333; font-size:0.96rem; }
    .modal-card input[type="text"], .modal-card textarea { width:100%; box-sizing:border-box; padding:8px 9px; border:1px solid #ddd; border-radius:6px; margin-bottom:8px; }
    .modal-actions { display:flex; gap:8px; justify-content:flex-end; margin-top:8px; }
    .small-note { font-size:0.86rem; color:#666; margin-top:6px; }
    @media (max-width:950px) {
        .filtros-historico { flex-direction:column; gap:12px; }
        .filtros-actions { margin-left:0; width:100%; justify-content:space-between; }
    }
    </style>
</head>
<body>
<header>
    <h1>Histórico de Movimentação</h1>
</header>
<nav class="menu">
    <a href="/SCE_php_teste/"><span class="material-icons">home</span>Início</a>
    <a href="estoque.php"><span class="material-icons">inventory_2</span>Estoque Atual</a>
    <a href="historico.php" class="active"><span class="material-icons">history</span>Histórico</a>
    <a href="graficos.php"><span class="material-icons">bar_chart</span>Gráficos</a>
</nav>

<div class="container">
    <h2><span class="material-icons" style="vertical-align:-5px;color:var(--primary-light);">history</span> Histórico de Movimentação</h2>

    <!-- FILTROS -->
    <form class="filtros-historico" id="form-filtros" onsubmit="filtrarHistorico();return false;">
        <label>
            Período:
            <div style="display:flex;gap:5px;">
                <input type="date" id="filtro-data-inicio"> a
                <input type="date" id="filtro-data-fim">
            </div>
        </label>
        <label>
            EPI:
            <select id="filtro-epi">
                <option value="">Todos</option>
            </select>
        </label>
        <label>
            Tipo:
            <select id="filtro-tipo">
                <option value="">Todos</option>
                <option value="entrada">Entrada</option>
                <option value="saida">Saída</option>
            </select>
        </label>
        <label>
            Responsável:
            <input type="text" id="filtro-responsavel" placeholder="Nome completo">
        </label>
        <label style="flex:1;">
            Busca rápida:
            <input type="text" id="filtro-busca" class="busca-rapida" placeholder="Buscar por qualquer campo...">
        </label>

        <div class="filtros-actions">
            <button type="submit" class="btn-primary"><span class="material-icons">search</span>Filtrar</button>
            <button type="button" class="btn-secondary" onclick="limparFiltros()"><span class="material-icons">clear_all</span>Limpar</button>

            <!-- NOVOS BOTÕES: Limpar Histórico e Desfazer Limpeza -->
            <button type="button" id="btn-limpar-historico" class="btn-danger" title="Limpar todo o histórico (cria backup)" onclick="abrirModalLimpar()">
                <span class="material-icons">delete_sweep</span> Limpar Histórico
            </button>
            <button type="button" id="btn-desfazer-historico" class="btn-warn" title="Desfazer último limpeza do histórico" onclick="abrirModalDesfazer()" disabled>
                <span class="material-icons">undo</span> Desfazer Limpeza
            </button>
        </div>
    </form>
    <!-- FIM FILTROS -->

    <table id="tabela-historico">
        <thead>
        <tr>
            <th>Data</th>
            <th>EPI</th>
            <th>Nº do EPI</th>
            <th>Tipo</th>
            <th>Quantidade</th>
            <th>Validade</th>
            <th>Responsável</th>
            <th>Recebido por</th>
            <th>Observação</th>
            <th>Gerar PDF</th>
        </tr>
        </thead>
        <tbody></tbody>
    </table>
    <div id="no-results" class="no-results" style="display:none;">Nenhum registro encontrado com os filtros atuais.</div>
</div>

<!-- Modal: Confirmar Limpar Histórico -->
<div id="modal-limpar" class="modal-bg" onclick="if(event.target===this) fecharModalLimpar()">
    <div class="modal-card" role="dialog" aria-modal="true">
        <h3>Confirmação: Limpar Histórico</h3>
        <p>Esta ação irá mover TODO o histórico atual para um backup e limpará a tabela de movimentações. Você poderá desfazer apenas o último lote. <strong>A operação é sensível — confirme com cuidado.</strong></p>

        <label>Responsável (obrigatório)</label>
        <input type="text" id="limpar-responsavel" placeholder="Nome do responsável">

        <label>Comentário (opcional)</label>
        <textarea id="limpar-comentario" placeholder="Motivo / observação"></textarea>

        <label>Digite <strong>LIMPAR</strong> para confirmar</label>
        <input type="text" id="limpar-confirm-word" placeholder="LIMPAR">

        <div style="display:flex;gap:8px;align-items:center;margin-top:6px;">
            <input type="checkbox" id="limpar-confirm-checkbox">
            <label for="limpar-confirm-checkbox" style="margin:0;">Estou ciente e quero prosseguir</label>
        </div>

        <div class="small-note">Pedimos confirmação textual e responsável para evitar cliques acidentais.</div>

        <div class="modal-actions">
            <button class="btn-secondary" onclick="fecharModalLimpar()">Cancelar</button>
            <button id="confirm-limpar-btn" class="btn-danger" disabled onclick="enviarLimparHistorico()">Confirmar e Limpar</button>
        </div>
    </div>
</div>

<!-- Modal: Confirmar Desfazer Limpeza -->
<div id="modal-desfazer" class="modal-bg" onclick="if(event.target===this) fecharModalDesfazer()">
    <div class="modal-card" role="dialog" aria-modal="true">
        <h3>Confirmar Desfazer Limpeza</h3>
        <p>Esta ação restaurará as movimentações do último lote limpo (se ainda não foram restauradas).</p>

        <label>Batch ID</label>
        <input type="text" id="desfazer-batch" readonly>

        <label>Responsável (obrigatório)</label>
        <input type="text" id="desfazer-responsavel" placeholder="Seu nome">

        <label>Comentário (opcional)</label>
        <textarea id="desfazer-comentario" placeholder="Motivo / observação"></textarea>

        <label>Digite <strong>DESFAZER</strong> para confirmar</label>
        <input type="text" id="desfazer-confirm-word" placeholder="DESFAZER">

        <div style="display:flex;gap:8px;align-items:center;margin-top:6px;">
            <input type="checkbox" id="desfazer-confirm-checkbox">
            <label for="desfazer-confirm-checkbox" style="margin:0;">Estou ciente</label>
        </div>

        <div class="small-note">Confirmação textual + responsável reduz risco de desfazer acidentalmente.</div>

        <div class="modal-actions">
            <button class="btn-secondary" onclick="fecharModalDesfazer()">Cancelar</button>
            <button id="confirm-desfazer-btn" class="btn-warn" disabled onclick="enviarDesfazerHistorico()">Confirmar e Desfazer</button>
        </div>
    </div>
</div>

<script>
// Tokens gerados no servidor (garantem que o JS envie o token correto)
const LIMPAR_TOKEN = '<?php echo $limpar_token; ?>';
const DESFAZER_TOKEN = '<?php echo $desfazer_token; ?>';

let historico = [];
let historicoFiltrado = [];
let epis = [];
let ultimoBatchHistorico = null; // batch retornado do servidor (se existir)

function carregarHistoricoBackend() {
    return fetch('listar_movimentacoes.php', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(lista => { historico = lista; historicoFiltrado = lista.slice(); return lista; });
}
function carregarEPIs() {
    return fetch('listar_itens.php', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(lista => {
            epis = lista;
            const filtroEpi = document.getElementById('filtro-epi');
            filtroEpi.innerHTML = `<option value="">Todos</option>`;
            lista.forEach(e => {
                filtroEpi.innerHTML += `<option value="${escapeHtml(e.nome)}">${escapeHtml(e.nome)} (Nº ${escapeHtml(e.numero_item)})</option>`;
            });
        });
}
function atualizarTabelaHistorico() {
    const tbody = document.getElementById('tabela-historico').querySelector('tbody');
    tbody.innerHTML = '';
    if (historicoFiltrado.length === 0) {
        document.getElementById('no-results').style.display = 'block';
        return;
    } else {
        document.getElementById('no-results').style.display = 'none';
    }
    historicoFiltrado.slice().reverse().forEach((reg, idx) => {
        tbody.innerHTML += `<tr>
            <td>${escapeHtml(reg.data)}</td>
            <td>${escapeHtml(reg.nome)}</td>
            <td>${escapeHtml(reg.numero_item || '')}</td>
            <td>${escapeHtml(reg.tipo === "entrada" ? "Entrada" : "Saída")}</td>
            <td>${escapeHtml(String(reg.quantidade))}</td>
            <td>${escapeHtml(reg.validade || 'N/A')}</td>
            <td>${escapeHtml(reg.responsavel || '')}</td>
            <td>${escapeHtml(reg.recebido_por || '')}</td>
            <td>${escapeHtml(reg.observacao || '')}</td>
            <td><button class="pdf-row-button" onclick="gerarPDF(${historico.length - 1 - idx})"><span class="material-icons" style="font-size:1rem;">picture_as_pdf</span>PDF</button></td>
        </tr>`;
    });
}
function filtrarHistorico() {
    const dataInicio = document.getElementById('filtro-data-inicio').value;
    const dataFim = document.getElementById('filtro-data-fim').value;
    const epi = document.getElementById('filtro-epi').value;
    const tipo = document.getElementById('filtro-tipo').value;
    const responsavel = document.getElementById('filtro-responsavel').value.trim().toLowerCase();
    const busca = document.getElementById('filtro-busca').value.trim().toLowerCase();

    historicoFiltrado = historico.filter(reg => {
        if (dataInicio && reg.data < dataInicio) return false;
        if (dataFim && reg.data > dataFim) return false;
        if (epi && reg.nome !== epi) return false;
        if (tipo && reg.tipo !== tipo) return false;
        if (responsavel && (!reg.responsavel || reg.responsavel.toLowerCase().indexOf(responsavel) === -1)) return false;
        if (busca) {
            let texto = [
                reg.data, reg.nome, reg.numero_item, reg.tipo, reg.quantidade, reg.validade,
                reg.responsavel, reg.recebido_por, reg.observacao
            ].join(' ').toLowerCase();
            if (texto.indexOf(busca) === -1) return false;
        }
        return true;
    });
    atualizarTabelaHistorico();
}
function limparFiltros() {
    document.getElementById('filtro-data-inicio').value = '';
    document.getElementById('filtro-data-fim').value = '';
    document.getElementById('filtro-epi').value = '';
    document.getElementById('filtro-tipo').value = '';
    document.getElementById('filtro-responsavel').value = '';
    document.getElementById('filtro-busca').value = '';
    historicoFiltrado = historico.slice();
    atualizarTabelaHistorico();
}
function atualizarTudo() {
    Promise.all([carregarHistoricoBackend(), carregarEPIs()]).then(() => {
        limparFiltros();
        atualizarTabelaHistorico();
    });
}

/* --- PDF (mantido) --- */
function gerarPDF(idx) {
    const { jsPDF } = window.jspdf || {};
    if (!jsPDF) { alert('Biblioteca jsPDF não carregada.'); return; }
    const doc = new jsPDF();
    const registro = historico.slice().reverse()[idx];
    doc.setFontSize(16);
    doc.text("Histórico de Movimentação - EPI", 14, 20);
    const dados = [
        ["Data:", registro.data || "N/A"],
        ["EPI:", registro.nome || "N/A"],
        ["Nº do EPI:", registro.numero_item || "N/A"],
        ["Tipo:", registro.tipo === "entrada" ? "Entrada" : "Saída"],
        ["Quantidade:", registro.quantidade || "N/A"],
        ["Validade:", registro.validade || "N/A"],
        ["Responsável:", registro.responsavel || "N/A"],
        ["Recebido por:", registro.recebido_por || "N/A"],
        ["Observação:", registro.observacao || "N/A"]
    ];
    doc.autoTable({
        startY: 30,
        theme: 'striped',
        styles: { fontSize: 12 },
        head: [['Campo', 'Valor']],
        body: dados,
        margin: { left: 14, right: 14 }
    });
    const safeName = (registro.nome || 'registro').replace(/[^\w\-]+/g,'_');
    doc.save(`historico_movimentacao_${safeName}_${(registro.data||'').replace(/[: ]/g,'_')}.pdf`);
}

/* --- Modais Limpar / Desfazer (frontend) --- */
function abrirModalLimpar() {
    document.getElementById('modal-limpar').style.display = 'flex';
    document.getElementById('limpar-responsavel').value = '';
    document.getElementById('limpar-comentario').value = '';
    document.getElementById('limpar-confirm-word').value = '';
    document.getElementById('limpar-confirm-checkbox').checked = false;
    document.getElementById('confirm-limpar-btn').disabled = true;
}
function fecharModalLimpar() { document.getElementById('modal-limpar').style.display = 'none'; }
function abrirModalDesfazer() {
    if (!ultimoBatchHistorico) { alert('Nenhum lote disponível para desfazer.'); return; }
    document.getElementById('modal-desfazer').style.display = 'flex';
    document.getElementById('desfazer-batch').value = ultimoBatchHistorico;
    document.getElementById('desfazer-responsavel').value = '';
    document.getElementById('desfazer-comentario').value = '';
    document.getElementById('desfazer-confirm-word').value = '';
    document.getElementById('desfazer-confirm-checkbox').checked = false;
    document.getElementById('confirm-desfazer-btn').disabled = true;
}
function fecharModalDesfazer() { document.getElementById('modal-desfazer').style.display = 'none'; }

function verificarHabilitarLimpar() {
    const ok = (document.getElementById('limpar-confirm-word').value || '').trim().toUpperCase() === 'LIMPAR';
    const chk = document.getElementById('limpar-confirm-checkbox').checked;
    const hasResp = (document.getElementById('limpar-responsavel').value || '').trim().length > 0;
    document.getElementById('confirm-limpar-btn').disabled = !(ok && chk && hasResp);
}
function verificarHabilitarDesfazer() {
    const ok = (document.getElementById('desfazer-confirm-word').value || '').trim().toUpperCase() === 'DESFAZER';
    const chk = document.getElementById('desfazer-confirm-checkbox').checked;
    const hasResp = (document.getElementById('desfazer-responsavel').value || '').trim().length > 0;
    document.getElementById('confirm-desfazer-btn').disabled = !(ok && chk && hasResp);
}
document.addEventListener('input', function(e){
    const id = (e.target && e.target.id) || '';
    if (id === 'limpar-confirm-word' || id === 'limpar-responsavel') verificarHabilitarLimpar();
    if (id === 'desfazer-confirm-word' || id === 'desfazer-responsavel') verificarHabilitarDesfazer();
});
document.addEventListener('change', function(e){
    if (e.target && e.target.id === 'limpar-confirm-checkbox') verificarHabilitarLimpar();
    if (e.target && e.target.id === 'desfazer-confirm-checkbox') verificarHabilitarDesfazer();
});

/* Enviar limpar histórico */
function enviarLimparHistorico() {
    const responsavel = (document.getElementById('limpar-responsavel').value || '').trim();
    const comentario = (document.getElementById('limpar-comentario').value || '').trim();
    if (!responsavel) { alert('Informe o responsável.'); return; }
    if (!confirm('Confirma criar backup e limpar todo o histórico? Essa ação pode ser desfeita apenas para o último lote.')) return;

    const params = new URLSearchParams({
        token: LIMPAR_TOKEN,
        responsavel: responsavel,
        comentario: comentario
    });

    fetch('limpar_historico.php', {
        method: 'POST',
        credentials: 'same-origin',
        body: params
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'ok') {
            alert('Histórico limpo com sucesso. Registros movidos: ' + (data.affected || 0));
            // força reload para obter novo token e estado consistente
            location.reload();
        } else {
            alert('Erro ao limpar histórico: ' + (data.error || JSON.stringify(data)));
            if (data.code === 'invalid_token') {
                alert('Token inválido/expirado. A página será recarregada para obter novo token.');
                location.reload();
            }
        }
    }).catch(err => {
        alert('Erro na requisição: ' + err);
    });
}

/* Enviar desfazer limpeza */
function enviarDesfazerHistorico() {
    const responsavel = (document.getElementById('desfazer-responsavel').value || '').trim();
    const comentario = (document.getElementById('desfazer-comentario').value || '').trim();
    const batch = (document.getElementById('desfazer-batch').value || '').trim();
    if (!responsavel || !batch) { alert('Campos obrigatórios faltando'); return; }
    if (!confirm('Confirma desfazer a limpeza do histórico (batch ' + batch + ') ?')) return;

    const params = new URLSearchParams({
        token: DESFAZER_TOKEN,
        batch_id: batch,
        responsavel: responsavel,
        comentario: comentario
    });

    fetch('desfazer_limpeza_historico.php', {
        method: 'POST',
        credentials: 'same-origin',
        body: params
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'ok') {
            alert('Desfazer executado com sucesso. Registros restaurados: ' + (data.restored || 0));
            location.reload(); // reload para garantir tokens e estado consistente
        } else {
            alert('Erro ao desfazer: ' + (data.error || JSON.stringify(data)));
            if (data.code === 'invalid_token') {
                alert('Token inválido/expirado. A página será recarregada para obter novo token.');
                location.reload();
            }
        }
    }).catch(err => alert('Erro na requisição: ' + err));
}

/* Buscar último lote disponível no servidor e habilitar botão */
function verificarUltimoLoteHistoricoServidor() {
    fetch('ultimo_lote_limpeza_historico.php', { credentials: 'same-origin' })
    .then(r => r.json())
    .then(res => {
        if (res.status === 'ok' && res.found && res.batch_id && !res.all_restored) {
            ultimoBatchHistorico = res.batch_id;
            document.getElementById('btn-desfazer-historico').disabled = false;
            try { localStorage.setItem('ultimo_batch_historico', ultimoBatchHistorico); } catch(e){}
            document.getElementById('desfazer-batch').value = ultimoBatchHistorico;
        } else {
            try {
                const saved = localStorage.getItem('ultimo_batch_historico');
                if (saved) {
                    ultimoBatchHistorico = saved;
                    document.getElementById('btn-desfazer-historico').disabled = false;
                    document.getElementById('desfazer-batch').value = ultimoBatchHistorico;
                } else {
                    document.getElementById('btn-desfazer-historico').disabled = true;
                }
            } catch(e){ document.getElementById('btn-desfazer-historico').disabled = true; }
        }
    }).catch(()=> {
        try {
            const saved = localStorage.getItem('ultimo_batch_historico');
            if (saved) {
                ultimoBatchHistorico = saved;
                document.getElementById('btn-desfazer-historico').disabled = false;
                document.getElementById('desfazer-batch').value = ultimoBatchHistorico;
            } else document.getElementById('btn-desfazer-historico').disabled = true;
        } catch(e){ document.getElementById('btn-desfazer-historico').disabled = true; }
    });
}

/* Segurança: escape simples para inserir texto no HTML */
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

/* Setup inicial */
document.addEventListener("DOMContentLoaded", () => {
    atualizarTudo();
    verificarUltimoLoteHistoricoServidor();
});
</script>

<!-- libs para gerar PDF (mantidos) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
</body>
</html>