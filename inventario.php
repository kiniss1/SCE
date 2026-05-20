<?php require 'session.php'; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Inventário — GEM</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <style>
    /* ── Layout inventário ─────────────────────────────── */
    .inv-wrap { max-width:1280px; margin:24px auto 48px; padding:0 var(--page-px,28px); }

    /* Abas Centro 12 / Centro 91 */
    .centro-tabs {
      display:flex; gap:0; margin-bottom:0;
      border-bottom:2px solid var(--border,#d6e8f7);
    }
    .centro-tab {
      padding:12px 28px; font-size:0.95rem; font-weight:700;
      color:var(--text-muted,#607080); cursor:pointer;
      border:none; background:none; border-bottom:3px solid transparent;
      margin-bottom:-2px; transition:all .18s;
    }
    .centro-tab:hover { color:var(--primary,#0b4b80); }
    .centro-tab.active {
      color:var(--primary,#0b4b80);
      border-bottom-color:var(--primary,#0b4b80);
      background:rgba(11,75,128,0.04);
      border-radius:8px 8px 0 0;
    }

    /* Card inventário */
    .inv-card {
      background:#fff; border-radius:0 var(--radius,16px) var(--radius,16px) var(--radius,16px);
      box-shadow:var(--shadow,0 4px 20px rgba(11,75,128,.12));
      border:1px solid var(--border,#d6e8f7);
      overflow:hidden;
    }
    .inv-card-header {
      padding:18px 24px;
      background:linear-gradient(135deg,#f5f9ff,#eaf3ff);
      border-bottom:1px solid var(--border,#d6e8f7);
      display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;
    }
    .inv-card-header h2 { margin:0; font-size:1rem; font-weight:700; color:var(--primary,#0b4b80); display:flex; align-items:center; gap:8px; }
    .inv-actions { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }

    /* Upload zone */
    .upload-zone {
      border:2px dashed var(--border,#d6e8f7); border-radius:12px;
      padding:32px; text-align:center; margin:20px 24px;
      cursor:pointer; transition:all .18s; background:#fafcff;
    }
    .upload-zone:hover { border-color:var(--primary-light,#1565c0); background:var(--accent,#e8f4ff); }
    .upload-zone .material-icons { font-size:2.5rem; color:#b0bec5; display:block; margin-bottom:8px; }
    .upload-zone p { color:var(--text-muted,#607080); font-size:0.9rem; margin:0; }
    .upload-zone strong { color:var(--primary,#0b4b80); }
    .upload-zone input[type=file] { display:none; }

    /* Estatísticas rápidas */
    .inv-stats {
      display:flex; gap:12px; padding:14px 24px;
      background:#f8fbff; border-bottom:1px solid var(--border,#d6e8f7);
      flex-wrap:wrap;
    }
    .stat-badge {
      display:flex; align-items:center; gap:6px;
      padding:6px 12px; border-radius:20px; font-size:0.82rem; font-weight:700;
    }
    .stat-badge .material-icons { font-size:0.95rem; }
    .stat-ok    { background:#e8f5e9; color:#2e7d32; }
    .stat-menor { background:#ffebee; color:#c62828; }
    .stat-maior { background:#fff8e1; color:#ef6c00; }
    .stat-total { background:var(--accent,#e8f4ff); color:var(--primary,#0b4b80); }

    /* Tabela */
    .inv-table-wrap { overflow-x:auto; max-height:520px; overflow-y:auto; }
    table.inv-table { width:100%; border-collapse:collapse; font-size:0.88rem; }
    table.inv-table th {
      background:linear-gradient(180deg,#e7f4ff,#d8ecfb);
      color:var(--primary,#0b4b80); font-weight:700; padding:11px 14px;
      text-align:left; font-size:0.78rem; text-transform:uppercase;
      letter-spacing:0.4px; position:sticky; top:0; z-index:1;
    }
    table.inv-table td { padding:9px 14px; border-bottom:1px solid #f0f5fb; vertical-align:middle; }
    table.inv-table tr:hover td { background:#f5f9ff; }

    /* Linha colorida por status */
    tr.inv-ok    td { background:rgba(67,160,71,0.05); }
    tr.inv-menor td { background:rgba(229,57,53,0.06); }
    tr.inv-maior td { background:rgba(255,167,38,0.08); }
    tr.inv-ok:hover    td { background:rgba(67,160,71,0.10); }
    tr.inv-menor:hover td { background:rgba(229,57,53,0.12); }
    tr.inv-maior:hover td { background:rgba(255,167,38,0.14); }

    /* Badge diferença */
    .diff-badge {
      display:inline-flex; align-items:center; gap:3px;
      padding:3px 8px; border-radius:12px; font-weight:800; font-size:0.82rem;
    }
    .diff-ok    { background:#e8f5e9; color:#2e7d32; }
    .diff-menor { background:#ffebee; color:#c62828; }
    .diff-maior { background:#fff8e1; color:#ef6c00; }
    .diff-nd    { background:#f5f5f5; color:#9e9e9e; }

    /* Input quantidade física */
    .qty-input {
      width:90px; padding:7px 10px; border:1.5px solid var(--border,#d6e8f7);
      border-radius:8px; font-size:0.95rem; font-weight:700; text-align:center;
      outline:none; transition:all .18s; font-family:inherit;
    }
    .qty-input:focus { border-color:var(--primary-light,#1565c0); box-shadow:0 0 0 3px rgba(21,101,192,.1); }
    .qty-input.menor { border-color:#e53935; background:#fff8f8; }
    .qty-input.maior { border-color:#f57c00; background:#fffdf0; }
    .qty-input.ok    { border-color:#43a047; background:#f8fff8; }

    /* Botões */
    .btn-inv {
      padding:9px 16px; border:none; border-radius:9px; font-size:0.9rem;
      font-weight:700; cursor:pointer; display:inline-flex; align-items:center;
      gap:6px; transition:all .18s;
    }
    .btn-inv-primary { background:linear-gradient(135deg,#0b4b80,#1565c0); color:#fff; box-shadow:0 2px 8px rgba(21,101,192,.25); }
    .btn-inv-primary:hover { box-shadow:0 4px 14px rgba(21,101,192,.4); transform:translateY(-1px); }
    .btn-inv-outline { background:#fff; border:1.5px solid var(--border,#d6e8f7); color:var(--primary,#0b4b80); }
    .btn-inv-outline:hover { background:var(--accent,#e8f4ff); }
    .btn-inv-danger  { background:linear-gradient(135deg,#b71c1c,#e53935); color:#fff; }
    .btn-inv-danger:hover { transform:translateY(-1px); }
    .btn-inv:disabled { opacity:.5; cursor:not-allowed; transform:none; }

    /* Empty state */
    .inv-empty {
      text-align:center; padding:48px 24px;
      color:var(--text-muted,#607080);
    }
    .inv-empty .material-icons { font-size:3rem; color:#d0dce8; display:block; margin-bottom:10px; }

    /* Toast */
    .inv-toast {
      position:fixed; bottom:28px; left:50%; transform:translateX(-50%) translateY(80px);
      background:#1b5e20; color:#fff; padding:12px 22px; border-radius:10px;
      font-weight:700; font-size:0.93rem; box-shadow:0 8px 32px rgba(0,0,0,.18);
      transition:all .3s; opacity:0; z-index:9999; display:flex; align-items:center; gap:8px;
    }
    .inv-toast.show { transform:translateX(-50%) translateY(0); opacity:1; }
    .inv-toast.err  { background:#b71c1c; }

    @media(max-width:700px){
      .inv-card-header { flex-direction:column; align-items:flex-start; }
      .qty-input { width:70px; }
      .centro-tab { padding:10px 16px; font-size:0.85rem; }
    }
  </style>
</head>
<body>

<header>
  <div class="header-inner">
    <div class="header-brand">
      <span class="material-icons">security</span>
      <div>
        <h1>GEM — Sistema de Controle de EPI</h1>
        <span class="sub">Gestão de Estoque e Materiais</span>
      </div>
    </div>
  </div>
</header>

<nav class="menu">
  <div class="nav-inner">
    <div class="nav-links">
      <a href="/" class="menu-btn"><span class="material-icons">home</span>Início</a>
      <a href="estoque.php" class="menu-btn"><span class="material-icons">inventory_2</span>Estoque Atual</a>
      <a href="historico.php" class="menu-btn"><span class="material-icons">history</span>Histórico</a>
      <a href="fichas_epi.php" class="menu-btn"><span class="material-icons">description</span>Fichas de EPI</a>
      <a href="descarte.php" class="menu-btn"><span class="material-icons">delete_forever</span>Descarte</a>
      <a href="inventario.php" class="menu-btn active"><span class="material-icons">fact_check</span>Inventário</a>
      <a href="logout.php" class="menu-btn" style="color:#e53935!important;"><span class="material-icons">logout</span>Sair</a>
    </div>
    <div class="nav-actions">
      <div id="session-timer" style="display:flex;align-items:center;gap:5px;font-size:0.8rem;font-weight:700;color:#607080;background:var(--accent,#e8f4ff);padding:5px 10px;border-radius:8px;border:1px solid var(--border,#d6e8f7);">
        <span class="material-icons" style="font-size:1rem;color:#1565c0;">timer</span>
        <span id="timer-display">30:00</span>
      </div>
      <a href="graficos.php" class="menu-btn btn-graficos"><span class="material-icons">bar_chart</span>Ver Gráficos</a>
    </div>
    <button class="nav-hamburger" onclick="abrirDrawer()"><span class="material-icons">menu</span></button>
  </div>
  <div class="nav-drawer" id="nav-drawer">
    <div class="nav-drawer-overlay" onclick="fecharDrawer()"></div>
    <div class="nav-drawer-panel">
      <div class="nav-drawer-header">
        <span>GEM — EPI</span>
        <button class="nav-drawer-close" onclick="fecharDrawer()"><span class="material-icons">close</span></button>
      </div>
      <div class="nav-drawer-links">
        <a href="/"><span class="material-icons">home</span>Início</a>
        <a href="estoque.php"><span class="material-icons">inventory_2</span>Estoque Atual</a>
        <a href="historico.php"><span class="material-icons">history</span>Histórico</a>
        <a href="fichas_epi.php"><span class="material-icons">description</span>Fichas de EPI</a>
        <a href="descarte.php"><span class="material-icons">delete_forever</span>Descarte</a>
        <a href="inventario.php" class="active"><span class="material-icons">fact_check</span>Inventário</a>
        <a href="graficos.php" class="drawer-graficos"><span class="material-icons">bar_chart</span>Ver Gráficos</a>
        <a href="logout.php" style="color:#e53935;"><span class="material-icons">logout</span>Sair</a>
      </div>
    </div>
  </div>
</nav>

<div class="inv-wrap">

  <!-- Abas Centro -->
  <div class="centro-tabs">
    <button class="centro-tab active" onclick="trocarCentro(12, this)">
      <span class="material-icons" style="font-size:1rem;vertical-align:-3px;">warehouse</span>
      Centro 12
    </button>
    <button class="centro-tab" onclick="trocarCentro(91, this)">
      <span class="material-icons" style="font-size:1rem;vertical-align:-3px;">warehouse</span>
      Centro 91
    </button>
  </div>

  <!-- Card Principal -->
  <div class="inv-card">

    <div class="inv-card-header">
      <h2>
        <span class="material-icons" style="color:var(--primary-light,#1565c0);">fact_check</span>
        Inventário — <span id="centro-label">Centro 12</span>
      </h2>
      <div class="inv-actions">
        <label class="btn-inv btn-inv-outline" style="cursor:pointer;">
          <span class="material-icons">upload_file</span>
          Importar Excel
          <input type="file" id="file-input" accept=".xlsx,.xls,.csv" onchange="importarExcel(event)">
        </label>
        <button class="btn-inv btn-inv-primary" onclick="exportarResumo()" id="btn-exportar" disabled>
          <span class="material-icons">download</span>Exportar Resumo
        </button>
        <button class="btn-inv btn-inv-danger" onclick="limparInventario()" id="btn-limpar" disabled>
          <span class="material-icons">delete_sweep</span>Limpar
        </button>
      </div>
    </div>

    <!-- Stats -->
    <div class="inv-stats" id="inv-stats" style="display:none;">
      <div class="stat-badge stat-total">
        <span class="material-icons">list</span>
        <span id="stat-total">0 itens</span>
      </div>
      <div class="stat-badge stat-ok">
        <span class="material-icons">check_circle</span>
        <span id="stat-ok">0 corretos</span>
      </div>
      <div class="stat-badge stat-menor">
        <span class="material-icons">arrow_downward</span>
        <span id="stat-menor">0 a menos</span>
      </div>
      <div class="stat-badge stat-maior">
        <span class="material-icons">arrow_upward</span>
        <span id="stat-maior">0 a mais</span>
      </div>
      <div class="stat-badge" style="background:#f5f5f5;color:#757575;">
        <span class="material-icons">hourglass_empty</span>
        <span id="stat-nd">0 não contados</span>
      </div>
    </div>

    <!-- Tabela -->
    <div class="inv-table-wrap" id="inv-table-wrap">
      <div class="inv-empty" id="inv-empty">
        <span class="material-icons">upload_file</span>
        <strong>Nenhuma lista importada</strong>
        <p style="margin-top:6px;">Importe o Excel exportado do SAP para iniciar o inventário</p>
      </div>
      <table class="inv-table" id="inv-table" style="display:none;">
        <thead>
          <tr>
            <th>Material</th>
            <th>Descrição</th>
            <th>Depósito</th>
            <th style="text-align:center;">Qtd SAP</th>
            <th style="text-align:center;">Qtd Física</th>
            <th style="text-align:center;">Diferença</th>
            <th style="text-align:center;">Status</th>
          </tr>
        </thead>
        <tbody id="inv-tbody"></tbody>
      </table>
    </div>

  </div>
</div>

<div class="inv-toast" id="inv-toast"></div>

<script>
// ── Estado ────────────────────────────────────────────────
const state = { 12: [], 91: [] };
let centroAtivo = 12;

// ── Trocar aba ────────────────────────────────────────────
function trocarCentro(centro, btn) {
  centroAtivo = centro;
  document.querySelectorAll('.centro-tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('centro-label').textContent = 'Centro ' + centro;
  renderTabela();
}

// ── Importar Excel ────────────────────────────────────────
function importarExcel(event) {
  const file = event.target.files[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = function(e) {
    try {
      const wb   = XLSX.read(e.target.result, { type: 'binary' });
      const ws   = wb.Sheets[wb.SheetNames[0]];
      const rows = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '' });

      if (rows.length < 2) { showToast('Arquivo vazio ou inválido.', true); return; }

      // Detectar cabeçalho
      const header = rows[0].map(h => String(h).trim().toLowerCase());
      const iMat   = header.findIndex(h => h.includes('material'));
      const iDesc  = header.findIndex(h => h.includes('texto') || h.includes('descri'));
      const iCentro = header.findIndex(h => h.includes('centro'));
      const iDep   = header.findIndex(h => h.includes('dep') || h.includes('depósito'));
      const iQty   = header.findIndex(h => h.includes('livre') || h.includes('qtd') || h.includes('utiliza'));

      if (iMat === -1 || iQty === -1) {
        showToast('Colunas "Material" e "Utilização livre" não encontradas.', true);
        return;
      }

      const itens = [];
      for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const material = String(row[iMat] || '').trim();
        if (!material) continue;

        const centroRow = iCentro >= 0 ? String(row[iCentro] || '').trim() : String(centroAtivo);
        // Filtrar pelo centro ativo
        if (centroRow && centroRow !== String(centroAtivo)) continue;

        itens.push({
          material: material,
          descricao: iDesc >= 0 ? String(row[iDesc] || '').trim() : '—',
          deposito:  iDep  >= 0 ? String(row[iDep]  || '').trim() : '—',
          qtd_sap:   parseFloat(String(row[iQty] || '0').replace(',', '.')) || 0,
          qtd_fisica: null,
        });
      }

      if (!itens.length) {
        showToast('Nenhum item encontrado para o Centro ' + centroAtivo + '.', true);
        return;
      }

      state[centroAtivo] = itens;
      renderTabela();
      showToast(itens.length + ' itens importados para o Centro ' + centroAtivo + '!');
    } catch(err) {
      showToast('Erro ao ler o arquivo: ' + err.message, true);
    }
    // Resetar input
    event.target.value = '';
  };
  reader.readAsBinaryString(file);
}

// ── Renderizar tabela ─────────────────────────────────────
function renderTabela() {
  const itens = state[centroAtivo];
  const empty = document.getElementById('inv-empty');
  const table = document.getElementById('inv-table');
  const stats = document.getElementById('inv-stats');
  const btnEx = document.getElementById('btn-exportar');
  const btnLp = document.getElementById('btn-limpar');

  if (!itens.length) {
    empty.style.display = 'block';
    table.style.display = 'none';
    stats.style.display = 'none';
    btnEx.disabled = true;
    btnLp.disabled = true;
    return;
  }

  empty.style.display = 'none';
  table.style.display = 'table';
  stats.style.display = 'flex';
  btnEx.disabled = false;
  btnLp.disabled = false;

  const tbody = document.getElementById('inv-tbody');
  tbody.innerHTML = itens.map((item, idx) => {
    const f   = item.qtd_fisica;
    const s   = item.qtd_sap;
    let status = 'nd', diff = '—', cls = '', diffCls = 'diff-nd', diffIcon = '';

    if (f !== null && f !== '') {
      const d = f - s;
      if (d === 0)      { status='ok';    cls='inv-ok';    diff='+0';       diffCls='diff-ok';    diffIcon='check'; }
      else if (d < 0)   { status='menor'; cls='inv-menor'; diff=String(d);  diffCls='diff-menor'; diffIcon='arrow_downward'; }
      else              { status='maior'; cls='inv-maior'; diff='+'+d;      diffCls='diff-maior'; diffIcon='arrow_upward'; }
    }

    const inputCls = f === null ? '' : (status === 'ok' ? 'ok' : status === 'menor' ? 'menor' : 'maior');

    return `<tr class="${cls}" id="row-${centroAtivo}-${idx}">
      <td><code style="background:#f0f5fb;padding:2px 7px;border-radius:5px;font-size:0.82rem;">${esc(item.material)}</code></td>
      <td style="max-width:300px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="${esc(item.descricao)}">${esc(item.descricao)}</td>
      <td style="text-align:center;">${esc(item.deposito)}</td>
      <td style="text-align:center;font-weight:700;">${s}</td>
      <td style="text-align:center;">
        <input type="number" class="qty-input ${inputCls}" min="0"
          value="${f !== null ? f : ''}"
          placeholder="—"
          oninput="atualizarLinha(${idx}, this.value)"
          onchange="atualizarLinha(${idx}, this.value)">
      </td>
      <td style="text-align:center;">
        <span class="diff-badge ${diffCls}">
          ${diffIcon ? '<span class="material-icons" style="font-size:0.85rem;">'+diffIcon+'</span>' : ''}
          ${diff}
        </span>
      </td>
      <td style="text-align:center;">
        ${status === 'ok'    ? '<span style="color:#2e7d32;font-weight:700;font-size:0.8rem;">✓ OK</span>' :
          status === 'menor' ? '<span style="color:#c62828;font-weight:700;font-size:0.8rem;">▼ A MENOS</span>' :
          status === 'maior' ? '<span style="color:#ef6c00;font-weight:700;font-size:0.8rem;">▲ A MAIS</span>' :
                               '<span style="color:#9e9e9e;font-size:0.8rem;">— Não contado</span>'}
      </td>
    </tr>`;
  }).join('');

  atualizarStats();
}

// ── Atualizar linha ao digitar ────────────────────────────
function atualizarLinha(idx, val) {
  const v = val === '' ? null : parseFloat(val);
  state[centroAtivo][idx].qtd_fisica = v;

  const item   = state[centroAtivo][idx];
  const s      = item.qtd_sap;
  const row    = document.getElementById('row-' + centroAtivo + '-' + idx);
  const input  = row ? row.querySelector('.qty-input') : null;

  if (!row) return;

  // Limpar classes
  row.className = '';
  if (input) input.className = 'qty-input';

  if (v === null) {
    // Não contado
    row.cells[5].innerHTML = '<span class="diff-badge diff-nd">—</span>';
    row.cells[6].innerHTML = '<span style="color:#9e9e9e;font-size:0.8rem;">— Não contado</span>';
  } else {
    const d = v - s;
    if (d === 0) {
      row.className = 'inv-ok';
      if (input) input.classList.add('ok');
      row.cells[5].innerHTML = '<span class="diff-badge diff-ok"><span class="material-icons" style="font-size:.85rem;">check</span>+0</span>';
      row.cells[6].innerHTML = '<span style="color:#2e7d32;font-weight:700;font-size:0.8rem;">✓ OK</span>';
    } else if (d < 0) {
      row.className = 'inv-menor';
      if (input) input.classList.add('menor');
      row.cells[5].innerHTML = `<span class="diff-badge diff-menor"><span class="material-icons" style="font-size:.85rem;">arrow_downward</span>${d}</span>`;
      row.cells[6].innerHTML = '<span style="color:#c62828;font-weight:700;font-size:0.8rem;">▼ A MENOS</span>';
    } else {
      row.className = 'inv-maior';
      if (input) input.classList.add('maior');
      row.cells[5].innerHTML = `<span class="diff-badge diff-maior"><span class="material-icons" style="font-size:.85rem;">arrow_upward</span>+${d}</span>`;
      row.cells[6].innerHTML = '<span style="color:#ef6c00;font-weight:700;font-size:0.8rem;">▲ A MAIS</span>';
    }
  }
  atualizarStats();
}

// ── Stats ─────────────────────────────────────────────────
function atualizarStats() {
  const itens = state[centroAtivo];
  let ok=0, menor=0, maior=0, nd=0;
  itens.forEach(i => {
    if (i.qtd_fisica === null) nd++;
    else if (i.qtd_fisica === i.qtd_sap) ok++;
    else if (i.qtd_fisica < i.qtd_sap) menor++;
    else maior++;
  });
  document.getElementById('stat-total').textContent  = itens.length + ' itens';
  document.getElementById('stat-ok').textContent     = ok + ' correto' + (ok!==1?'s':'');
  document.getElementById('stat-menor').textContent  = menor + ' a menos';
  document.getElementById('stat-maior').textContent  = maior + ' a mais';
  document.getElementById('stat-nd').textContent     = nd + ' não contado' + (nd!==1?'s':'');
}

// ── Exportar resumo ───────────────────────────────────────
function exportarResumo() {
  const itens = state[centroAtivo];
  if (!itens.length) return;

  const rows = [['Material','Descrição','Depósito','Qtd SAP','Qtd Física','Diferença','Status']];
  itens.forEach(i => {
    const f = i.qtd_fisica;
    const d = f !== null ? (f - i.qtd_sap) : '';
    const status = f === null ? 'Não contado' :
                   f === i.qtd_sap ? 'OK' :
                   f < i.qtd_sap   ? 'A MENOS'   : 'A MAIS';
    rows.push([i.material, i.descricao, i.deposito, i.qtd_sap, f !== null ? f : '', d !== '' ? (d>=0?'+':'')+d : '', status]);
  });

  const wb = XLSX.utils.book_new();
  const ws = XLSX.utils.aoa_to_sheet(rows);

  // Larguras de coluna
  ws['!cols'] = [10,45,12,12,12,12,14].map(w=>({wch:w}));

  XLSX.utils.book_append_sheet(wb, ws, 'Centro ' + centroAtivo);
  XLSX.writeFile(wb, `Inventario_Centro${centroAtivo}_${new Date().toISOString().slice(0,10)}.xlsx`);
  showToast('Resumo exportado!');
}

// ── Limpar ────────────────────────────────────────────────
function limparInventario() {
  if (!confirm('Limpar todo o inventário do Centro ' + centroAtivo + '?')) return;
  state[centroAtivo] = [];
  renderTabela();
  showToast('Inventário do Centro ' + centroAtivo + ' limpo.');
}

// ── Helpers ───────────────────────────────────────────────
function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function showToast(msg, err=false) {
  const t = document.getElementById('inv-toast');
  t.className = 'inv-toast' + (err?' err':'');
  t.innerHTML = `<span class="material-icons" style="font-size:1rem;">${err?'error':'check_circle'}</span> ${msg}`;
  t.classList.add('show');
  clearTimeout(t._t);
  t._t = setTimeout(() => t.classList.remove('show'), 3000);
}

// ── Nav drawer ────────────────────────────────────────────
function abrirDrawer(){document.getElementById('nav-drawer').classList.add('open');document.body.style.overflow='hidden';}
function fecharDrawer(){document.getElementById('nav-drawer').classList.remove('open');document.body.style.overflow='';}
document.addEventListener('keydown',e=>{if(e.key==='Escape')fecharDrawer();});

// ── Timer sessão ──────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function(){
  var remaining = <?= isset($tempo_restante) ? (int)$tempo_restante : 1800 ?>;
  var display = document.getElementById('timer-display');
  var timer_el = document.getElementById('session-timer');
  var warned = false;
  function update() {
    if (remaining <= 0) { window.location.href = 'logout.php?expired=1'; return; }
    var m = Math.floor(remaining/60), s = remaining%60;
    if (display) display.textContent = m+':'+(s<10?'0':'')+s;
    if (remaining<=300 && !warned) {
      warned=true;
      if (confirm('Sua sessão expira em 5 minutos! Clique OK para renovar.')) {
        fetch(window.location.href); remaining=1800; warned=false;
        if(timer_el){timer_el.style.background='';timer_el.style.borderColor='';}
      }
    }
    if (remaining<=60 && timer_el) { timer_el.style.background='#ffebee'; if(display) display.style.color='#e53935'; }
    remaining--;
    setTimeout(update,1000);
  }
  if (display) update();
});
</script>
</body>
</html>
