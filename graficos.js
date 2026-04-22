// graficos.js (com drill-through + uso do endpoint agregado + export PDF)
// Requisitos: api_movimentacoes_aggregate.php, listar_movimentacoes_item.php,
// listar_itens.php, listar_movimentacoes.php já disponíveis no servidor.

// Estado global
const state = {
  itens: [],
  charts: {},
  topN: 5,
  filterStart: null,
  filterEnd: null
};

// Util
function escapeHtml(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

// Carrega itens (usado nos tooltips / mapping nome->id)
async function loadItems(){
  const res = await fetch('listar_itens.php', {credentials:'same-origin'});
  state.itens = await res.json();
  const map = {};
  state.itens.forEach(i => map[i.nome] = i.id);
  state.itemNameToId = map;
}

// Usa endpoint agregado para Top Items
async function loadTopItems(){
  const start = document.getElementById('filter-start').value || '';
  const end = document.getElementById('filter-end').value || '';
  const topN = Number(document.getElementById('filter-topn').value || 5);
  const url = new URL('api_movimentacoes_aggregate.php', location.href);
  url.searchParams.set('action','top_items');
  if (start) url.searchParams.set('start', start);
  if (end) url.searchParams.set('end', end);
  url.searchParams.set('topN', topN);
  const res = await fetch(url.toString(), {credentials:'same-origin'});
  const json = await res.json();
  return (json.status === 'ok') ? json.data : [];
}

// Usa endpoint agregado para months (entradas/saídas)
async function loadMovsByDay(){
  const start = document.getElementById('filter-start').value || '';
  const end = document.getElementById('filter-end').value || '';
  const url = new URL('api_movimentacoes_aggregate.php', location.href);
  url.searchParams.set('action','days');
  if (start) url.searchParams.set('start', start);
  if (end) url.searchParams.set('end', end);
  const res = await fetch(url.toString(), {credentials:'same-origin'});
  const json = await res.json();
  return (json.status === 'ok') ? json.data : [];
}

// Carrega tudo inicial (itens + movs agregados)
async function loadData(){
  await loadItems();
  // Top items and monthly aggregated used when rendering charts individually
}

// --- Chart rendering ---
// cria ou atualiza chart
function createOrUpdateChart(ctxId, cfg){
  const ctx = document.getElementById(ctxId);
  if (!ctx) return null;
  if (state.charts[ctxId]) {
    state.charts[ctxId].destroy();
    delete state.charts[ctxId];
  }
  const chart = new Chart(ctx, cfg);
  state.charts[ctxId] = chart;

  // Adicionar handler de clique para drill-through
  ctx.onclick = function(evt){
    const points = chart.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);
    if (points.length) {
      const pt = points[0];
      const label = chart.data.labels[pt.index];
      // tenta mapear label para item_id (procura por nome exato ou começo)
      let itemId = null;
      // labels podem ter "NOME (Nº 1234)" -> extrair o nome antes do " (Nº"
      let nomeChave = label.split(' (Nº')[0].trim();
      if (state.itemNameToId && state.itemNameToId[nomeChave]) itemId = state.itemNameToId[nomeChave];
      // fallback: procura item cujo nome está contido no label
      if (!itemId) {
        for (const it of state.itens) {
          if (label.includes(it.nome) || it.nome.includes(label)) { itemId = it.id; break; }
        }
      }
      // abrir modal com movimentações se tivermos itemId ou ao menos nome
      openDrillModal(itemId || nomeChave, label);
    }
  };

  return chart;
}

