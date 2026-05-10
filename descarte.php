<?php require 'session.php'; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Descarte de EPI — GEM</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
  <style>
    /* ── Descarte: CSS específico ───────────────────────── */

    /* Header vermelho para diferenciar */
    header { background: linear-gradient(120deg, #5c0a0a 0%, #b71c1c 55%, #e53935 100%) !important; }
    nav.menu .menu-btn.active { background: var(--danger) !important; border-color: var(--danger) !important; }

    /* KPI danger strip */
    .kpi-card::before { background: var(--danger); }
    .kpi-card .kpi-icon { background: #ffebee; }
    .kpi-card .kpi-icon .material-icons { color: var(--danger); }
    .kpi-card .kpi-value { color: var(--danger); }

    /* Layout 2 colunas */
    .descarte-layout {
      max-width: 1280px;
      margin: 24px auto 48px;
      padding: 0 var(--page-px, 28px);
      display: grid;
      grid-template-columns: 420px 1fr;
      gap: 20px;
      align-items: start;
    }

    /* Card header vermelho */
    .card-header-red {
      padding: 16px 20px 14px;
      border-bottom: 1px solid #fce4e4;
      display: flex; align-items: center; gap: 10px;
      background: linear-gradient(135deg, #fff5f5, #ffe8e8);
    }
    .card-header-red .material-icons {
      font-size: 1.3rem; color: var(--danger);
      background: #ffebee; padding: 6px; border-radius: 8px;
    }
    .card-header-red h2 { font-size: 0.97rem; font-weight: 700; color: #8b1a1a; }

    /* Busca item */
    .field-found {
      display: none; background: #f0faf8; border: 1px solid #b2dfdb;
      border-radius: 8px; padding: 10px 14px; margin-top: 6px;
      font-size: 0.88rem; color: #00695c; font-weight: 600;
      align-items: center; gap: 8px;
    }
    .field-found.visible { display: flex; }

    /* Motivo grid */
    .motivo-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 6px; }
    .motivo-btn {
      padding: 10px 8px; border: 1.5px solid var(--border);
      border-radius: 9px; background: #fafcff; font-size: 0.82rem;
      font-weight: 600; color: var(--text-muted); cursor: pointer;
      transition: all 0.18s; text-align: center;
      display: flex; flex-direction: column; align-items: center; gap: 4px;
      min-height: 44px;
    }
    .motivo-btn .material-icons { font-size: 1.1rem; }
    .motivo-btn:hover { border-color: var(--danger); color: var(--danger); background: #ffebee; }
    .motivo-btn.selected { border-color: var(--danger); background: #ffebee; color: var(--danger); font-weight: 700; }
    .motivo-btn.selected .material-icons { color: var(--danger); }

    /* Btn primário vermelho */
    .btn-danger {
      width: 100%; padding: 13px 20px;
      background: linear-gradient(135deg, #b71c1c, #e53935);
      color: #fff; border: none; border-radius: var(--radius-sm, 10px);
      font-size: 1rem; font-weight: 700; cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      gap: 8px; box-shadow: 0 4px 14px rgba(229,57,53,0.35);
      transition: all 0.18s; margin-top: 6px; min-height: 48px;
    }
    .btn-danger:hover { background: linear-gradient(135deg, #7f0000, #b71c1c); }
    .btn-danger:active { transform: scale(0.98); }
    .btn-danger:disabled { background: #e0bebe; box-shadow: none; cursor: not-allowed; }

    /* Btn add lote vermelho */
    .btn-add-lote {
      width: 100%; padding: 10px;
      background: #ffebee; border: 1.5px dashed var(--danger);
      border-radius: var(--radius-sm, 10px); color: var(--danger);
      font-weight: 700; font-size: 0.9rem; cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      gap: 6px; transition: all 0.18s; min-height: 44px;
    }
    .btn-add-lote:hover { background: #ffcdd2; border-style: solid; }

    /* Lote lista */
    .lote-lista { margin: 10px 0 0; border: 1.5px solid #ffc4c4; border-radius: var(--radius-sm, 10px); overflow: hidden; }
    .lote-lista-header { background: #fff0f0; padding: 8px 14px; font-size: 0.75rem; font-weight: 700; color: #8b1a1a; text-transform: uppercase; letter-spacing: 0.4px; display: flex; justify-content: space-between; align-items: center; }
    .lote-lista-header span { font-weight: 400; color: var(--danger); font-size: 0.82rem; }
    .lote-vazia { padding: 14px; text-align: center; font-size: 0.85rem; color: #c4a4a4; }
    .lote-item { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-top: 1px solid #fce4e4; gap: 8px; }
    .lote-item:nth-child(even) { background: #fffafa; }
    .lote-item-info { flex: 1; min-width: 0; }
    .lote-item-nome { font-weight: 600; color: #3a1a1a; font-size: 0.87rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .lote-item-meta { font-size: 0.75rem; color: #a07070; margin-top: 2px; }
    .lote-item-qtd { font-weight: 800; color: var(--danger); background: #ffebee; padding: 3px 10px; border-radius: 20px; font-size: 0.85rem; white-space: nowrap; }
    .lote-item-remove { background: none; border: none; cursor: pointer; color: #dca0a0; padding: 4px; border-radius: 4px; transition: color 0.15s; display: flex; align-items: center; min-width: 32px; min-height: 32px; justify-content: center; }
    .lote-item-remove:hover { color: var(--danger); }
    .lote-badge { display: inline-flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.3); color: #fff; font-size: 0.72rem; font-weight: 800; width: 18px; height: 18px; border-radius: 50%; margin-left: 4px; }

    /* Charts painel */
    .graficos-panel { display: flex; flex-direction: column; gap: 18px; }
    .chart-card { background: var(--surface); border-radius: var(--radius, 16px); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden; }
    .chart-card-header { padding: 14px 18px; border-bottom: 1px solid var(--accent2); display: flex; align-items: center; justify-content: space-between; background: linear-gradient(135deg,#f5f9ff,#eaf3ff); }
    .chart-card-header h3 { font-size: 0.92rem; font-weight: 700; color: var(--primary); display: flex; align-items: center; gap: 7px; }
    .chart-card-header h3::before { content:''; display:inline-block; width:4px; height:14px; background:var(--danger); border-radius:2px; }
    .chart-card-body { padding: 16px; min-height: 200px; display: flex; align-items: center; justify-content: center; }
    .chart-card-body canvas { width: 100% !important; max-height: 220px; }
    .btn-export { padding: 5px 10px; border: 1px solid var(--border); border-radius: 7px; background: var(--accent); color: var(--primary); font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: all 0.18s; }
    .btn-export:hover { background: var(--primary); color: #fff; }

    /* Historico */
    .historico-card { background: var(--surface); border-radius: var(--radius, 16px); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden; }
    .historico-header { padding: 14px 18px; border-bottom: 1px solid #fce4e4; display: flex; align-items: center; justify-content: space-between; background: linear-gradient(135deg,#fff5f5,#ffe8e8); }
    .historico-header h3 { font-size: 0.92rem; font-weight: 700; color: #8b1a1a; display: flex; align-items: center; gap: 7px; }
    .btn-pdf { padding: 6px 12px; border: none; border-radius: 7px; background: var(--danger); color: #fff; font-size: 0.82rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 5px; }
    .table-wrap { overflow-x: auto; max-height: 300px; overflow-y: auto; }
    table.desc-table { width: 100%; border-collapse: collapse; font-size: 0.86rem; }
    table.desc-table th { background: linear-gradient(180deg,#fff5f5,#ffe8e8); color: #8b1a1a; font-weight: 700; padding: 10px 12px; text-align: left; font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.4px; position: sticky; top: 0; }
    table.desc-table td { padding: 9px 12px; border-bottom: 1px solid #fce4e4; }
    table.desc-table tr:hover td { background: #fff8f8; }
    .badge-motivo { display: inline-block; padding: 3px 8px; border-radius: 20px; font-size: 0.73rem; font-weight: 700; background: #ffebee; color: var(--danger); }

    /* Period bar */
    .period-bar { max-width: 1280px; margin: 20px auto 0; padding: 0 var(--page-px, 28px); }
    .period-bar-inner { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius, 16px); padding: 14px 20px; display: flex; align-items: center; gap: 16px; flex-wrap: wrap; box-shadow: var(--shadow-sm); }
    .period-label { font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
    .period-inputs { display: flex; align-items: center; gap: 8px; background: var(--accent); padding: 7px 12px; border-radius: 9px; border: 1px solid #c9e0f5; }
    .period-inputs input[type="date"] { border: none; background: transparent; font-size: 0.93rem; color: var(--primary); font-weight: 600; outline: none; cursor: pointer; }
    .period-inputs .sep { color: var(--text-muted); font-weight: 700; }
    .btn-refresh { padding: 9px 16px; background: var(--primary); color: #fff; border: none; border-radius: 9px; font-size: 0.9rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 6px; box-shadow: 0 2px 8px rgba(11,75,128,0.2); }

    /* Toast */
    .toast { position: fixed; bottom: 28px; left: 50%; transform: translateX(-50%) translateY(80px); background: #1b5e20; color: #fff; padding: 13px 24px; border-radius: 10px; font-weight: 700; font-size: 0.93rem; box-shadow: var(--shadow-lg); transition: transform 0.3s ease, opacity 0.3s ease; opacity: 0; z-index: 9999; display: flex; align-items: center; gap: 8px; }
    .toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
    .toast.error { background: #b71c1c; }

    /* Responsive */
    @media (max-width: 960px) { .descarte-layout { grid-template-columns: 1fr; } }
    @media (max-width: 600px) { .motivo-grid { grid-template-columns: 1fr 1fr; } }
  </style>
</head>
<body>

<header>
  <div class="header-inner">
    <div class="header-brand">
      <span class="material-icons">delete_forever</span>
      <div>
        <h1>Descarte de EPI</h1>
        <span class="sub">Registro e controle de equipamentos descartados</span>
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
      <a href="descarte.php" class="menu-btn active"><span class="material-icons">delete_forever</span>Descarte</a>
    </div>
    <div class="nav-actions">
      <!-- Timer de sessão -->
<div id="session-timer" style="display:flex;align-items:center;gap:5px;font-size:0.8rem;font-weight:700;color:#607080;background:var(--accent,#e8f4ff);padding:5px 10px;border-radius:8px;border:1px solid var(--border,#d6e8f7);">
  <span class="material-icons" style="font-size:1rem;color:#1565c0;">timer</span>
  <span id="timer-display">30:00</span>
</div>
      <a href="graficos.php" class="menu-btn btn-graficos"><span class="material-icons">bar_chart</span>Ver Gráficos</a>
    
      <a href="logout.php" class="menu-btn" style="color:#e53935!important;border-color:#ffcdd2;" title="Sair"><span class="material-icons">logout</span>Sair</a>
    </div>
    <button class="nav-hamburger" onclick="abrirDrawer()" aria-label="Menu">
      <span class="material-icons">menu</span>
    </button>
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
        <a href="descarte.php" class="active"><span class="material-icons">delete_forever</span>Descarte</a>
        <a href="graficos.php" class="drawer-graficos"><span class="material-icons">bar_chart</span>Ver Gráficos</a>
      </div>
    </div>
  </div>
</nav>

<!-- KPI STRIP -->
<div class="kpi-strip">
  <div class="kpi-card">
    <div class="kpi-icon"><span class="material-icons">delete_forever</span></div>
    <div class="kpi-info">
      <div class="kpi-label">Total Descartado</div>
      <div class="kpi-value" id="kpi-total-desc">—</div>
      <div class="kpi-desc">Itens no período</div>
    </div>
  </div>
  <div class="kpi-card">
    <div class="kpi-icon"><span class="material-icons">attach_money</span></div>
    <div class="kpi-info">
      <div class="kpi-label">Custo Descarte</div>
      <div class="kpi-value" id="kpi-custo-desc" style="font-size:1.1rem;">—</div>
      <div class="kpi-desc">Valor dos EPIs descartados</div>
    </div>
  </div>
  <div class="kpi-card">
    <div class="kpi-icon"><span class="material-icons">category</span></div>
    <div class="kpi-info">
      <div class="kpi-label">Motivo Principal</div>
      <div class="kpi-value" id="kpi-motivo-principal" style="font-size:0.88rem;line-height:1.3;">—</div>
      <div class="kpi-desc">Causa mais frequente</div>
    </div>
  </div>
  <div class="kpi-card">
    <div class="kpi-icon"><span class="material-icons">inventory</span></div>
    <div class="kpi-info">
      <div class="kpi-label">EPI Mais Descartado</div>
      <div class="kpi-value" id="kpi-epi-top" style="font-size:0.82rem;line-height:1.3;">—</div>
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
<div class="descarte-layout">

  <!-- FORMULÁRIO -->
  <div class="card">
    <div class="card-header-red">
      <span class="material-icons">playlist_add</span>
      <h2>Registrar Descarte</h2>
    </div>
    <div class="card-body">

      <div class="field">
        <label>Nº do Item <span style="color:var(--danger)">*</span></label>
        <div class="field-with-action">
          <input type="text" id="numero-busca" placeholder="Ex: 986185" oninput="buscarItem()" autocomplete="off">
          <button class="btn-icon" onclick="document.getElementById('numero-busca').focus()" title="Buscar">
            <span class="material-icons">search</span>
          </button>
        </div>
        <p style="font-size:0.75rem;color:var(--text-muted);margin-top:4px;">Digite o número — o item é identificado automaticamente</p>
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
          <label>Quantidade <span style="color:var(--danger)">*</span></label>
          <input type="number" id="quantidade-descarte" min="1" value="1">
        </div>
        <div class="field">
          <label>Custo Unit. (R$)</label>
          <input type="number" id="custo-preview" readonly placeholder="Do cadastro">
        </div>
      </div>

      <div class="field">
        <label>Motivo do Descarte <span style="color:var(--danger)">*</span></label>
        <div class="motivo-grid">
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

      <!-- BOTÃO ADICIONAR AO LOTE -->
      <button class="btn-add-lote" onclick="adicionarAoLote()">
        <span class="material-icons">add_shopping_cart</span>
        Adicionar ao lote de descarte
      </button>

      <!-- LISTA DO LOTE -->
      <div class="lote-lista">
        <div class="lote-lista-header">
          Itens no lote
          <span id="lote-contador">0 itens</span>
        </div>
        <div id="lote-itens">
          <div class="lote-vazia">Nenhum item adicionado ainda</div>
        </div>
      </div>

      <div class="divider"></div>

      <div class="field">
        <label>Matrícula do Responsável</label>
        <div class="field-with-action">
          <input type="text" id="matricula-desc" placeholder="Digite a matrícula (opcional)" maxlength="10" oninput="buscarResponsavelDesc()">
          <span id="status-matricula-desc" class="field-status"></span>
        </div>
      </div>
      <div class="field">
        <input type="text" id="responsavel-desc" readonly placeholder="Preenchido pela matrícula" style="background:#f0f5fb;color:var(--text-muted);">
      </div>

      <div class="field">
        <label>Observação</label>
        <textarea id="observacao-descarte" placeholder="Informações adicionais (opcional)"></textarea>
      </div>

      <button class="btn-danger" id="btn-registrar-lote" onclick="registrarLote()" disabled>
        <span class="material-icons">delete_forever</span>
        Registrar lote
        <span id="lote-btn-count" class="lote-badge" style="display:none;">0</span>
      </button>

    </div>
  </div>

  <!-- GRÁFICOS + HISTÓRICO -->
  <div class="graficos-panel">

    <div class="chart-card">
      <div class="chart-card-header">
        <h3>Descartes por Motivo</h3>
        <button class="btn-export" onclick="exportChart('chartMotivo')">Exportar PNG</button>
      </div>
      <div class="chart-card-body"><canvas id="chartMotivo"></canvas></div>
    </div>

    <div class="chart-card">
      <div class="chart-card-header">
        <h3>Custo Total de Descarte por EPI (R$)</h3>
        <button class="btn-export" onclick="exportChart('chartCustoDesc')">Exportar PNG</button>
      </div>
      <div class="chart-card-body"><canvas id="chartCustoDesc"></canvas></div>
    </div>

    <div class="historico-card">
      <div class="historico-header">
        <h3><span class="material-icons" style="font-size:1.1rem;vertical-align:-3px;">history</span>Histórico de Descartes</h3>
        <button class="btn-pdf" onclick="exportarPDF()">
          <span class="material-icons" style="font-size:0.95rem;">download</span> PDF
        </button>
      </div>
      <div class="table-wrap">
        <table class="desc-table">
          <thead>
            <tr>
              <th>Data</th><th>Item</th><th>Nº</th><th>Qtd</th>
              <th>Motivo</th><th>Custo</th><th>Responsável</th>
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

<div class="toast" id="toast"></div>

<script>
function abrirDrawer() { document.getElementById('nav-drawer').classList.add('open'); document.body.style.overflow='hidden'; }
function fecharDrawer() { document.getElementById('nav-drawer').classList.remove('open'); document.body.style.overflow=''; }
document.addEventListener('keydown', e => { if(e.key==='Escape') fecharDrawer(); });
</script>

<script>

// ── Estado ──────────────────────────────────────────────────────────
let itensEstoque    = [];
let descartes       = [];
let charts          = {};
let debounceTimer   = null;
let motivoSelecionado = '';
let loteDescarte    = []; // itens aguardando registro

// ── Init ─────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  const hoje  = new Date();
  const inicio = new Date(hoje.getFullYear(), hoje.getMonth() - 1, hoje.getDate());
  document.getElementById('filter-start').value = inicio.toISOString().slice(0,10);
  document.getElementById('filter-end').value   = hoje.toISOString().slice(0,10);

  carregarItens().then(carregarTudo);
  renderLote();
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
// ── LOTE: Adicionar ──────────────────────────────────────────────────
function adicionarAoLote() {
  const item_id  = document.getElementById('item-id-selecionado').value;
  const nome     = document.getElementById('nome-item').value.trim();
  const numero   = document.getElementById('numero-busca').value.trim();
  const quantidade = parseInt(document.getElementById('quantidade-descarte').value);
  const motivo   = motivoSelecionado === 'Outro'
    ? document.getElementById('motivo-outro').value.trim()
    : motivoSelecionado;
  const custoUnit = parseFloat(document.getElementById('custo-preview').value || 0);

  if (!item_id)                          { showToast('Selecione um item pelo Nº.', true); return; }
  if (!quantidade || quantidade < 1)     { showToast('Informe a quantidade.', true); return; }
  if (!motivo)                           { showToast('Selecione o motivo.', true); return; }

  // Agrupa se mesmo item+motivo já está no lote
  const existente = loteDescarte.find(i => i.item_id === item_id && i.motivo === motivo);
  if (existente) {
    existente.quantidade += quantidade;
    existente.custo_total = existente.quantidade * existente.custo_unit;
  } else {
    loteDescarte.push({ item_id, nome, numero, quantidade, motivo, custo_unit: custoUnit, custo_total: custoUnit * quantidade });
  }

  // Limpar campos do item
  document.getElementById('numero-busca').value = '';
  document.getElementById('nome-item').value    = '';
  document.getElementById('item-id-selecionado').value = '';
  document.getElementById('custo-preview').value = '';
  document.getElementById('item-encontrado').classList.remove('visible');
  document.getElementById('quantidade-descarte').value = 1;
  document.querySelectorAll('.motivo-btn').forEach(b => b.classList.remove('selected'));
  motivoSelecionado = '';
  document.getElementById('campo-outro').style.display = 'none';
  document.getElementById('motivo-outro').value = '';

  renderLote();
}

// ── LOTE: Remover ────────────────────────────────────────────────────
function removerDoLote(idx) {
  loteDescarte.splice(idx, 1);
  renderLote();
}

// ── LOTE: Renderizar ─────────────────────────────────────────────────
function renderLote() {
  const container = document.getElementById('lote-itens');
  const contador  = document.getElementById('lote-contador');
  const btnReg    = document.getElementById('btn-registrar-lote');
  const btnCount  = document.getElementById('lote-btn-count');

  contador.textContent = loteDescarte.length + ' ' + (loteDescarte.length === 1 ? 'item' : 'itens');
  btnCount.textContent = loteDescarte.length;
  btnCount.style.display = loteDescarte.length ? 'inline-flex' : 'none';
  btnReg.disabled = loteDescarte.length === 0;
  btnReg.style.opacity  = loteDescarte.length ? '1' : '0.5';
  btnReg.style.cursor   = loteDescarte.length ? 'pointer' : 'not-allowed';

  if (!loteDescarte.length) {
    container.innerHTML = '<div class="lote-vazia">Nenhum item adicionado ainda</div>';
    return;
  }

  container.innerHTML = loteDescarte.map((item, idx) => `
    <div class="lote-item">
      <div class="lote-item-info">
        <div class="lote-item-nome">${escHtml(item.nome)}</div>
        <div class="lote-item-meta">
          Nº ${escHtml(item.numero)} &nbsp;·&nbsp;
          <span class="badge-motivo" style="font-size:0.72rem;">${escHtml(item.motivo)}</span>
          ${item.custo_total > 0 ? ` &nbsp;·&nbsp; R$ ${item.custo_total.toLocaleString('pt-BR',{minimumFractionDigits:2})}` : ''}
        </div>
      </div>
      <span class="lote-item-qtd">× ${item.quantidade}</span>
      <button class="lote-item-remove" onclick="removerDoLote(${idx})" title="Remover">
        <span class="material-icons" style="font-size:1.1rem;">close</span>
      </button>
    </div>
  `).join('');
}

// ── LOTE: Registrar todos ─────────────────────────────────────────────
async function registrarLote() {
  if (!loteDescarte.length) { showToast('Adicione pelo menos um item ao lote.', true); return; }

  const responsavel = document.getElementById('responsavel-desc').value.trim();
  const matricula   = document.getElementById('matricula-desc').value.trim();
  const observacao  = document.getElementById('observacao-descarte').value.trim();

  const btn = document.getElementById('btn-registrar-lote');
  btn.disabled = true;
  btn.innerHTML = '<span class="material-icons" style="animation:spin 1s linear infinite">refresh</span> Registrando...';

  let erros = 0;
  for (const item of loteDescarte) {
    try {
      const res  = await fetch('registrar_descarte.php', {
        method: 'POST',
        body: new URLSearchParams({ item_id: item.item_id, quantidade: item.quantidade, motivo: item.motivo, observacao, responsavel, matricula })
      });
      const data = await res.json();
      if (data.status !== 'ok') erros++;
    } catch(e) { erros++; }
  }

  if (erros === 0) {
    loteDescarte = [];
    renderLote();
    document.getElementById('matricula-desc').value    = '';
    document.getElementById('responsavel-desc').value  = '';
    document.getElementById('status-matricula-desc').textContent = '';
    document.getElementById('observacao-descarte').value = '';
    btn.innerHTML = '<span class="material-icons">delete_forever</span> Registrar lote <span id="lote-btn-count" class="lote-badge" style="display:none;">0</span>';
    btn.disabled = true;
    await carregarTudo();
    showToast('Lote de descarte registrado com sucesso!');
  } else {
    showToast(`${erros} item(ns) não foram registrados.`, true);
    btn.disabled = false;
    btn.innerHTML = '<span class="material-icons">delete_forever</span> Registrar lote <span id="lote-btn-count" class="lote-badge">' + loteDescarte.length + '</span>';
  }
}

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
  return true;
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

const spinSt = document.createElement('style');
spinSt.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
document.head.appendChild(spinSt);

function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

</script>
<script>
(function(){
  var remaining = <?= $tempo_restante ?? 1800 ?>;
  var display = document.getElementById('timer-display');
  var timer_el = document.getElementById('session-timer');
  var warned = false;

  function update() {
    if (remaining <= 0) {
      window.location.href = 'logout.php?expired=1';
      return;
    }
    var m = Math.floor(remaining / 60);
    var s = remaining % 60;
    display.textContent = m + ':' + (s < 10 ? '0' : '') + s;

    // Aviso aos 5 minutos
    if (remaining <= 300 && !warned) {
      warned = true;
      timer_el.style.background = '#fff8e1';
      timer_el.style.borderColor = '#ffe082';
      timer_el.style.color = '#f57c00';
      timer_el.querySelector('.material-icons').style.color = '#f57c00';
      if (confirm('⚠️ Sua sessão expira em 5 minutos!\n\nClique OK para renovar a sessão.')) {
        fetch(window.location.href); // ping para renovar
        remaining = <?= SESSION_TIMEOUT ?? 1800 ?>;
        warned = false;
        timer_el.style.background = '';
        timer_el.style.borderColor = '';
        timer_el.style.color = '';
      }
    }

    // Vermelho nos últimos 60 segundos
    if (remaining <= 60) {
      timer_el.style.background = '#ffebee';
      timer_el.style.borderColor = '#ffcdd2';
      timer_el.querySelector('.material-icons').style.color = '#e53935';
      display.style.color = '#e53935';
    }

    remaining--;
    setTimeout(update, 1000);
  }

  if (display) update();
})();
</script>
</body>
</html>
