// ── Toast visual ─────────────────────────────────────────────────────
function showToast(msg, error = false) {
    let toast = document.getElementById('gem-toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'gem-toast';
        toast.style.cssText = 'position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(80px);background:#1b5e20;color:#fff;padding:13px 24px;border-radius:10px;font-weight:700;font-size:0.93rem;box-shadow:0 8px 36px rgba(0,0,0,0.18);transition:transform 0.3s ease,opacity 0.3s ease;opacity:0;z-index:99999;display:flex;align-items:center;gap:8px;';
        document.body.appendChild(toast);
    }
    toast.style.background = error ? '#b71c1c' : '#1b5e20';
    toast.innerHTML = `<span class="material-icons" style="font-size:1.1rem;">${error ? 'error' : 'check_circle'}</span> ${msg}`;
    toast.style.opacity = '1';
    toast.style.transform = 'translateX(-50%) translateY(0)';
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(-50%) translateY(80px)';
    }, 3000);
}

let equipamentos = [];
let html5QrCode  = null;
let lote         = []; // Array de itens a registrar

// ── Debounce matrícula ────────────────────────────────────────────────
let debounceResponsavel = null;
let debounceRecebido    = null;

function buscarResponsavel() {
    clearTimeout(debounceResponsavel);
    debounceResponsavel = setTimeout(() => {
        const mat    = document.getElementById('matricula-responsavel').value.trim();
        const nome   = document.getElementById('usuario');
        const status = document.getElementById('status-matricula');
        if (mat.length < 5) { nome.value = ''; status.textContent = ''; return; }
        fetch('buscar_colaborador.php?matricula=' + encodeURIComponent(mat))
            .then(r => r.json())
            .then(d => {
                if (d.status === 'ok') { nome.value = d.colaborador.nome; status.textContent = '✅'; }
                else { nome.value = ''; status.textContent = '❌'; }
            });
    }, 400);
}

function buscarRecebidoPor() {
    clearTimeout(debounceRecebido);
    debounceRecebido = setTimeout(() => {
        const mat    = document.getElementById('matricula-recebido').value.trim();
        const nome   = document.getElementById('recebido-por');
        const status = document.getElementById('status-matricula-recebido');
        if (mat.length < 5) { nome.value = ''; status.textContent = ''; return; }
        fetch('buscar_colaborador.php?matricula=' + encodeURIComponent(mat))
            .then(r => r.json())
            .then(d => {
                if (d.status === 'ok') { nome.value = d.colaborador.nome; status.textContent = '✅'; }
                else { nome.value = ''; status.textContent = '❌'; }
            });
    }, 400);
}

// ── Carregar equipamentos ─────────────────────────────────────────────
function carregarEquipamentosBackend() {
    return fetch('listar_itens.php')
        .then(r => r.json())
        .then(lista => { equipamentos = lista; return lista; });
}

function atualizarSelectEquipamento() {
    const select = document.getElementById('select-equipamento');
    select.innerHTML = '';
    equipamentos.forEach(eq => {
        select.innerHTML += `<option value="${eq.id}" data-numero="${eq.numero_item}" data-nome="${eq.nome}">${eq.nome} (Nº ${eq.numero_item})</option>`;
    });
}

// ── Cadastrar EPI ─────────────────────────────────────────────────────
async function adicionarEquipamento() {
    const nome       = document.getElementById('nome-equipamento').value.trim();
    const numero     = document.getElementById('numero-item').value.trim();
    const quantidade = parseInt(document.getElementById('quantidade-inicial').value) || 0;
    if (!nome)   { alert('Informe o nome do EPI.'); return; }
    if (!numero) { alert('Informe o Nº do EPI.'); return; }
    if (equipamentos.find(e => e.nome.toLowerCase() === nome.toLowerCase() && e.numero_item === numero)) {
        alert('EPI já cadastrado com esse nome e Nº!'); return;
    }
    const res  = await fetch('adicionar_item.php', { method: 'POST', body: new URLSearchParams({nome, numero, quantidade}) });
    const data = await res.json();
    if (data.status === 'ok') {
        document.getElementById('nome-equipamento').value   = '';
        document.getElementById('numero-item').value        = '';
        document.getElementById('quantidade-inicial').value = 0;
        await atualizarTudo();
        showToast('EPI adicionado com sucesso!');
    } else {
        showToast('Erro ao adicionar: ' + (data.mensagem || JSON.stringify(data)), true);
    }
}