// Render Estoque (lista top N itens por quantidade)
async function renderEstoque(){
  // usar listar_itens.php (já em state.itens)
  const topN = Number(document.getElementById('filter-topn').value || 5);
  const sorted = state.itens.slice().sort((a,b)=>Number(b.quantidade||0)-Number(a.quantidade||0)).slice(0, topN);
  const labels = sorted.map(i => (i.nome || 'SEM_NOME') + (i.numero_item ? ` (Nº ${i.numero_item})` : ''));
  const data = sorted.map(i => Number(i.quantidade||0));
  const cfg = {
    type: 'bar',
    data: { labels, datasets: [{ label:'Quantidade', data, backgroundColor:'#1976d2' }] },
    options: {
      indexAxis: 'y',
      responsive:true,
      maintainAspectRatio:false,
      plugins:{legend:{display:false}},
      scales:{ x:{beginAtZero:true} }
    }
  };
  createOrUpdateChart('chartEstoqueTotal', cfg);
}

// Render Top Movimentados (usa API agregado top_items)
async function renderMaisMovimentados(){
  const arr = await loadTopItems();
  const labels = arr.map(r => (r.nome || 'SEM_NOME') + (r.numero_item ? ` (Nº ${r.numero_item})` : ''));
  const data = arr.map(r => Number(r.total_mov || 0));
  const cfg = {
    type: 'bar',
    data: { labels, datasets: [{ label:'Movimentações', data, backgroundColor:'#43a047' }] },
    options: { indexAxis:'y', responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}} , scales:{ x:{beginAtZero:true} } }
  };
  createOrUpdateChart('chartMaisMovimentados', cfg);
}

// Render Entradas/Saídas (agregado por months - usa API meses)
async function renderEntradasSaidas(){
  const months = await loadMovsByDay(); // array of {period:'YYYY-MM', entradas, saidas}
  const labels = months.map(m => m.period);
  const entradas = months.map(m => Number(m.entradas || 0));
  const saidas = months.map(m => Number(m.saidas || 0));
  const cfg = {
    type: 'line',
    data: {
      labels,
      datasets: [
        { label:'Entradas', data:entradas, borderColor:'#1976d2', backgroundColor:'#1976d230', tension:0.3, fill:true },
        { label:'Saídas', data:saidas, borderColor:'#ff5252', backgroundColor:'#ff525230', tension:0.3, fill:true }
      ]
    },
    options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom'}}, scales:{ y:{beginAtZero:true} } }
  };
  createOrUpdateChart('chartEntradasSaidas', cfg);
}

// Render Vencimento (faz fetch parcial: lista de itens + lista movs para cálculo client-side)
async function renderVencimento(){
  // Reutiliza movs completos (pouco custo) — se base grande, adaptar para endpoint agregado por validade
  const movsRes = await fetch('listar_movimentacoes.php', {credentials:'same-origin'});
  const movs = await movsRes.json();
  const hoje = Date.now();
  const dias30 = 30*24*3600*1000;
  const map = {};
  movs.forEach(m => {
    if (!m.validade) return;
    const t = new Date(m.validade).getTime();
    if (t >= hoje && t <= hoje + dias30) {
      const nome = m.nome || ('#' + (m.item_id || '?'));
      map[nome] = (map[nome] || 0) + Number(m.quantidade || 0);
    }
  });
  const arr = Object.entries(map).sort((a,b)=>b[1]-a[1]).slice(0, Number(document.getElementById('filter-topn').value || 5));
  const labels = arr.map(a => a[0]);
  const data = arr.map(a => a[1]);
  const cfg = {
    type: 'bar',
    data: { labels, datasets: [{ label:'Próx. vencer', data, backgroundColor:'#ff9800' }] },
    options: { indexAxis:'y', responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}} , scales:{ x:{beginAtZero:true} } }
  };
  createOrUpdateChart('chartVencimento', cfg);
}

