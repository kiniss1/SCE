<?php
session_start();
// Tokens para operações críticas (CSRF-like). Gerar se não existir.
if (!isset($_SESSION['zerar_token'])) $_SESSION['zerar_token'] = bin2hex(random_bytes(16));
if (!isset($_SESSION['desfazer_token'])) $_SESSION['desfazer_token'] = bin2hex(random_bytes(16));
$zerar_token = $_SESSION['zerar_token'];
$desfazer_token = $_SESSION['desfazer_token'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Estoque Atual - EPI</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,500,700" rel="stylesheet">
    <style>
    /*
      Melhorias visuais e organizacionais:
      - Botões com classes reutilizáveis (.btn, .btn--outline, .btn--danger, .btn--warn, .btn--icon)
      - Grupo de ações com separação clara (Export / Validade / Ações)
      - Espaçamento e responsividade melhorados
      - Modais com foco e visual mais limpo
    */
    :root {
        --primary: #115293;
        --primary-600: #0b4b80;
        --accent: #e3f2fd;
        --danger: #d32f2f;
        --danger-dark: #b71c1c;
        --warn: #ffb74d;
        --success: #43a047;
        --bg: #f6f9fc;
        --card: #ffffff;
        --muted: #6b7280;
        --radius: 10px;
        --shadow-sm: 0 2px 12px rgba(0,0,0,0.06);
        --shadow-md: 0 6px 28px rgba(0,0,0,0.08);
    }
    html,body { height:100%; margin:0; font-family: 'Roboto', Arial, sans-serif; background:var(--bg); color:#111; }
    header {
        background: var(--primary);
        color: #fff;
        padding: 18px 0;
        text-align: center;
        box-shadow: var(--shadow-md);
    }
    header h1 { margin:0; font-size:1.9rem; letter-spacing:1px; }
    nav.menu {
        background: var(--card);
        padding: 10px 0;
        border-bottom: 1px solid #e6eef6;
        display:flex;
        justify-content:center;
    }
    .nav-links { display:flex; gap:14px; align-items:center; }
    .menu-btn {
        display:inline-flex; gap:8px; align-items:center;
        padding:8px 16px; border-radius:8px; text-decoration:none;
        background:var(--accent); color:var(--primary); font-weight:600;
        border:1px solid #dfeefc;
    }
    .menu-btn.active { background:var(--primary); color:#fff; box-shadow:var(--shadow-sm); }
    .container {
        max-width: 1000px;
        margin: 28px auto;
        background: var(--card);
        padding: 20px;
        border-radius: 12px;
        box-shadow: var(--shadow-md);
    }

    /* Top controls layout */
    .controls {
        display:flex;
        gap:12px;
        align-items:center;
        flex-wrap:wrap;
        margin-bottom: 14px;
    }
    .controls .group {
        display:flex;
        gap:10px;
        align-items:center;
    }
    .controls .group--left { flex: 0 1 auto; }
    .controls .group--center { flex: 1 1 auto; justify-content:center; }
    .controls .group--right { flex: 0 1 auto; margin-left:auto; }

    /* Reusable button styles */
    .btn {
        display:inline-flex;
        gap:8px;
        align-items:center;
        border:0;
        cursor:pointer;
        border-radius:8px;
        padding:9px 14px;
        font-weight:600;
        font-size:0.98rem;
        background:var(--card);
        color:var(--primary-600);
        box-shadow: 0 1px 0 rgba(0,0,0,0.02);
        border:1px solid #d7eafc;
    }
    .btn:focus { outline:3px solid rgba(17,82,147,0.12); outline-offset:2px; }
    .btn--outline { background:#fff; color:var(--primary-600); border:1px solid #cfe6ff; }
    .btn--icon { padding:9px 10px; width:auto; display:inline-flex; justify-content:center; }
    .btn--danger { background:var(--danger); color:#fff; border:none; box-shadow:0 6px 18px rgba(211,47,47,0.14); }
    .btn--danger:hover { background:var(--danger-dark); }
    .btn--warn { background:var(--warn); color:#111; border:none; }
    .btn--small { padding:7px 10px; font-size:0.92rem; border-radius:7px; }

    .btn .material-icons { font-size:1.1rem; vertical-align:middle; }

    /* Validade chips */
    .chip {
        background:#fff7ed;
        color:#be6a00;
        border:1px solid #ffdca8;
        padding:7px 10px;
        border-radius:8px;
        font-weight:600;
        font-size:0.95rem;
    }

    /* Table */
    table { width:100%; border-collapse:collapse; margin-top:10px; background:transparent; }
    th, td {
        text-align:left;
        padding:10px 8px;
        border-bottom:1px solid #eef6fb;
        vertical-align:middle;
    }
    th {
        background:linear-gradient(180deg, #f2f9ff, #eaf6ff);
        color:var(--primary);
        font-weight:700;
        font-size:0.98rem;
    }
    tr:nth-child(even) { background:#fbfeff; }
    .low-stock { background:#fff9f4 !important; color:#b44d00; font-weight:700; }

    /* Small helpers */
    .muted { color:var(--muted); font-weight:600; font-size:0.95rem; }

    /* Modal base */
    .modal-bg { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.32); z-index:9999; align-items:center; justify-content:center; }
    .modal { width:420px; max-width:94vw; background:#fff; border-radius:10px; padding:18px; box-shadow:0 12px 40px rgba(0,0,0,0.2); }
    .modal h3 { margin:0 0 8px 0; color:var(--danger); }
    .modal p { margin:0 0 12px 0; color:#333; }
    .modal input[type="text"], .modal textarea {
        width:100%; padding:8px; border:1px solid #e6eef6; border-radius:6px; margin-bottom:8px; box-sizing:border-box;
    }
    .modal .modal-actions { display:flex; gap:8px; justify-content:flex-end; margin-top:6px; }

    /* Responsive */
    @media (max-width:860px) {
        .controls .group--center { order: 3; width:100%; justify-content:flex-start; }
        .controls .group--right { margin-left:0; order:2; }
        .controls .group--left { order:1; }
        table th, table td { font-size:0.92rem; padding:8px 6px; }
        .btn { padding:7px 10px; font-size:0.95rem; }
    }
    </style>
</head>
<body>
<header>
    <h1>Estoque Atual</h1>
</header>
<nav class="menu">
    <div class="nav-links">
        <a href="/" class="menu-btn"><span class="material-icons">home</span>Início</a>
        <a href="estoque.php" class="menu-btn active"><span class="material-icons">inventory_2</span>Estoque Atual</a>
        <a href="historico.php" class="menu-btn"><span class="material-icons">history</span>Histórico</a>
        <a href="graficos.php" class="menu-btn"><span class="material-icons">bar_chart</span>Ver Gráficos</a>
    </div>
</nav>

<div class="container" role="main" aria-labelledby="titulo-estoque">
    <h2 id="titulo-estoque" style="margin:0 0 12px 0; color:var(--primary); font-size:1.15rem;">Visão Geral do Estoque</h2>

    <!-- CONTROLS: Export | Validade | Ações -->
    <div class="controls" aria-hidden="false">
        <div class="group group--left" role="toolbar" aria-label="Exportar">
            <button class="btn btn--outline" onclick="exportarCSV()" aria-label="Exportar planilha CSV">
                <span class="material-icons">download</span> Exportar Planilha (CSV)
            </button>
            <button class="btn btn--outline" onclick="exportarCSVBaixo()" aria-label="Exportar estoque baixo">
                <span class="material-icons">warning</span> Estoque Baixo (CSV)
            </button>
        </div>

        <div class="group group--center" role="toolbar" aria-label="Validade">
            <div class="chip" title="Itens com validade aproximando (7 dias)" onclick="exportarProximosValidade(7)" style="cursor:pointer;">
                EPIs próximos da validade (1 semana)
            </div>
            <div class="chip" title="Itens com validade aproximando (30 dias)" onclick="exportarProximosValidade(30)" style="cursor:pointer;">
                EPIs próximos da validade (1 mês)
            </div>
        </div>

        <div class="group group--right" role="toolbar" aria-label="Ações">
            <button id="btn-desfazer-ultimo" class="btn btn--warn btn--small" title="Desfazer último zeramento" aria-disabled="true" disabled>
                <span class="material-icons">undo</span> Desfazer Último Zeramento
            </button>
            <button id="btn-zerar-estoque" class="btn btn--danger btn--small" title="Zerar quantidades de todos os itens">
                <span class="material-icons">layers_clear</span> Zerar Estoque
            </button>
        </div>
    </div>

    <!-- Tabela de Estoque -->
    <table id="tabela-estoque" aria-describedby="titulo-estoque">
        <thead>
            <tr>
                <th style="width:42%;">EPI</th>
                <th style="width:18%;">Nº do EPI</th>
                <th style="width:12%;">Quantidade</th>
                <th style="width:28%;">Movimentar</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
    <div style="height:6px"></div>
    <div id="alerta-falta" class="muted" aria-live="polite"></div>
</div>

<!-- Modal: Zerar Estoque -->
<div id="modal-zerar-estoque" class="modal-bg" onclick="if(event.target === this) fecharZerarModal()">
    <div class="modal" role="dialog" aria-labelledby="zerar-title" aria-modal="true">
        <h3 id="zerar-title">Confirmação: Zerar Estoque</h3>
        <p>Esta ação definirá quantidade = 0 para todos os itens (mantendo cadastros). Será criado um lote para rastrear e permitir desfazer.</p>

        <label>Responsável (obrigatório)</label>
        <input type="text" id="responsavel-zerar" placeholder="Nome do responsável" aria-label="Responsável pela operação">

        <label>Comentário (opcional)</label>
        <textarea id="comentario-zerar" placeholder="Motivo / observação"></textarea>

        <label>Digite <strong>ZERAR</strong> para confirmar</label>
        <input type="text" id="confirm-word" placeholder="ZERAR" aria-label="Confirmação textual">

        <div style="display:flex;gap:8px;align-items:center;margin-top:6px;">
            <input type="checkbox" id="confirm-checkbox" aria-label="Estou ciente">
            <label for="confirm-checkbox" style="margin:0;">Estou ciente e desejo prosseguir</label>
        </div>

        <div class="modal-actions">
            <button class="btn btn--outline" onclick="fecharZerarModal()">Cancelar</button>
            <button id="zerar-confirm-btn" class="btn btn--danger" disabled onclick="confirmarZerarEstoque()">Confirmar e Zerar</button>
        </div>
    </div>
</div>

<!-- Modal: Desfazer Zeramento -->
<div id="modal-desfazer-zeramento" class="modal-bg" onclick="if(event.target === this) fecharDesfazerModal()">
    <div class="modal" role="dialog" aria-labelledby="desfazer-title" aria-modal="true">
        <h3 id="desfazer-title">Confirmar Desfazer Último Zeramento</h3>
        <p>Re-aplica as quantidades registradas no último lote. Só é possível desfazer se o lote ainda não foi revertido.</p>

        <label>Batch ID</label>
        <input type="text" id="desfazer-batch" readonly aria-readonly="true">

        <label>Responsável (obrigatório)</label>
        <input type="text" id="responsavel-desfazer" placeholder="Seu nome">

        <label>Comentário (opcional)</label>
        <textarea id="comentario-desfazer" placeholder="Motivo / observação"></textarea>

        <label>Digite <strong>DESFAZER</strong> para confirmar</label>
        <input type="text" id="confirm-word-desfazer" placeholder="DESFAZER">

        <div style="display:flex;gap:8px;align-items:center;margin-top:6px;">
            <input type="checkbox" id="confirm-checkbox-desfazer">
            <label for="confirm-checkbox-desfazer" style="margin:0;">Estou ciente</label>
        </div>

        <div class="modal-actions">
            <button class="btn btn--outline" onclick="fecharDesfazerModal()">Cancelar</button>
            <button id="desfazer-confirm-btn" class="btn btn--warn" disabled onclick="confirmarDesfazerEstoque()">Confirmar e Desfazer</button>
        </div>
    </div>
</div>

<script>
/*
  Lógica JS mantida (mesma funcionalidade). Só pequenos ajustes para habilitar/desabilitar botões
  e para a melhor integração com o novo layout.
*/
let equipamentos = [];
let currentBatch = null;

function carregarEquipamentosBackend() {
    return fetch('listar_itens.php')
        .then(r => r.json())
        .then(lista => { equipamentos = lista; return lista; });
}

function atualizarTabelaEstoque() {
    const tbody = document.getElementById('tabela-estoque').querySelector('tbody');
    tbody.innerHTML = '';
    equipamentos.forEach((eq, idx) => {
        const low = Number(eq.quantidade) <= 2 ? 'low-stock' : '';
        tbody.innerHTML += `<tr class="${low}">
            <td>${eq.nome}</td>
            <td>${eq.numero_item || ''}</td>
            <td>${eq.quantidade}</td>
            <td class="actions-btns">
                <input type="number" id="personalizado-qtd-input-${idx}-entrada" min="1" placeholder="+" style="width:72px;margin-right:6px;">
                <button class="btn btn--small btn--outline" onclick="movimentarPersonalizado(${idx},'entrada')" title="Adicionar">Entrar</button>
                <input type="number" id="personalizado-qtd-input-${idx}-saida" min="1" placeholder="-" style="width:72px;margin-left:8px;margin-right:6px;">
                <button class="btn btn--small btn--outline" onclick="movimentarPersonalizado(${idx},'saida')" title="Remover">Sair</button>
                <button class="btn btn--small" style="background:var(--danger);color:#fff;margin-left:8px;" onclick="removerEquipamento(${eq.id})" title="Remover EPI">
                    <span class="material-icons" style="font-size:1rem;">delete</span>
                </button>
            </td>
        </tr>`;
    });
}

function movimentarPersonalizado(idx, tipo) {
    const eq = equipamentos[idx];
    const entradaId = `personalizado-qtd-input-${idx}-entrada`;
    const saidaId = `personalizado-qtd-input-${idx}-saida`;
    const quantidade = tipo === 'entrada'
        ? parseInt(document.getElementById(entradaId).value)
        : parseInt(document.getElementById(saidaId).value);
    if (isNaN(quantidade) || quantidade <= 0) { alert('Informe uma quantidade válida.'); return; }
    if (tipo === 'saida' && eq.quantidade < quantidade) { alert('Estoque insuficiente!'); return; }
    const usuario = prompt('Responsável:');
    if (!usuario) return;
    let validade = prompt('Validade do EPI (AAAA-MM-DD - opcional):');
    let recebido_por = prompt('Recebido por (opcional):');
    let observacao = prompt('Observação (opcional):');
    fetch('movimentar.php', {
        method: 'POST',
        body: new URLSearchParams({
            item_id: eq.id, tipo, quantidade,
            validade: validade || "",
            responsavel: usuario,
            recebido_por: recebido_por || "",
            observacao: observacao || ""
        })
    }).then(r => r.text()).then(resp => {
        if (resp.trim() === "ok") atualizarTudo();
        else alert("Erro: " + resp);
    });
}

function removerEquipamento(id) {
    if (!confirm('Tem certeza que deseja remover esse EPI? Essa ação não pode ser desfeita.')) return;
    fetch('deletar_item.php', { method:'POST', body: new URLSearchParams({ id }) })
    .then(r => r.json()).then(data => {
        if (data.status === 'ok') { alert('EPI removido com sucesso.'); atualizarTudo(); }
        else alert('Erro ao remover: ' + (data.mensagem || data.message || JSON.stringify(data)));
    }).catch(e => alert('Erro na requisição: ' + e));
}

function atualizarTudo() {
    carregarEquipamentosBackend().then(() => {
        atualizarTabelaEstoque();
    });
}

/* CSV export (mantido) */
function baixarCSV(csv, nomeArquivo) {
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);
    link.setAttribute("href", url);
    link.setAttribute("download", nomeArquivo);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}
function exportarCSVLista(lista, nomeArquivo = 'estoque.csv') {
    if (!lista || lista.length === 0) { alert('Nenhum dado para exportar.'); return; }
    const cab = ['Nome do EPI', 'Nº do EPI', 'Quantidade', 'Validade'];
    let csv = cab.join(';') + '\n';
    lista.forEach(item => csv += `"${item.nome}";"${item.numero_item}";${item.quantidade};\n`);
    baixarCSV(csv, nomeArquivo);
}
function exportarCSV() { exportarCSVLista(equipamentos, 'estoque_completo.csv'); }
function exportarCSVBaixo() {
    const baixo = equipamentos.filter(e => Number(e.quantidade || 0) <= 2);
    if (baixo.length === 0) { alert("Não há EPIs com estoque baixo."); return; }
    exportarCSVLista(baixo, 'estoque_baixo.csv');
}
function exportarProximosValidade(dias) { alert("Função disponível no histórico de movimentação."); }

/* --- Modais Zeramento / Desfazer (integração com endpoints) --- */
function abrirZerarModal() {
    document.getElementById('modal-zerar-estoque').style.display = 'flex';
    document.getElementById('confirm-word').value = '';
    document.getElementById('confirm-checkbox').checked = false;
    document.getElementById('responsavel-zerar').value = '';
    document.getElementById('comentario-zerar').value = '';
    document.getElementById('zerar-confirm-btn').disabled = true;
}
function fecharZerarModal() { document.getElementById('modal-zerar-estoque').style.display = 'none'; }
function abrirDesfazerModal() {
    if (!currentBatch) { alert('Nenhum lote disponível para desfazer.'); return; }
    document.getElementById('modal-desfazer-zeramento').style.display = 'flex';
    document.getElementById('desfazer-batch').value = currentBatch;
    document.getElementById('confirm-word-desfazer').value = '';
    document.getElementById('confirm-checkbox-desfazer').checked = false;
    document.getElementById('responsavel-desfazer').value = '';
    document.getElementById('comentario-desfazer').value = '';
    document.getElementById('desfazer-confirm-btn').disabled = true;
}
function fecharDesfazerModal() { document.getElementById('modal-desfazer-zeramento').style.display = 'none'; }

function verificarHabilitarZerar() {
    const okWord = (document.getElementById('confirm-word').value || '').trim().toUpperCase() === 'ZERAR';
    const chk = document.getElementById('confirm-checkbox').checked;
    const hasResp = (document.getElementById('responsavel-zerar').value || '').trim().length > 0;
    document.getElementById('zerar-confirm-btn').disabled = !(okWord && chk && hasResp);
}
function verificarHabilitarDesfazer() {
    const okWord = (document.getElementById('confirm-word-desfazer').value || '').trim().toUpperCase() === 'DESFAZER';
    const chk = document.getElementById('confirm-checkbox-desfazer').checked;
    const hasResp = (document.getElementById('responsavel-desfazer').value || '').trim().length > 0;
    document.getElementById('desfazer-confirm-btn').disabled = !(okWord && chk && hasResp);
}

function confirmarZerarEstoque() {
    const responsavel = (document.getElementById('responsavel-zerar').value || '').trim();
    const comentario = (document.getElementById('comentario-zerar').value || '').trim();
    if (!responsavel) { alert('Informe o responsável.'); return; }
    if (!confirm('Deseja realmente zerar o estoque?')) return;

    const params = new URLSearchParams({ token: '<?php echo $zerar_token; ?>', responsavel, comentario });
    fetch('zerar_estoque.php', { method:'POST', body: params })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'ok') {
            alert('Estoque zerado com sucesso. Itens afetados: ' + (data.affected || 0));
            if (data.batch_id) {
                currentBatch = data.batch_id;
                document.getElementById('btn-desfazer-ultimo').disabled = false;
                try { localStorage.setItem('ultimo_batch_zeramento', currentBatch); } catch(e){}
            }
            fecharZerarModal();
            atualizarTudo();
        } else {
            alert('Erro ao zerar estoque: ' + (data.error || JSON.stringify(data)));
            if (data.code === 'invalid_token') location.reload();
        }
    }).catch(err => alert('Erro na requisição: ' + err));
}

function confirmarDesfazerEstoque() {
    const responsavel = (document.getElementById('responsavel-desfazer').value || '').trim();
    const comentario = (document.getElementById('comentario-desfazer').value || '').trim();
    const batch = (document.getElementById('desfazer-batch').value || '').trim();
    if (!responsavel || !batch) { alert('Campos obrigatórios faltando'); return; }
    if (!confirm('Confirma desfazer o lote ' + batch + ' ?')) return;

    const params = new URLSearchParams({ token: '<?php echo $desfazer_token; ?>', batch_id: batch, responsavel, comentario });
    fetch('desfazer_zeramento.php', { method:'POST', body: params })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'ok') {
            alert('Desfazer executado com sucesso. Itens revertidos: ' + (data.reversed || 0));
            currentBatch = null;
            document.getElementById('btn-desfazer-ultimo').disabled = true;
            try { localStorage.removeItem('ultimo_batch_zeramento'); } catch(e){}
            fecharDesfazerModal();
            atualizarTudo();
        } else {
            alert('Erro ao desfazer: ' + (data.error || JSON.stringify(data)));
            if (data.code === 'invalid_token') location.reload();
        }
    }).catch(err => alert('Erro na requisição: ' + err));
}

/* Busca último lote no servidor para habilitar botão Desfazer (se existir) */
function verificarUltimoLoteServidor() {
    fetch('ultimo_lote_zeramento.php').then(r => r.json()).then(res => {
        if (res.status === 'ok' && res.found && res.batch_id) {
            currentBatch = res.batch_id;
            document.getElementById('btn-desfazer-ultimo').disabled = false;
            try { localStorage.setItem('ultimo_batch_zeramento', currentBatch); } catch(e){}
        } else {
            try {
                const saved = localStorage.getItem('ultimo_batch_zeramento');
                if (saved) {
                    currentBatch = saved;
                    document.getElementById('btn-desfazer-ultimo').disabled = false;
                } else {
                    document.getElementById('btn-desfazer-ultimo').disabled = true;
                }
            } catch(e) { document.getElementById('btn-desfazer-ultimo').disabled = true; }
        }
    }).catch(() => {
        try {
            const saved = localStorage.getItem('ultimo_batch_zeramento');
            if (saved) {
                currentBatch = saved;
                document.getElementById('btn-desfazer-ultimo').disabled = false;
            } else document.getElementById('btn-desfazer-ultimo').disabled = true;
        } catch(e){ document.getElementById('btn-desfazer-ultimo').disabled = true; }
    });
}

/* Setup eventos */
document.addEventListener('DOMContentLoaded', () => {
    atualizarTudo();

    document.getElementById('btn-zerar-estoque').addEventListener('click', abrirZerarModal);
    document.getElementById('btn-desfazer-ultimo').addEventListener('click', abrirDesfazerModal);

    // Habilitar botões com checks
    document.getElementById('confirm-word').addEventListener('input', verificarHabilitarZerar);
    document.getElementById('confirm-checkbox').addEventListener('change', verificarHabilitarZerar);
    document.getElementById('responsavel-zerar').addEventListener('input', verificarHabilitarZerar);

    document.getElementById('confirm-word-desfazer').addEventListener('input', verificarHabilitarDesfazer);
    document.getElementById('confirm-checkbox-desfazer').addEventListener('change', verificarHabilitarDesfazer);
    document.getElementById('responsavel-desfazer').addEventListener('input', verificarHabilitarDesfazer);

    verificarUltimoLoteServidor();
});
</script>
</body>
</html>