// ── LOTE: Adicionar item ──────────────────────────────────────────────
function adicionarAoLote() {
    if (equipamentos.length === 0) { alert('Cadastre um EPI antes.'); return; }

    const select     = document.getElementById('select-equipamento');
    const item_id    = select.value;
    const tipo       = document.getElementById('tipo-movimento').value;
    const quantidade = parseInt(document.getElementById('quantidade-movimento').value);
    const validade   = document.getElementById('validade-equipamento').value;

    if (isNaN(quantidade) || quantidade <= 0) { alert('Quantidade inválida.'); return; }

    const eq = equipamentos.find(e => e.id == item_id);
    if (!eq) { alert('EPI não encontrado!'); return; }

    if (tipo === 'saida') {
        const jaNoLote = lote
            .filter(i => i.item_id == item_id && i.tipo === 'saida')
            .reduce((s, i) => s + i.quantidade, 0);
        if (eq.quantidade < quantidade + jaNoLote) {
            alert(`Estoque insuficiente! Disponível: ${eq.quantidade - jaNoLote}`);
            return;
        }
    }

    // Se mesmo item+tipo+validade já está no lote, soma quantidade
    const existente = lote.find(i => i.item_id == item_id && i.tipo === tipo && i.validade === validade);
    if (existente) {
        existente.quantidade += quantidade;
    } else {
        lote.push({ item_id, tipo, quantidade, validade, nome: eq.nome, numero: eq.numero_item });
    }

    document.getElementById('quantidade-movimento').value = 1;
    document.getElementById('validade-equipamento').value = '';
    renderLote();
}

// ── LOTE: Remover item ────────────────────────────────────────────────
function removerDoLote(idx) {
    lote.splice(idx, 1);
    renderLote();
}

// ── LOTE: Renderizar lista ────────────────────────────────────────────
function renderLote() {
    const container = document.getElementById('lote-itens');
    const contador  = document.getElementById('lote-contador');
    const btnReg    = document.getElementById('btn-registrar-lote');
    const btnCount  = document.getElementById('lote-btn-count');

    contador.textContent       = lote.length + ' ' + (lote.length === 1 ? 'item' : 'itens');
    btnCount.textContent       = lote.length;
    btnCount.style.display     = lote.length ? 'inline-flex' : 'none';
    btnReg.disabled            = lote.length === 0;

    if (!lote.length) {
        container.innerHTML = '<div class="lote-vazia">Nenhum item adicionado ainda</div>';
        return;
    }

    container.innerHTML = lote.map((item, idx) => `
        <div class="lote-item">
            <div class="lote-item-info">
                <div class="lote-item-nome">${item.nome}</div>
                <div class="lote-item-meta">
                    Nº ${item.numero}
                    ${item.validade ? ' · Val: ' + item.validade : ''}
                    &nbsp;<span class="tipo-tag tipo-${item.tipo}">${item.tipo === 'saida' ? '↓ Saída' : '↑ Entrada'}</span>
                </div>
            </div>
            <span class="lote-item-qtd">× ${item.quantidade}</span>
            <button class="lote-item-remove" onclick="removerDoLote(${idx})" title="Remover">
                <span class="material-icons">close</span>
            </button>
        </div>
    `).join('');
}

// ── LOTE: Registrar todos ─────────────────────────────────────────────
async function registrarLote() {
    if (!lote.length) { alert('Adicione pelo menos um item ao lote.'); return; }

    const usuario               = document.getElementById('usuario').value.trim();
    const recebido_por          = document.getElementById('recebido-por').value.trim();
    const observacao            = document.getElementById('observacao-movimentacao').value.trim();
    const matricula_responsavel = document.getElementById('matricula-responsavel').value.trim();
    const matricula_recebido    = document.getElementById('matricula-recebido').value.trim();

    if (!usuario) { alert('Informe a matrícula do responsável.'); return; }

    const btn = document.getElementById('btn-registrar-lote');
    btn.disabled = true;
    btn.innerHTML = '<span class="material-icons" style="animation:spin 1s linear infinite">refresh</span> Registrando...';

    let erros = 0;
    for (const item of lote) {
        try {
            const res = await fetch('movimentar.php', {
                method: 'POST',
                body: new URLSearchParams({
                    item_id:              item.item_id,
                    tipo:                 item.tipo,
                    quantidade:           item.quantidade,
                    validade:             item.validade || '',
                    responsavel:          usuario,
                    recebido_por,
                    observacao,
                    matricula_responsavel,
                    matricula_recebido
                })
            });
            const data = await res.json();
            if (data.status !== 'ok') erros++;
        } catch(e) { erros++; }
    }

    if (erros === 0) {
        lote = [];
        renderLote();
        document.getElementById('matricula-responsavel').value          = '';
        document.getElementById('usuario').value                        = '';
        document.getElementById('status-matricula').textContent         = '';
        document.getElementById('matricula-recebido').value             = '';
        document.getElementById('recebido-por').value                   = '';
        document.getElementById('status-matricula-recebido').textContent = '';
        document.getElementById('observacao-movimentacao').value        = '';
        await atualizarTudo();
        showToast('Lote registrado com sucesso!');
        btn.innerHTML = '<span class="material-icons">send</span> Registrar lote <span id="lote-btn-count" class="lote-badge" style="display:none;">0</span>';
        btn.disabled = false;
    } else {
        showToast(`Atenção: ${erros} item(ns) não foram registrados.`, true);
        btn.disabled = false;
        btn.innerHTML = '<span class="material-icons">send</span> Registrar lote <span id="lote-btn-count" class="lote-badge">'+lote.length+'</span>';
    }
}