// Render Usuarios (usa listar_movimentacoes.php e processa)
async function renderUsuarios(){
  const res = await fetch('listar_movimentacoes.php', {credentials:'same-origin'});
  const movs = await res.json();
  const userMap = {}, userItens = {};

  // Filtro de período
  const filterStart = document.getElementById('filter-start').value;
  const filterEnd   = document.getElementById('filter-end').value;

  movs.forEach(m => {
    if (m.tipo !== 'saida') return;

    // Aplicar filtro de data
    if (filterStart && m.data && m.data < filterStart) return;
    if (filterEnd   && m.data && m.data.slice(0,10) > filterEnd) return;

    const user = (m.recebido_por && m.recebido_por.trim()) ? m.recebido_por.trim() : 'Não informado';
    userMap[user] = (userMap[user]||0) + Number(m.quantidade || 0);
    userItens[user] = userItens[user] || [];
    userItens[user].push({
      nome:       m.nome || ('#'+m.item_id),
      numero:     m.numero_item || '',
      quantidade: Number(m.quantidade || 0),
      data:       m.data || '',
      responsavel: m.responsavel || ''
    });
  });

  const arr = Object.entries(userMap).sort((a,b)=>b[1]-a[1]).slice(0, Number(document.getElementById('filter-topn').value || 5));
  const labels = arr.map(a=>a[0]), data = arr.map(a=>a[1]);
  state.usuariosItensMap = userItens;

  const cfg = {
    type: 'bar',
    data: { labels, datasets:[{ label:'Itens recebidos', data, backgroundColor:'#00bcd4' }] },
    options: {
      indexAxis:'y', responsive:true, maintainAspectRatio:false,
      plugins:{
        legend:{display:false},
        tooltip:{ callbacks:{ label: ctx => `${ctx.raw} itens recebidos` } }
      },
      scales:{ x:{beginAtZero:true} },
      onClick: (evt, elements) => {
        if (!elements.length) return;
        const idx = elements[0].index;
        const nomeUsuario = labels[idx];
        openUserDrillModal(nomeUsuario);
      }
    }
  };
  createOrUpdateChart('chartUsuariosMaisSolicitaram', cfg);

  // Sobrescreve onclick genérico para abrir modal do colaborador
  const canvasU = document.getElementById('chartUsuariosMaisSolicitaram');
  canvasU.onclick = function(evt){
    const chart = state.charts['chartUsuariosMaisSolicitaram'];
    if (!chart) return;
    const points = chart.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);
    if (points.length) openUserDrillModal(labels[points[0].index]);
  };
}

// Modal de drill do colaborador — mostra todos os itens que ele recebeu
function openUserDrillModal(nomeUsuario){
  const modal = document.getElementById('modal-details');
  modal.style.display = 'flex';
  document.getElementById('modal-title').textContent = `Itens recebidos por: ${nomeUsuario}`;
  document.getElementById('modal-export-pdf').onclick = () => exportDrillPdf(`Itens - ${nomeUsuario}`);

  const itens = state.usuariosItensMap[nomeUsuario] || [];
  if (!itens.length) {
    document.getElementById('modal-content').innerHTML = '<p>Nenhum item encontrado para este colaborador.</p>';
    return;
  }

  // Agrupa por EPI e soma quantidades
  const agrupado = {};
  itens.forEach(i => {
    const key = i.nome + (i.numero ? ` (Nº ${i.numero})` : '');
    if (!agrupado[key]) agrupado[key] = { total: 0, datas: [] };
    agrupado[key].total += i.quantidade;
    if (i.data) agrupado[key].datas.push(i.data.slice(0,10));
  });

  const table = document.createElement('table');
  table.className = 'mini-table';
  table.innerHTML = `<thead><tr>
    <th>EPI</th>
    <th style="text-align:center">Qtd Total</th>
    <th>Última Retirada</th>
  </tr></thead>`;
  const tbody = document.createElement('tbody');

  Object.entries(agrupado)
    .sort((a,b) => b[1].total - a[1].total)
    .forEach(([key, val]) => {
      const tr = document.createElement('tr');
      const ultimaData = val.datas.sort().reverse()[0] || '-';
      tr.innerHTML = `<td>${escapeHtml(key)}</td><td style="text-align:center;font-weight:700">${val.total}</td><td>${escapeHtml(ultimaData)}</td>`;
      tbody.appendChild(tr);
    });

  table.appendChild(tbody);
  document.getElementById('modal-content').innerHTML = '';
  document.getElementById('modal-content').appendChild(table);
  document.getElementById('modal-pagination').innerHTML = '';
}

