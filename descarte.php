<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Descarte de EPI — SCE</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;800&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
  <style>
    :root {
      --primary:       #0b4b80;
      --primary-light: #1565c0;
      --primary-dark:  #082f52;
      --accent:        #e8f4ff;
      --accent2:       #dbeeff;
      --danger:        #e53935;
      --danger-light:  #ffebee;
      --warn:          #f57c00;
      --success:       #00897b;
      --bg:            #f0f5fb;
      --surface:       #ffffff;
      --border:        #d6e8f7;
      --text:          #1a2535;
      --text-muted:    #607080;
      --radius:        16px;
      --radius-sm:     10px;
      --shadow-sm:     0 2px 8px rgba(11,75,128,0.08);
      --shadow:        0 4px 20px rgba(11,75,128,0.12);
      --shadow-lg:     0 8px 36px rgba(11,75,128,0.18);
      --transition:    all 0.2s cubic-bezier(.4,0,.2,1);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Roboto', Arial, sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }

    /* HEADER */
    header {
      background: linear-gradient(120deg, #5c0a0a 0%, #b71c1c 55%, #e53935 100%);
      position: sticky; top: 0; z-index: 100;
      box-shadow: 0 4px 24px rgba(92,10,10,0.3);
    }
    .header-inner {
      max-width: 1280px; margin: 0 auto; padding: 16px 28px;
      display: flex; align-items: center; justify-content: space-between;
    }
    .header-brand { display: flex; align-items: center; gap: 12px; }
    .header-brand .brand-icon {
      font-size: 2rem; color: rgba(255,255,255,0.9);
      background: rgba(255,255,255,0.15); padding: 8px; border-radius: 10px;
    }
    .header-brand h1 { font-size: 1.35rem; font-weight: 700; color: #fff; line-height: 1.2; }
    .header-brand .sub { font-size: 0.75rem; color: rgba(255,255,255,0.65); display: block; }

    /* NAV */
    nav.menu {
      background: rgba(255,255,255,0.97); border-bottom: 1px solid var(--border);
      box-shadow: 0 2px 10px rgba(11,75,128,0.07);
    }
    .nav-inner {
      max-width: 1280px; margin: 0 auto; padding: 0 28px;
      display: flex; align-items: center; justify-content: space-between; height: 52px;
    }
    .nav-links { display: flex; gap: 4px; height: 100%; align-items: center; }
    .nav-actions { display: flex; gap: 8px; align-items: center; }
    .menu-btn {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 7px 14px; border-radius: 8px; font-size: 0.9rem; font-weight: 600;
      color: var(--text-muted) !important; text-decoration: none;
      transition: var(--transition); border: 1px solid transparent; white-space: nowrap;
    }
    .menu-btn .material-icons { font-size: 1.1rem; }
    .menu-btn:hover { background: var(--accent); color: var(--primary) !important; border-color: var(--border); }
    .menu-btn.active { background: var(--danger); color: #fff !important; border-color: var(--danger); box-shadow: 0 3px 12px rgba(229,57,53,0.3); }
    .menu-btn.btn-graficos { background: linear-gradient(135deg,#0b4b80,#1565c0); color:#fff !important; border:none; box-shadow:0 3px 12px rgba(21,101,192,0.3); }
    .menu-btn.btn-graficos:hover { background: linear-gradient(135deg,#082f52,#0b4b80); }

    /* KPI STRIP */
    .kpi-strip {
      max-width: 1280px; margin: 28px auto 0; padding: 0 28px;
      display: grid; grid-template-columns: repeat(4,1fr); gap: 14px;
    }
    .kpi-card {
      background: var(--surface); border-radius: var(--radius);
      padding: 18px 20px; box-shadow: var(--shadow-sm); border: 1px solid var(--border);
      display: flex; align-items: center; gap: 14px;
      transition: var(--transition); position: relative; overflow: hidden;
    }
    .kpi-card::before {
      content:''; position:absolute; top:0; left:0;
      width:4px; height:100%; background: var(--danger); border-radius:16px 0 0 16px;
    }
    .kpi-card:hover { transform: translateY(-3px); box-shadow: var(--shadow); }
    .kpi-icon {
      width:46px; height:46px; border-radius:12px;
      background: var(--danger-light); display:flex; align-items:center; justify-content:center; flex-shrink:0;
    }
    .kpi-icon .material-icons { font-size:1.5rem; color: var(--danger); }
    .kpi-label { font-size:0.75rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; }
    .kpi-value { font-size:1.55rem; font-weight:800; color:var(--danger); line-height:1.1; margin-top:2px; }
    .kpi-desc  { font-size:0.75rem; color:var(--text-muted); margin-top:1px; }

    /* LAYOUT */
    .main-layout {
      max-width: 1280px; margin: 24px auto 0; padding: 0 28px;
      display: grid; grid-template-columns: 420px 1fr; gap: 22px; align-items: start;
    }

    /* CARD */
    .card { background:var(--surface); border-radius:var(--radius); box-shadow:var(--shadow-sm); border:1px solid var(--border); overflow:hidden; }
    .card-header {
      padding:18px 24px 16px; border-bottom:1px solid var(--accent2);
      display:flex; align-items:center; gap:10px;
      background: linear-gradient(135deg,#fff5f5,#ffe8e8);
    }
    .card-header .material-icons {
      font-size:1.4rem; color:var(--danger);
      background:var(--danger-light); padding:7px; border-radius:9px;
    }
    .card-header h2 { font-size:1rem; font-weight:700; color:#8b1a1a; }
    .card-body { padding:22px 24px 24px; }

    /* FIELDS */
    .field { margin-bottom:16px; }
    .field label { display:block; font-size:0.75rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px; }
    .field input, .field select, .field textarea {
      width:100%; padding:10px 14px; border:1.5px solid var(--border);
      border-radius:var(--radius-sm); font-size:0.95rem; color:var(--text);
      background:#fafcff; transition:var(--transition); outline:none; font-family:inherit;
    }
    .field input:focus, .field select:focus, .field textarea:focus {
      border-color: var(--danger); background:#fff; box-shadow:0 0 0 3px rgba(229,57,53,0.1);
    }
    .field input[readonly] { background:#f5f5f5; color:var(--text-muted); cursor:not-allowed; }
    .field textarea { resize:vertical; min-height:64px; max-height:120px; }
    .field-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .field-with-action { display:flex; gap:8px; align-items:center; }
    .field-with-action input, .field-with-action select { flex:1; }
    .field-hint { font-size:0.75rem; color:var(--text-muted); margin-top:4px; }
    .field-found {
      display:none; background:#f0faf8; border:1px solid #b2dfdb;
      border-radius:8px; padding:10px 14px; margin-top:6px;
      font-size:0.88rem; color:#00695c; font-weight:600;
      align-items:center; gap:8px;
    }
    .field-found.visible { display:flex; }
    .field-found .material-icons { font-size:1.1rem; }

    /* SELECT MOTIVO personalizado */
    .motivo-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-top:6px; }
    .motivo-btn {
      padding:10px 12px; border:1.5px solid var(--border);
      border-radius:9px; background:#fafcff; font-size:0.83rem;
      font-weight:600; color:var(--text-muted); cursor:pointer;
      transition:var(--transition); text-align:center; display:flex;
      flex-direction:column; align-items:center; gap:4px;
    }
    .motivo-btn .material-icons { font-size:1.2rem; color:var(--text-muted); }
    .motivo-btn:hover { border-color:var(--danger); color:var(--danger); background:var(--danger-light); }
    .motivo-btn.selected { border-color:var(--danger); background:var(--danger-light); color:var(--danger); font-weight:700; }
    .motivo-btn.selected .material-icons { color:var(--danger); }

    /* DIVIDER */
    .divider { height:1px; background:var(--accent2); margin:18px 0; }

    /* BUTTONS */
    .btn-primary {
      width:100%; padding:13px 20px;
      background:linear-gradient(135deg,#b71c1c,#e53935);
      color:#fff; border:none; border-radius:var(--radius-sm);
      font-size:0.97rem; font-weight:700; cursor:pointer;
      display:flex; align-items:center; justify-content:center; gap:8px;
      box-shadow:0 4px 14px rgba(229,57,53,0.35); transition:var(--transition);
      letter-spacing:0.3px; margin-top:6px;
    }
    .btn-primary:hover { background:linear-gradient(135deg,#7f0000,#b71c1c); box-shadow:0 6px 20px rgba(229,57,53,0.45); transform:translateY(-1px); }
    .btn-icon {
      background:none; border:1.5px solid var(--border); border-radius:8px; padding:8px;
      cursor:pointer; display:flex; align-items:center; color:var(--primary-light); transition:var(--transition);
    }
    .btn-icon:hover { background:var(--accent); border-color:var(--primary-light); }
    .btn-icon .material-icons { font-size:1.2rem; }

    /* PAINEL GRAFICOS */
    .graficos-panel { display:flex; flex-direction:column; gap:18px; }

    .chart-card { background:var(--surface); border-radius:var(--radius); box-shadow:var(--shadow-sm); border:1px solid var(--border); overflow:hidden; }
    .chart-card-header {
      padding:14px 20px; border-bottom:1px solid var(--accent2);
      display:flex; align-items:center; justify-content:space-between;
      background:linear-gradient(135deg,#f5f9ff,#eaf3ff);
    }
    .chart-card-header h3 {
      font-size:0.93rem; font-weight:700; color:var(--primary);
      display:flex; align-items:center; gap:7px;
    }
    .chart-card-header h3::before {
      content:''; display:inline-block; width:4px; height:14px;
      background:var(--danger); border-radius:2px;
    }
    .chart-card-body { padding:16px; min-height:220px; display:flex; align-items:center; justify-content:center; }
    .chart-card-body canvas { width:100% !important; max-height:220px; }
    .chart-actions { display:flex; gap:6px; align-items:center; }
    .btn-export {
      padding:5px 10px; border:1px solid var(--border); border-radius:7px;
      background:var(--accent); color:var(--primary); font-size:0.8rem;
      font-weight:600; cursor:pointer; transition:var(--transition);
    }
    .btn-export:hover { background:var(--primary); color:#fff; }

    /* HISTORICO */
    .historico-card { background:var(--surface); border-radius:var(--radius); box-shadow:var(--shadow-sm); border:1px solid var(--border); margin-top:0; }
    .historico-header {
      padding:14px 20px; border-bottom:1px solid var(--accent2);
      display:flex; align-items:center; justify-content:space-between;
      background:linear-gradient(135deg,#fff5f5,#ffe8e8);
    }
    .historico-header h3 { font-size:0.93rem; font-weight:700; color:#8b1a1a; display:flex; align-items:center; gap:7px; }
    .historico-filtro { display:flex; gap:8px; align-items:center; }
    .historico-filtro input[type="date"] {
      border:1px solid var(--border); border-radius:7px; padding:5px 10px;
      font-size:0.82rem; color:var(--primary); background:var(--accent);
      font-weight:600; outline:none; cursor:pointer;
    }
    .btn-filtrar { padding:5px 12px; border:none; border-radius:7px; background:var(--danger); color:#fff; font-size:0.82rem; font-weight:700; cursor:pointer; }

    .table-wrap { overflow-x:auto; max-height:280px; overflow-y:auto; }
    table.desc-table { width:100%; border-collapse:collapse; font-size:0.86rem; }
    table.desc-table th {
      background:linear-gradient(180deg,#fff5f5,#ffe8e8);
      color:#8b1a1a; font-weight:700; padding:10px 12px;
      text-align:left; font-size:0.78rem; text-transform:uppercase;
      letter-spacing:0.4px; position:sticky; top:0;
    }
    table.desc-table td { padding:9px 12px; border-bottom:1px solid #fce4e4; color:var(--text); }
    table.desc-table tr:hover td { background:#fff8f8; }
    .badge-motivo {
      display:inline-block; padding:3px 8px; border-radius:20px;
      font-size:0.75rem; font-weight:700; background:var(--danger-light); color:var(--danger);
    }

    /* PERIOD FILTER */
    .period-bar {
      max-width:1280px; margin:20px auto 0; padding:0 28px;
      display:flex; align-items:center; gap:14px; flex-wrap:wrap;
    }
    .period-bar-inner {
      background:var(--surface); border:1px solid var(--border);
      border-radius:var(--radius); padding:14px 20px;
      display:flex; align-items:center; gap:16px; flex-wrap:wrap;
      box-shadow:var(--shadow-sm); width:100%;
    }
    .period-label { font-size:0.78rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; white-space:nowrap; }
    .period-inputs { display:flex; align-items:center; gap:8px; background:var(--accent); padding:7px 12px; border-radius:9px; border:1px solid #c9e0f5; }
    .period-inputs input[type="date"] { border:none; background:transparent; font-size:0.93rem; color:var(--primary); font-weight:600; outline:none; cursor:pointer; }
    .period-inputs .sep { color:var(--text-muted); font-weight:700; }
    .btn-refresh { padding:9px 16px; background:var(--primary); color:#fff; border:none; border-radius:9px; font-size:0.9rem; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:6px; box-shadow:0 2px 8px rgba(11,75,128,0.2); transition:var(--transition); }
    .btn-refresh:hover { background:var(--primary-dark); }

    /* TOAST */
    .toast {
      position:fixed; bottom:28px; left:50%; transform:translateX(-50%) translateY(80px);
      background:#1b5e20; color:#fff; padding:13px 24px; border-radius:10px;
      font-weight:700; font-size:0.93rem; box-shadow:var(--shadow-lg);
      transition:transform 0.3s ease, opacity 0.3s ease; opacity:0; z-index:9999;
      display:flex; align-items:center; gap:8px;
    }
    .toast.show { transform:translateX(-50%) translateY(0); opacity:1; }
    .toast.error { background:#b71c1c; }

    /* RESPONSIVE */
    @media(max-width:960px){
      .main-layout { grid-template-columns:1fr; }
      .kpi-strip   { grid-template-columns:repeat(2,1fr); }
    }
    @media(max-width:500px){
      .kpi-strip   { grid-template-columns:1fr 1fr; }
      .motivo-grid { grid-template-columns:1fr 1fr; }
      .header-inner,.nav-inner,.main-layout,.kpi-strip,.period-bar { padding-left:14px; padding-right:14px; }
    }
  </style>
</head>
<body>

<!-- HEADER -->
<header>
  <div class="header-inner">
    <div class="header-brand">
      <span class="material-icons brand-icon">delete_forever</span>
      <div>
        <h1>Descarte de EPI</h1>
        <span class="sub">Registro e controle de equipamentos descartados</span>
      </div>
    </div>
  </div>
</header>

<!-- NAV -->
<nav class="menu">
  <div class="nav-inner">
    <div class="nav-links">
      <a href="/" class="menu-btn"><span class="material-icons">home</span>Início</a>
      <a href="estoque.php" class="menu-btn"><span class="material-icons">inventory_2</span>Estoque Atual</a>
      <a href="historico.php" class="menu-btn"><span class="material-icons">history</span>Histórico</a>
      <a href="fichas_epi.php" class="menu-btn"><span class="material-icons">description</span>Fichas de EPI</a>
      <a href="descarte.php" class="menu-btn active"><span class="material-icons">delete_forever</span>Descarte</a>
    </div>
    <div class="nav-actions">
      <a href="graficos.php" class="menu-btn btn-graficos"><span class="material-icons">bar_chart</span>Ver Gráficos</a>
    </div>
  </div>
</nav>

<!-- KPI STRIP -->
<div class="kpi-strip">
  <div class="kpi-card">
    <div class="kpi-icon"><span class="material-icons">delete_forever</span></div>
    <div>
      <div class="kpi-label">Total Descartado</div>
      <div class="kpi-value" id="kpi-total-desc">—</div>
      <div class="kpi-desc">Itens no período</div>
    </div>
  </div>
  <div class="kpi-card">
    <div class="kpi-icon"><span class="material-icons">attach_money</span></div>
    <div>
      <div class="kpi-label">Custo Descarte</div>
      <div class="kpi-value" id="kpi-custo-desc" style="font-size:1.2rem;">—</div>
      <div class="kpi-desc">Valor dos EPIs descartados</div>
    </div>
  </div>
  <div class="kpi-card">
    <div class="kpi-icon"><span class="material-icons">category</span></div>
    <div>
      <div class="kpi-label">Motivo Principal</div>
      <div class="kpi-value" id="kpi-motivo-principal" style="font-size:0.9rem;line-height:1.3;">—</div>
      <div class="kpi-desc">Causa mais frequente</div>
    </div>
  </div>
  <div class="kpi-card">
    <div class="kpi-icon"><span class="material-icons">inventory</span></div>
    <div>
      <div class="kpi-label">EPI Mais Descartado</div>
      <div class="kpi-value" id="kpi-epi-top" style="font-size:0.85rem;line-height:1.3;">—</div>
      <div class="kpi-desc">Maior volume</div>
    </div>
  </div>
</div>

<!-- PERIOD BAR -->
<div class="period-bar">
  <div class="period-bar-inner">
    <span class="period-label">Período dos Gráficos</span>
    <div class="period-inputs">
      <input type="date" id="filter-start">
      <span class="sep">→</span>
      <input type="date" id="filter-end">
    </div>
    <button class="btn-refresh" onclick="carregarTudo()">
      <span class="material-icons" style="font-size:1rem;">refresh</span> Atualizar
    </button>
  </div>
</div>

<!-- MAIN LAYOUT -->
<div class="main-layout" style="margin-top:20px;margin-bottom:40px;">

  <!-- COLUNA ESQUERDA: FORMULÁRIO -->
  <div class="card">
    <div class="card-header">
      <span class="material-icons">playlist_add</span>
      <h2>Registrar Descarte</h2>
    </div>
    <div class="card-body">

      <div class="field">
        <label>Nº do Item <span style="color:#e53935">*</span></label>
        <div class="field-with-action">
          <input type="text" id="numero-busca" placeholder="Ex: 986185" oninput="buscarItem()" autocomplete="off">
          <button class="btn-icon" onclick="document.getElementById('numero-busca').focus()" title="Buscar">
            <span class="material-icons">search</span>
          </button>
        </div>
        <div class="field-hint">Digite o número e o item será identificado automaticamente</div>
        <div class="field-found" id="item-encontrado">
          <span class="material-icons">check_circle</span>
          <span id="item-encontrado-nome"></span>
        </div>
      </div>

      <div class="field">
        <label>Nome do Item</label>
        <input type="text" id="nome-item" readonly placeholder="Preenchido automaticamente">
      </div>

      <input type="hidden" id="item-id-selecionado">

      <div class="field-row">
        <div class="field">
          <label>Quantidade <span style="color:#e53935">*</span></label>
          <input type="number" id="quantidade-descarte" min="1" value="1">
        </div>
        <div class="field">
          <label>Custo Unit. (R$)</label>
          <input type="number" id="custo-preview" readonly placeholder="Do cadastro">
        </div>
      </div>

      <div class="field">
        <label>Motivo do Descarte <span style="color:#e53935">*</span></label>
        <div class="motivo-grid" id="motivo-grid">
          <button type="button" class="motivo-btn" data-motivo="Fim de Vida Útil" onclick="selecionarMotivo(this)">
            <span class="material-icons">hourglass_empty</span>Fim de Vida Útil
          </button>
          <button type="button" class="motivo-btn" data-motivo="Danificado" onclick="selecionarMotivo(this)">
            <span class="material-icons">broken_image</span>Danificado
          </button>
          <button type="button" class="motivo-btn" data-motivo="Vencimento" onclick="selecionarMotivo(this)">
            <span class="material-icons">event_busy</span>Vencimento
          </button>
          <button type="button" class="motivo-btn" data-motivo="Contaminação" onclick="selecionarMotivo(this)">
            <span class="material-icons">warning</span>Contaminação
          </button>
          <button type="button" class="motivo-btn" data-motivo="Defeito de Fabricação" onclick="selecionarMotivo(this)">
            <span class="material-icons">build</span>Defeito de Fab.
          </button>
          <button type="button" class="motivo-btn" data-motivo="Outro" onclick="selecionarMotivo(this)">
            <span class="material-icons">more_horiz</span>Outro
          </button>
        </div>
        <input type="hidden" id="motivo-selecionado">
      </div>

      <div class="field" id="campo-outro" style="display:none;">
        <label>Especifique o motivo</label>
        <input type="text" id="motivo-outro" placeholder="Descreva o motivo...">
      </div>

      <div class="divider"></div>

      <div class="field">
        <label>Responsável pelo Descarte</label>
        <div class="field-with-action">
          <input type="text" id="matricula-desc" placeholder="Digite a matrícula (opcional)" maxlength="10" oninput="buscarResponsavelDesc()">
          <span id="status-matricula-desc" style="font-size:1.1rem;width:24px;text-align:center;"></span>
        </div>
      </div>
      <div class="field">
        <input type="text" id="responsavel-desc" readonly placeholder="Preenchido pela matrícula" style="background:#f5f5f5;color:#607080;">
      </div>

      <div class="field">
        <label>Observação</label>
        <textarea id="observacao-descarte" placeholder="Informações adicionais sobre o descarte (opcional)"></textarea>
      </div>

      <button class="btn-primary" onclick="registrarDescarte()">
        <span class="material-icons">delete_forever</span>Registrar Descarte
      </button>

    </div>
  </div>

  <!-- COLUNA DIREITA: GRÁFICOS + HISTÓRICO -->
  <div class="graficos-panel">

    <!-- Gráfico: Motivo do Descarte -->
    <div class="chart-card">
      <div class="chart-card-header">
        <h3>Descartes por Motivo</h3>
        <div class="chart-actions">
          <button class="btn-export" onclick="exportChart('chartMotivo')">Exportar PNG</button>
        </div>
      </div>
      <div class="chart-card-body"><canvas id="chartMotivo"></canvas></div>
    </div>

    <!-- Gráfico: Custo por EPI -->
    <div class="chart-card">
      <div class="chart-card-header">
        <h3>Custo Total de Descarte por EPI (R$)</h3>
        <div class="chart-actions">
          <button class="btn-export" onclick="exportChart('chartCustoDesc')">Exportar PNG</button>
        </div>
      </div>
      <div class="chart-card-body"><canvas id="chartCustoDesc"></canvas></div>
    </div>

    <!-- Histórico de descartes -->
    <div class="historico-card">
      <div class="historico-header">
        <h3><span class="material-icons" style="font-size:1.1rem;">history</span>Histórico de Descartes</h3>
        <button class="btn-filtrar" onclick="exportarPDF()">
          <span class="material-icons" style="font-size:0.95rem;vertical-align:-3px;">download</span> PDF
        </button>
      </div>
      <div class="table-wrap">
        <table class="desc-table">
          <thead>
            <tr>
              <th>Data</th>
              <th>Item</th>
              <th>Nº</th>
              <th>Qtd</th>
              <th>Motivo</th>
              <th>Custo Total</th>
              <th>Responsável</th>
            </tr>
          </thead>
          <tbody id="tabela-descartes">
            <tr><td colspan="7" style="text-align:center;color:#999;padding:20px;">Carregando...</td></tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast"></div>

<script>
// ── Estado ──────────────────────────────────────────────────────────
let itensEstoque = [];
let descartes    = [];
let charts       = {};
let debounceTimer = null;
let motivoSelecionado = '';

// ── Init ─────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  const hoje  = new Date();
  const inicio = new Date(hoje.getFullYear(), hoje.getMonth() - 1, hoje.getDate());
  document.getElementById('filter-start').value = inicio.toISOString().slice(0,10);
  document.getElementById('filter-end').value   = hoje.toISOString().slice(0,10);

  carregarItens().then(carregarTudo);
});

// ── Carregar itens do estoque ─────────────────────────────────────────
async function carregarItens() {
  try {
    const res = await fetch('listar_itens.php');
    itensEstoque = await res.json();
  } catch(e) { itensEstoque = []; }
}

// ── Busca automática de item pelo número ──────────────────────────────
function buscarItem() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    const num = document.getElementById('numero-busca').value.trim();
    const found = document.getElementById('item-encontrado');
    const nomeEl = document.getElementById('nome-item');
    const idEl   = document.getElementById('item-id-selecionado');
    const custoEl = document.getElementById('custo-preview');

    if (!num) {
      found.classList.remove('visible');
      nomeEl.value = ''; idEl.value = ''; custoEl.value = '';
      return;
    }

    const item = itensEstoque.find(i =>
      (i.numero_item || '').trim().toLowerCase() === num.toLowerCase()
    );

    if (item) {
      nomeEl.value  = item.nome;
      idEl.value    = item.id;
      custoEl.value = Number(item.custo_unitario || 0).toFixed(2);
      document.getElementById('item-encontrado-nome').textContent = item.nome;
      found.classList.add('visible');
    } else {
      nomeEl.value = ''; idEl.value = ''; custoEl.value = '';
      found.classList.remove('visible');
    }
  }, 350);
}

// ── Seleção de motivo ─────────────────────────────────────────────────
function selecionarMotivo(btn) {
  document.querySelectorAll('.motivo-btn').forEach(b => b.classList.remove('selected'));
  btn.classList.add('selected');
  motivoSelecionado = btn.dataset.motivo;
  document.getElementById('motivo-selecionado').value = motivoSelecionado;
  document.getElementById('campo-outro').style.display =
    motivoSelecionado === 'Outro' ? 'block' : 'none';
}

// ── Busca responsável pela matrícula ──────────────────────────────────
let debounceResp = null;
function buscarResponsavelDesc() {
  clearTimeout(debounceResp);
  debounceResp = setTimeout(() => {
    const mat = document.getElementById('matricula-desc').value.trim();
    const status = document.getElementById('status-matricula-desc');
    const nome   = document.getElementById('responsavel-desc');
    if (mat.length < 5) { nome.value = ''; status.textContent = ''; return; }
    fetch('buscar_colaborador.php?matricula=' + encodeURIComponent(mat))
      .then(r => r.json())
      .then(d => {
        if (d.status === 'ok') { nome.value = d.colaborador.nome; status.textContent = '✅'; }
        else { nome.value = ''; status.textContent = '❌'; }
      });
  }, 400);
}

// ── Registrar descarte ────────────────────────────────────────────────
async function registrarDescarte() {
  const item_id    = document.getElementById('item-id-selecionado').value;
  const quantidade = parseInt(document.getElementById('quantidade-descarte').value);
  const motivo     = motivoSelecionado === 'Outro'
    ? document.getElementById('motivo-outro').value.trim()
    : motivoSelecionado;
  const observacao  = document.getElementById('observacao-descarte').value.trim();
  const responsavel = document.getElementById('responsavel-desc').value.trim();
  const matricula   = document.getElementById('matricula-desc').value.trim();

  if (!item_id)           { showToast('Informe e selecione o Nº do item.', true); return; }
  if (!quantidade || quantidade < 1) { showToast('Informe a quantidade.', true); return; }
  if (!motivo)            { showToast('Selecione o motivo do descarte.', true); return; }

  const res  = await fetch('registrar_descarte.php', {
    method: 'POST',
    body: new URLSearchParams({ item_id, quantidade, motivo, observacao, responsavel, matricula })
  });
  const data = await res.json();

  if (data.status === 'ok') {
    showToast('Descarte registrado com sucesso!');
    limparFormulario();
    carregarTudo();
  } else {
    showToast(data.mensagem || 'Erro ao registrar.', true);
  }
}

function limparFormulario() {
  document.getElementById('numero-busca').value = '';
  document.getElementById('nome-item').value    = '';
  document.getElementById('item-id-selecionado').value = '';
  document.getElementById('custo-preview').value = '';
  document.getElementById('quantidade-descarte').value = 1;
  document.getElementById('item-encontrado').classList.remove('visible');
  document.querySelectorAll('.motivo-btn').forEach(b => b.classList.remove('selected'));
  motivoSelecionado = '';
  document.getElementById('campo-outro').style.display = 'none';
  document.getElementById('motivo-outro').value = '';
  document.getElementById('observacao-descarte').value = '';
  document.getElementById('matricula-desc').value = '';
  document.getElementById('responsavel-desc').value = '';
  document.getElementById('status-matricula-desc').textContent = '';
}

// ── Carregar tudo ─────────────────────────────────────────────────────
async function carregarTudo() {
  const start = document.getElementById('filter-start').value;
  const end   = document.getElementById('filter-end').value;
  let url = 'listar_descartes.php';
  if (start && end) url += `?start=${start}&end=${end}`;

  try {
    const res = await fetch(url);
    descartes = await res.json();
  } catch(e) { descartes = []; }

  renderKPIs();
  renderChartMotivo();
  renderChartCusto();
  renderTabela();
}

// ── KPIs ──────────────────────────────────────────────────────────────
function renderKPIs() {
  const total     = descartes.reduce((s,d) => s + Number(d.quantidade||0), 0);
  const custo     = descartes.reduce((s,d) => s + Number(d.custo_total||0), 0);

  const motivoMap = {};
  descartes.forEach(d => { motivoMap[d.motivo] = (motivoMap[d.motivo]||0) + Number(d.quantidade||0); });
  const topMotivo = Object.entries(motivoMap).sort((a,b)=>b[1]-a[1])[0];

  const epiMap = {};
  descartes.forEach(d => { epiMap[d.nome] = (epiMap[d.nome]||0) + Number(d.quantidade||0); });
  const topEpi = Object.entries(epiMap).sort((a,b)=>b[1]-a[1])[0];

  document.getElementById('kpi-total-desc').textContent    = total || '0';
  document.getElementById('kpi-custo-desc').textContent    = 'R$ ' + custo.toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2});
  document.getElementById('kpi-motivo-principal').textContent = topMotivo ? topMotivo[0] : '—';
  document.getElementById('kpi-epi-top').textContent       = topEpi ? topEpi[0] : '—';
}

// ── Gráfico: Motivo ───────────────────────────────────────────────────
function renderChartMotivo() {
  const map = {};
  descartes.forEach(d => { map[d.motivo] = (map[d.motivo]||0) + Number(d.quantidade||0); });
  const labels = Object.keys(map);
  const data   = Object.values(map);

  const colors = ['#e53935','#f57c00','#fbc02d','#7b1fa2','#0288d1','#00897b'];

  const cfg = {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{ data, backgroundColor: colors.slice(0,labels.length), borderWidth:2, borderColor:'#fff' }]
    },
    options: {
      responsive:true, maintainAspectRatio:false,
      plugins:{
        legend:{ position:'right', labels:{ font:{ size:12 }, boxWidth:14, padding:14 } },
        tooltip:{ callbacks:{ label: ctx => ` ${ctx.label}: ${ctx.raw} unid.` } }
      },
      cutout: '60%'
    }
  };
  createOrUpdateChart('chartMotivo', cfg);
}

// ── Gráfico: Custo por EPI ────────────────────────────────────────────
function renderChartCusto() {
  const map = {};
  descartes.forEach(d => {
    if (Number(d.custo_total||0) > 0)
      map[d.nome] = (map[d.nome]||0) + Number(d.custo_total||0);
  });

  if (!Object.keys(map).length) {
    if (charts['chartCustoDesc']) { charts['chartCustoDesc'].destroy(); delete charts['chartCustoDesc']; }
    const ctx = document.getElementById('chartCustoDesc').getContext('2d');
    ctx.clearRect(0,0,9999,9999);
    ctx.font = '13px Roboto'; ctx.fillStyle = '#999';
    ctx.fillText('Sem dados de custo para o período.', 10, 40);
    return;
  }

  const sorted = Object.entries(map).sort((a,b)=>b[1]-a[1]);
  const labels = sorted.map(e=>e[0]);
  const data   = sorted.map(e=>e[1]);

  const cfg = {
    type:'bar',
    data:{ labels, datasets:[{ label:'Custo (R$)', data, backgroundColor:'#e5393580', borderColor:'#e53935', borderWidth:1.5, borderRadius:5 }] },
    options:{
      indexAxis:'y', responsive:true, maintainAspectRatio:false,
      plugins:{ legend:{ display:false }, tooltip:{ callbacks:{ label: ctx => ' R$ ' + ctx.raw.toLocaleString('pt-BR',{minimumFractionDigits:2}) } } },
      scales:{ x:{ beginAtZero:true, ticks:{ callback: v => 'R$ ' + v.toLocaleString('pt-BR') } } }
    }
  };
  createOrUpdateChart('chartCustoDesc', cfg);
}

// ── Tabela histórico ──────────────────────────────────────────────────
function renderTabela() {
  const tbody = document.getElementById('tabela-descartes');
  if (!descartes.length) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#999;padding:20px;">Nenhum descarte no período.</td></tr>';
    return;
  }
  tbody.innerHTML = descartes.map(d => `
    <tr>
      <td>${(d.data||'').slice(0,10)}</td>
      <td>${escHtml(d.nome||'')}</td>
      <td><code style="background:#fce4e4;padding:2px 6px;border-radius:4px;font-size:0.8rem;">${escHtml(d.numero_item||'')}</code></td>
      <td><strong>${d.quantidade}</strong></td>
      <td><span class="badge-motivo">${escHtml(d.motivo||'')}</span></td>
      <td>${Number(d.custo_total||0) > 0 ? '<strong>R$ ' + Number(d.custo_total).toLocaleString('pt-BR',{minimumFractionDigits:2}) + '</strong>' : '<span style="color:#ccc">—</span>'}</td>
      <td>${escHtml(d.responsavel||'—')}</td>
    </tr>`).join('');
}

// ── Helpers ───────────────────────────────────────────────────────────
function createOrUpdateChart(id, cfg) {
  if (charts[id]) { charts[id].destroy(); }
  const ctx = document.getElementById(id);
  if (!ctx) return;
  charts[id] = new Chart(ctx, cfg);
}

function exportChart(id) {
  const canvas = document.getElementById(id);
  if (!canvas) return;
  const a = document.createElement('a');
  a.download = id + '.png';
  a.href = canvas.toDataURL('image/png');
  a.click();
}

function exportarPDF() {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({ orientation:'landscape' });
  doc.setFontSize(14);
  doc.text('Histórico de Descartes de EPI', 14, 16);
  doc.setFontSize(9);
  doc.text(`Período: ${document.getElementById('filter-start').value} a ${document.getElementById('filter-end').value}`, 14, 23);

  const rows = descartes.map(d => [
    (d.data||'').slice(0,10),
    d.nome||'',
    d.numero_item||'',
    d.quantidade,
    d.motivo||'',
    Number(d.custo_total||0) > 0 ? 'R$ ' + Number(d.custo_total).toLocaleString('pt-BR',{minimumFractionDigits:2}) : '—',
    d.responsavel||'—'
  ]);

  doc.autoTable({
    startY: 28,
    head: [['Data','Item','Nº','Qtd','Motivo','Custo Total','Responsável']],
    body: rows,
    headStyles:{ fillColor:[183,28,28], textColor:255, fontStyle:'bold' },
    alternateRowStyles:{ fillColor:[255,245,245] },
    styles:{ fontSize:8, cellPadding:4 }
  });
  doc.save('descartes.pdf');
}

function showToast(msg, error=false) {
  const t = document.getElementById('toast');
  t.className = 'toast' + (error ? ' error' : '');
  t.innerHTML = `<span class="material-icons" style="font-size:1.1rem;">${error ? 'error' : 'check_circle'}</span> ${msg}`;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3200);
}

function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>
</body>
</html>