// ── Dashboard ─────────────────────────────────────────────────────────
async function atualizarTudo() {
    await carregarEquipamentosBackend();
    atualizarSelectEquipamento();
    await atualizarDashboard();
}

async function atualizarDashboard() {
    const total = equipamentos.reduce((sum, eq) => sum + Number(eq.quantidade || 0), 0);
    document.getElementById('card-total-estoque').innerText = total;

    const baixo = equipamentos.filter(e => Number(e.quantidade || 0) <= 2).length;
    document.getElementById('card-estoque-baixo').innerText = baixo;
    const card = document.getElementById('card-estoque-baixo-card');
    if (baixo > 0) card.classList.add('pulsante');
    else card.classList.remove('pulsante');

    try {
        const movs = await fetch('listar_movimentacoes.php').then(r => r.json());
        const movMap = {};
        movs.forEach(m => {
            const nome = m.nome || '';
            movMap[nome] = (movMap[nome] || 0) + Number(m.quantidade || 0);
        });
        const mais = Object.entries(movMap).sort((a, b) => b[1] - a[1]).slice(0, 3);
        document.getElementById('card-mais-movimentados').innerHTML = mais.length
            ? mais.map(([nome, qtd], i) =>
                `<span style="display:block;font-size:1.05em;font-weight:600;">${i+1}. ${nome}<span style="color:#1976d2;font-weight:700;font-size:0.93em;"> (${qtd})</span></span>`
              ).join('')
            : '-';

        const hoje   = new Date();
        const dias30 = 30 * 24 * 60 * 60 * 1000;
        const vencendo = movs.filter(m => {
            if (!m.validade) return false;
            const dataVal = new Date(m.validade);
            const eq = equipamentos.find(e => e.nome === m.nome);
            if (!eq || eq.quantidade <= 0) return false;
            return (dataVal - hoje) >= 0 && (dataVal - hoje) <= dias30;
        });
        document.getElementById('card-vencendo').innerText = vencendo.length;
    } catch(e) {}
}

function mostrarModalEstoqueBaixo() {
    const criticos = equipamentos.filter(e => Number(e.quantidade || 0) <= 2);
    document.getElementById('modal-estoque-baixo-lista').innerHTML = criticos.length === 0
        ? '<p style="color:#43a047;font-weight:600;">Nenhum EPI com estoque crítico!</p>'
        : `<table>
            <tr><th>Nome</th><th>Nº</th><th style="text-align:right;">Qtd</th></tr>
            ${criticos.map(e => `<tr><td>${e.nome}</td><td>${e.numero_item}</td><td style="text-align:right;color:#d32f2f;font-weight:700;">${e.quantidade}</td></tr>`).join('')}
           </table>`;
    document.getElementById('modal-estoque-baixo').style.display = 'flex';
}

function fecharModalEstoqueBaixo() {
    document.getElementById('modal-estoque-baixo').style.display = 'none';
}

// ── QR Code ───────────────────────────────────────────────────────────
function abrirQRModal() {
    document.getElementById('qr-modal-bg').style.display = 'flex';
    document.getElementById('qr-status').textContent = '';
    if (!html5QrCode) html5QrCode = new Html5Qrcode("qr-reader");
    html5QrCode.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: 220 },
        qrCodeMessage => {
            document.getElementById('qr-status').textContent = "QR lido: " + qrCodeMessage;
            selecionarEPIporQR(qrCodeMessage);
            setTimeout(fecharQRModal, 900);
        },
        errorMessage => {}
    ).catch(err => {
        document.getElementById('qr-status').textContent = "Erro ao acessar câmera: " + err;
    });
}

function fecharQRModal() {
    document.getElementById('qr-modal-bg').style.display = 'none';
    if (html5QrCode) html5QrCode.stop().then(() => html5QrCode.clear());
}

function selecionarEPIporQR(qrCodeMessage) {
    const qr     = qrCodeMessage.trim().toLowerCase();
    const select = document.getElementById('select-equipamento');
    let found    = false;
    for (const opt of select.options) {
        const numero = (opt.getAttribute('data-numero') || '').trim().toLowerCase();
        const nome   = (opt.getAttribute('data-nome')   || '').trim().toLowerCase();
        if (qr === numero || qr === nome || nome.includes(qr) || numero.includes(qr)) {
            select.value = opt.value; found = true; break;
        }
    }
    if (!found) document.getElementById('qr-status').textContent = "EPI não encontrado pelo QR Code!";
}

// ── Animação spinner ──────────────────────────────────────────────────
const spinStyle = document.createElement('style');
spinStyle.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
document.head.appendChild(spinStyle);

// ── Init ──────────────────────────────────────────────────────────────
document.addEventListener("DOMContentLoaded", () => {
    atualizarTudo();
    renderLote();
    document.getElementById('card-estoque-baixo-card').onclick = mostrarModalEstoqueBaixo;
    document.getElementById('modal-estoque-baixo').onclick = function(e) {
        if (e.target === this) fecharModalEstoqueBaixo();
    };
    document.getElementById('btn-ler-qr').onclick = abrirQRModal;
});