// Atualiza KPIs
async function updateKPIs(){
  // total itens
  const itensRes = await fetch('listar_itens.php', {credentials:'same-origin'});
  const itens = await itensRes.json();
  const total = itens.reduce((s,i)=>s+Number(i.quantidade||0),0);
  document.getElementById('kpi-total-value').textContent = total;
  const baixoCount = itens.filter(i => Number(i.quantidade||0) <= 2).length;
  document.getElementById('kpi-baixo-value').textContent = baixoCount;
  // top movimentado (usar agregado top_items)
  const arr = await loadTopItems();
  if (arr && arr.length) {
    document.getElementById('kpi-top-value').textContent = `${arr[0].nome} (${arr[0].total_mov})`;
  } else document.getElementById('kpi-top-value').textContent = '-';
  // vencimentos (calcular rápido)
  const movsRes = await fetch('listar_movimentacoes.php', {credentials:'same-origin'});
  const movs = await movsRes.json();
  const hoje = Date.now();
  const dias30 = 30*24*3600*1000;
  const venc = movs.filter(m => m.validade && (new Date(m.validade).getTime() >= hoje) && (new Date(m.validade).getTime() <= hoje+dias30)).length;
  document.getElementById('kpi-venc-value').textContent = venc;
}

// --- Drill modal: abrir e mostrar movimentos detalhados para um item ou nome ---
async function openDrillModal(itemOrName, label){
  // Se veio item id => buscar por item_id, senão usa nome
  const modal = document.getElementById('modal-details');
  modal.style.display = 'flex';
  document.getElementById('modal-title').textContent = `Movimentações: ${label}`;
  document.getElementById('modal-content').innerHTML = '<p>Carregando...</p>';
  document.getElementById('modal-export-pdf').onclick = () => exportDrillPdf(label);

  // params: procurar item_id ou nome
  let url = new URL('listar_movimentacoes_item.php', location.href);
  if (Number(itemOrName)) url.searchParams.set('item_id', Number(itemOrName));
  else url.searchParams.set('nome', itemOrName);
  // usa filtros globais
  const start = document.getElementById('filter-start').value;
  const end = document.getElementById('filter-end').value;
  if (start) url.searchParams.set('start', start);
  if (end) url.searchParams.set('end', end);
  url.searchParams.set('page', 1);
  url.searchParams.set('per_page', 200);

  try {
    const res = await fetch(url.toString(), {credentials:'same-origin'});
    const json = await res.json();
    if (json.status !== 'ok') {
      document.getElementById('modal-content').innerHTML = `<p>Erro: ${escapeHtml(json.error || 'Resposta inválida')}</p>`;
      return;
    }
    const rows = json.data || [];
    if (rows.length === 0) {
      document.getElementById('modal-content').innerHTML = '<p>Nenhuma movimentação encontrada para este item no período.</p>';
      return;
    }
    // montar tabela HTML
    const headers = ['Data','Tipo','Quantidade','Validade','Responsável','Recebido por','Observação'];
    const table = document.createElement('table');
    table.className = 'mini-table';
    const thead = document.createElement('thead');
    thead.innerHTML = '<tr>' + headers.map(h => `<th>${escapeHtml(h)}</th>`).join('') + '</tr>';
    table.appendChild(thead);
    const tbody = document.createElement('tbody');
    for (const r of rows) {
      const tr = document.createElement('tr');
      tr.innerHTML = '<td>' + escapeHtml(r.data || '') + '</td>'
        + '<td>' + escapeHtml(r.tipo || '') + '</td>'
        + '<td>' + escapeHtml(String(r.quantidade || 0)) + '</td>'
        + '<td>' + escapeHtml(r.validade || 'N/A') + '</td>'
        + '<td>' + escapeHtml(r.responsavel || '') + '</td>'
        + '<td>' + escapeHtml(r.recebido_por || '') + '</td>'
        + '<td>' + escapeHtml(r.observacao || '') + '</td>';
      tbody.appendChild(tr);
    }
    table.appendChild(tbody);
    document.getElementById('modal-content').innerHTML = '';
    document.getElementById('modal-content').appendChild(table);

    // paginação (simples, usa json.total)
    const pagDiv = document.getElementById('modal-pagination');
    pagDiv.innerHTML = '';
    const total = json.total || rows.length;
    if (total > json.per_page) {
      // criar botões prev/next (apenas demonstração, não implementamos carregamento de outras páginas agora)
      const info = document.createElement('div');
      info.textContent = `Mostrando página ${json.page} — total items: ${total}`;
      pagDiv.appendChild(info);
    }
  } catch (e) {
    document.getElementById('modal-content').innerHTML = `<p>Erro na requisição: ${escapeHtml(e.message)}</p>`;
  }
}

function closeDrillModal(){
  const modal = document.getElementById('modal-details');
  modal.style.display = 'none';
}
document.getElementById && document.addEventListener('click', (e) => {
  if (e.target && e.target.id === 'modalCloseBtn') closeDrillModal();
  // fechar quando clicar fora do conteúdo
  if (e.target && e.target.id === 'modal-details') closeDrillModal();
});

// Export da tabela do drill em PDF via jsPDF + autotable
async function exportDrillPdf(title){
  const content = document.getElementById('modal-content');
  if (!content) return alert('Nada para exportar');
  // extrair tabela HTML se existir
  const table = content.querySelector('table');
  if (!table) return alert('Tabela não encontrada para exportação.');
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF('p','pt','a4');
  doc.setFontSize(14);
  doc.text(title || 'Relatório', 40, 40);
  doc.autoTable({ html: table, startY: 60, theme: 'striped', headStyles: { fillColor: [11,75,128] } });
  doc.save((title || 'relatorio') + '.pdf');
}

// Exporta um chart como PNG
function exportChartPNG(chartId, filename){
  const chart = state.charts[chartId];
  if (!chart) return alert('Gráfico não carregado');
  const url = chart.toBase64Image();
  const a = document.createElement('a');
  a.href = url;
  a.download = filename || (chartId + '.png');
  document.body.appendChild(a);
  a.click();
  a.remove();
}

// Export dashboard PDF (cada gráfico em uma página)
async function exportDashboardPDF(){
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF('p','pt','a4');
  const chartIds = Object.keys(state.charts);
  let y = 40;
  doc.setFontSize(16);
  doc.text('Painel de Indicadores', 40, y);
  y += 24;
  for (let i = 0; i < chartIds.length; i++) {
    const id = chartIds[i];
    const chart = state.charts[id];
    if (!chart) continue;
    const img = chart.toBase64Image();
    // Calcula escala para caber na página
    const pageWidth = doc.internal.pageSize.getWidth() - 80;
    const imgProps = chart.canvas;
    // inserir imagem e depois quebra de página
    doc.addImage(img, 'PNG', 40, y, pageWidth, 180);
    if (i < chartIds.length - 1) doc.addPage();
    y = 40;
  }
  doc.save('painel_estoque_epi.pdf');
}

// Inicialização e eventos
async function init(){
  // controles
  document.getElementById('btn-refresh').addEventListener('click', async ()=>{
    state.topN = Number(document.getElementById('filter-topn').value || 5);
    state.filterStart = document.getElementById('filter-start').value || null;
    state.filterEnd = document.getElementById('filter-end').value || null;
    await loadData();
    await refreshAll();
  });
  document.getElementById('btn-export-all').addEventListener('click', () => exportDashboardPDF());

  // export buttons per card (delegation)
  document.querySelectorAll('[data-export]').forEach(btn=>{
    btn.addEventListener('click', ()=> {
      const id = btn.getAttribute('data-export');
      exportChartPNG(id, id + '.png');
    });
  });

  // change chart types (simple re-create)
  document.querySelectorAll('.chart-type').forEach(sel=>{
    sel.addEventListener('change', (e) => {
      const target = sel.getAttribute('data-target');
      const type = sel.value;
      const old = state.charts[target];
      if (!old) return;
      const data = old.data;
      const options = old.options;
      old.destroy();
      state.charts[target] = new Chart(document.getElementById(target), { type, data, options });
    });
  });

  // detalhe botões
  document.querySelectorAll('[data-detail]').forEach(b=>{
    b.addEventListener('click', ()=> showDetail(b.getAttribute('data-detail')));
  });

  // modal close
  document.getElementById('modalCloseBtn').addEventListener('click', closeDrillModal);
  document.getElementById('modal-details').addEventListener('click', (e)=> { if (e.target === document.getElementById('modal-details')) closeDrillModal(); });

  // carrega dados iniciais
  await loadData();
  // ajustar datas default (último mês)
  const end = new Date();
  const start = new Date(end.getFullYear(), end.getMonth() - 1, end.getDate());
  document.getElementById('filter-start').value = start.toISOString().slice(0,10);
  document.getElementById('filter-end').value = end.toISOString().slice(0,10);

  await refreshAll();
}

// showDetail: abre modal com a mesma função de drill (passa tipo)
function showDetail(type){
  // mapeia para ações:
  if (type === 'estoque') {
    // abre com item = none -> lista de itens com quantidade (local)
    const content = document.getElementById('modal-content');
    const rows = state.itens.map(i => [i.nome + (i.numero_item ? ` (Nº ${i.numero_item})` : ''), i.quantidade]);
    const html = '<table class="mini-table"><thead><tr><th>EPI</th><th>Qtd</th></tr></thead><tbody>' + rows.map(r=>`<tr><td>${escapeHtml(r[0])}</td><td>${escapeHtml(String(r[1]))}</td></tr>`).join('') + '</tbody></table>';
    document.getElementById('modal-title').textContent = 'Estoque atual (itens)';
    document.getElementById('modal-content').innerHTML = html;
    document.getElementById('modal-details').style.display = 'flex';
    return;
  }
  // para outros tipos usamos drill com top item label
  // aqui reusa openDrillModal com o primeiro label, se existir
  if (type === 'mais-movimentados') {
    // abrir primeiro item do chartMaisMovimentados
    const ch = state.charts['chartMaisMovimentados'];
    if (ch && ch.data && ch.data.labels && ch.data.labels.length) {
      openDrillModal(null, ch.data.labels[0]);
    } else alert('Gráfico ainda não carregado.');
    return;
  }
  if (type === 'vencimento' || type === 'entradas-saidas' || type === 'usuarios') {
    // apenas abre modal com informação resumida (ou usa drill para primeiro label)
    const idMap = {
      'vencimento': 'chartVencimento',
      'entradas-saidas': 'chartEntradasSaidas',
      'usuarios': 'chartUsuariosMaisSolicitaram'
    };
    const cid = idMap[type];
    const ch2 = state.charts[cid];
    if (ch2 && ch2.data && ch2.data.labels && ch2.data.labels.length) {
      openDrillModal(null, ch2.data.labels[0]);
    } else alert('Gráfico ainda não carregado.');
    return;
  }
}

// refreshAll
async function refreshAll(){
  await updateKPIs();
  await renderEstoque();
  await renderMaisMovimentados();
  await renderEntradasSaidas();
  await renderVencimento();
  await renderUsuarios();
}

// start
document.addEventListener('DOMContentLoaded', init);
