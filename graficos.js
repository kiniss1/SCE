// graficos.js — versão consolidada

const state = {
  itens: [],
  charts: {},
  topN: 5,
  filterStart: null,
  filterEnd: null,
  usuariosItensMap: {}
};

function escapeHtml(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

// ─── Loaders ────────────────────────────────────────────────────────────────

async function loadItems(){
  const res = await fetch('listar_itens.php', {credentials:'same-origin'});
  state.itens = await res.json();
  const map = {};
  state.itens.forEach(i => map[i.nome] = i.id);
  state.itemNameToId = map;
}

async function loadTopItems(){
  const start = document.getElementById('filter-start').value || '';
  const end   = document.getElementById('filter-end').value || '';
  const topN  = Number(document.getElementById('filter-topn').value || 5);
  const url   = new URL('api_movimentacoes_aggregate.php', location.href);
  url.searchParams.set('action','top_items');
  if (start) url.searchParams.set('start', start);
  if (end)   url.searchParams.set('end', end);
  url.searchParams.set('topN', topN);
  const res  = await fetch(url.toString(), {credentials:'same-origin'});
  const json = await res.json();
  return (json.status === 'ok') ? json.data : [];
}

// Agrupa por DIA — usa o filtro de período do select do card
async function loadMovsByDay(){
  // Busca todas as movimentações e agrega por dia client-side
  // (mais confiável — não depende de endpoint intermediário)
  const meses  = Number(document.getElementById('filter-periodo-es').value || 1);
  const endDt  = new Date();
  const startDt = new Date(endDt.getFullYear(), endDt.getMonth() - (meses - 1), 1);
  // Zera hora para pegar o dia completo
  startDt.setHours(0,0,0,0);
  endDt.setHours(23,59,59,999);

  const res  = await fetch('listar_movimentacoes.php', {credentials:'same-origin'});
  const movs = await res.json();

  const map = {};
  movs.forEach(m => {
    if (!m.data) return;
    const d = new Date(m.data.replace(' ','T'));
    if (d < startDt || d > endDt) return;
    const day = m.data.slice(0,10);
    if (!map[day]) map[day] = { period: day, entradas: 0, saidas: 0 };
    if (m.tipo === 'entrada') map[day].entradas += Number(m.quantidade||0);
    if (m.tipo === 'saida')   map[day].saidas   += Number(m.quantidade||0);
  });

  return Object.values(map).sort((a,b) => a.period.localeCompare(b.period));
}

async function loadCusto(){
  const start = document.getElementById('filter-start').value || '';
  const end   = document.getElementById('filter-end').value || '';
  const topN  = Number(document.getElementById('filter-topn').value || 5);
  const url   = new URL('api_movimentacoes_aggregate.php', location.href);
  url.searchParams.set('action','custo');
  if (start) url.searchParams.set('start', start);
  if (end)   url.searchParams.set('end', end);
  url.searchParams.set('topN', topN);
  const res  = await fetch(url.toString(), {credentials:'same-origin'});
  const json = await res.json();
  return (json.status === 'ok') ? json : { data: [], custo_geral: 0 };
}

async function loadData(){
  await loadItems();
}

// ─── Chart helper ────────────────────────────────────────────────────────────

function createOrUpdateChart(ctxId, cfg){
  const ctx = document.getElementById(ctxId);
  if (!ctx) return null;
  if (state.charts[ctxId]) { state.charts[ctxId].destroy(); delete state.charts[ctxId]; }
  const chart = new Chart(ctx, cfg);
  state.charts[ctxId] = chart;
  // handler genérico de clique (drill por item)
  ctx.onclick = function(evt){
    const points = chart.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);
    if (!points.length) return;
    const label = chart.data.labels[points[0].index];
    let itemId = null;
    let nomeChave = label.split(' (Nº')[0].trim();
    if (state.itemNameToId && state.itemNameToId[nomeChave]) itemId = state.itemNameToId[nomeChave];
    if (!itemId) {
      for (const it of state.itens) {
        if (label.includes(it.nome) || it.nome.includes(label)) { itemId = it.id; break; }
      }
    }
    openDrillModal(itemId || nomeChave, label);
  };
  return chart;
}

// ─── Renders ─────────────────────────────────────────────────────────────────

async function renderEstoque(){
  const topN   = Number(document.getElementById('filter-topn').value || 5);
  const sorted = state.itens.slice().sort((a,b)=>Number(b.quantidade||0)-Number(a.quantidade||0)).slice(0, topN);
  const labels = sorted.map(i => (i.nome||'SEM_NOME') + (i.numero_item ? ` (Nº ${i.numero_item})` : ''));
  const data   = sorted.map(i => Number(i.quantidade||0));
  createOrUpdateChart('chartEstoqueTotal', {
    type: 'bar',
    data: { labels, datasets:[{ label:'Quantidade', data, backgroundColor:'#1976d2' }] },
    options: { indexAxis:'y', responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{x:{beginAtZero:true}} }
  });
}

async function renderMaisMovimentados(){
  const arr    = await loadTopItems();
  const labels = arr.map(r => (r.nome||'SEM_NOME') + (r.numero_item ? ` (Nº ${r.numero_item})` : ''));
  const data   = arr.map(r => Number(r.total_mov||0));
  createOrUpdateChart('chartMaisMovimentados', {
    type: 'bar',
    data: { labels, datasets:[{ label:'Movimentações', data, backgroundColor:'#43a047' }] },
    options: { indexAxis:'y', responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{x:{beginAtZero:true}} }
  });
}

async function renderEntradasSaidas(){
  const days    = await loadMovsByDay();

  if (!days.length) {
    const ctx = document.getElementById('chartEntradasSaidas');
    if (ctx) {
      if (state.charts['chartEntradasSaidas']) { state.charts['chartEntradasSaidas'].destroy(); delete state.charts['chartEntradasSaidas']; }
      const c = ctx.getContext('2d');
      c.clearRect(0,0,ctx.width,ctx.height);
      c.font = '14px Roboto, Arial'; c.fillStyle = '#888';
      c.fillText('Sem movimentações no período selecionado.', 10, 40);
    }
    return;
  }

  const labels   = days.map(d => d.period);
  const entradas = days.map(d => Number(d.entradas||0));
  const saidas   = days.map(d => Number(d.saidas||0));

  // Com 1 único ponto a linha não aparece — usar bar nesse caso
  const tipoGraf = labels.length <= 1 ? 'bar' : 'line';

  createOrUpdateChart('chartEntradasSaidas', {
    type: tipoGraf,
    data: {
      labels,
      datasets: [
        { label:'Entradas', data:entradas, borderColor:'#1976d2', backgroundColor: tipoGraf==='bar' ? '#1976d2' : '#1976d230',
          tension:0.3, fill:tipoGraf==='line', pointRadius:5, pointHoverRadius:7, borderWidth:2, spanGaps:true },
        { label:'Saídas',   data:saidas,   borderColor:'#ff5252', backgroundColor: tipoGraf==='bar' ? '#ff5252' : '#ff525230',
          tension:0.3, fill:tipoGraf==='line', pointRadius:5, pointHoverRadius:7, borderWidth:2, spanGaps:true }
      ]
    },
    options: {
      responsive:true, maintainAspectRatio:false,
      plugins:{ legend:{ position:'bottom' } },
      scales:{ y:{ beginAtZero:true, ticks:{ precision:0 } } }
    }
  });
}

async function renderVencimento(){
  const movsRes = await fetch('listar_movimentacoes.php', {credentials:'same-origin'});
  const movs    = await movsRes.json();
  const hoje    = Date.now();
  const dias30  = 30*24*3600*1000;
  const map     = {};
  movs.forEach(m => {
    if (!m.validade) return;
    const t = new Date(m.validade).getTime();
    if (t >= hoje && t <= hoje + dias30) {
      const nome = m.nome || ('#'+(m.item_id||'?'));
      map[nome] = (map[nome]||0) + Number(m.quantidade||0);
    }
  });
  const arr    = Object.entries(map).sort((a,b)=>b[1]-a[1]).slice(0, Number(document.getElementById('filter-topn').value||5));
  const labels = arr.map(a => a[0]);
  const data   = arr.map(a => a[1]);
  createOrUpdateChart('chartVencimento', {
    type: 'bar',
    data: { labels, datasets:[{ label:'Próx. vencer', data, backgroundColor:'#ff9800' }] },
    options: { indexAxis:'y', responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{x:{beginAtZero:true}} }
  });
}

async function renderUsuarios(){
  const res    = await fetch('listar_movimentacoes.php', {credentials:'same-origin'});
  const movs   = await res.json();
  const userMap   = {};
  const userItens = {};

  const filterStart = document.getElementById('filter-start').value;
  const filterEnd   = document.getElementById('filter-end').value;

  movs.forEach(m => {
    if (m.tipo !== 'saida') return;
    if (filterStart && m.data && m.data < filterStart) return;
    if (filterEnd   && m.data && m.data.slice(0,10) > filterEnd) return;

    // USA recebido_por — quem RECEBEU o EPI
    const user = (m.recebido_por && m.recebido_por.trim()) ? m.recebido_por.trim() : 'Não informado';
    userMap[user] = (userMap[user]||0) + Number(m.quantidade||0);
    userItens[user] = userItens[user] || [];
    userItens[user].push({
      nome:       m.nome || ('#'+m.item_id),
      numero:     m.numero_item || '',
      quantidade: Number(m.quantidade||0),
      data:       m.data || ''
    });
  });

  const topN   = Number(document.getElementById('filter-topn').value||5);
  const arr    = Object.entries(userMap).sort((a,b)=>b[1]-a[1]).slice(0, topN);
  const labels = arr.map(a => a[0]);
  const data   = arr.map(a => a[1]);
  state.usuariosItensMap = userItens;

  createOrUpdateChart('chartUsuariosMaisSolicitaram', {
    type: 'bar',
    data: { labels, datasets:[{ label:'Itens recebidos', data, backgroundColor:'#00bcd4' }] },
    options: {
      indexAxis:'y', responsive:true, maintainAspectRatio:false,
      plugins:{ legend:{display:false}, tooltip:{ callbacks:{ label: ctx => `${ctx.raw} itens recebidos` } } },
      scales:{ x:{beginAtZero:true} }
    }
  });

  // Sobrescreve onclick genérico para abrir modal do colaborador
  const canvasU = document.getElementById('chartUsuariosMaisSolicitaram');
  canvasU.onclick = function(evt){
    const chart  = state.charts['chartUsuariosMaisSolicitaram'];
    if (!chart) return;
    const points = chart.getElementsAtEventForMode(evt, 'nearest', {intersect:true}, true);
    if (points.length) openUserDrillModal(labels[points[0].index]);
  };
}

function openUserDrillModal(nomeUsuario){
  const modal = document.getElementById('modal-details');
  modal.style.display = 'flex';
  document.getElementById('modal-title').textContent = `Itens recebidos por: ${nomeUsuario}`;
  document.getElementById('modal-export-pdf').onclick = () => exportDrillPdf(`Itens - ${nomeUsuario}`);
  document.getElementById('modal-pagination').innerHTML = '';

  const itens = state.usuariosItensMap[nomeUsuario] || [];
  if (!itens.length) {
    document.getElementById('modal-content').innerHTML = '<p>Nenhum item encontrado.</p>';
    return;
  }

  const agrupado = {};
  itens.forEach(i => {
    const key = i.nome + (i.numero ? ` (Nº ${i.numero})` : '');
    if (!agrupado[key]) agrupado[key] = { total:0, datas:[] };
    agrupado[key].total += i.quantidade;
    if (i.data) agrupado[key].datas.push(i.data.slice(0,10));
  });

  const table = document.createElement('table');
  table.className = 'mini-table';
  table.innerHTML = `<thead><tr><th>EPI</th><th style="text-align:center">Qtd</th><th>Última Retirada</th></tr></thead>`;
  const tbody = document.createElement('tbody');
  Object.entries(agrupado).sort((a,b)=>b[1].total-a[1].total).forEach(([key, val]) => {
    const tr = document.createElement('tr');
    const ultimaData = val.datas.sort().reverse()[0] || '-';
    tr.innerHTML = `<td>${escapeHtml(key)}</td><td style="text-align:center;font-weight:700">${val.total}</td><td>${escapeHtml(ultimaData)}</td>`;
    tbody.appendChild(tr);
  });
  table.appendChild(tbody);
  document.getElementById('modal-content').innerHTML = '';
  document.getElementById('modal-content').appendChild(table);
}

async function renderCusto(){
  const result     = await loadCusto();
  const rows       = result.data || [];
  const custoGeral = result.custo_geral || 0;

  const kpiEl = document.getElementById('kpi-custo-value');
  if (kpiEl) kpiEl.textContent = 'R$ ' + custoGeral.toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2});

  const ctx = document.getElementById('chartCusto');
  if (!rows.length) {
    if (ctx) {
      if (state.charts['chartCusto']) { state.charts['chartCusto'].destroy(); delete state.charts['chartCusto']; }
      const c = ctx.getContext('2d');
      c.clearRect(0, 0, ctx.width, ctx.height);
      c.font = '13px Roboto, Arial';
      c.fillStyle = '#aaa';
      c.fillText('Nenhum item com custo cadastrado no período.', 10, 40);
    }
    return;
  }

  const labels = rows.map(r => (r.nome||'') + (r.numero_item ? ` (Nº ${r.numero_item})` : ''));
  const data   = rows.map(r => parseFloat(r.custo_total||0));
  createOrUpdateChart('chartCusto', {
    type: 'bar',
    data: { labels, datasets:[{ label:'Custo Total (R$)', data, backgroundColor:'#7b1fa2', borderRadius:4 }] },
    options: {
      indexAxis:'y', responsive:true, maintainAspectRatio:false,
      plugins:{ legend:{display:false}, tooltip:{ callbacks:{ label: ctx => 'R$ ' + ctx.raw.toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2}) } } },
      scales:{ x:{ beginAtZero:true, ticks:{ callback: v => 'R$ '+v.toLocaleString('pt-BR') } } }
    }
  });
}

// ─── KPIs ────────────────────────────────────────────────────────────────────

async function updateKPIs(){
  const itensRes = await fetch('listar_itens.php', {credentials:'same-origin'});
  const itens    = await itensRes.json();
  const total    = itens.reduce((s,i)=>s+Number(i.quantidade||0), 0);
  document.getElementById('kpi-total-value').textContent = total;
  document.getElementById('kpi-baixo-value').textContent = itens.filter(i=>Number(i.quantidade||0)<=2).length;

  const arr = await loadTopItems();
  document.getElementById('kpi-top-value').textContent = arr.length ? `${arr[0].nome} (${arr[0].total_mov})` : '-';

  const movsRes = await fetch('listar_movimentacoes.php', {credentials:'same-origin'});
  const movs    = await movsRes.json();
  const hoje    = Date.now();
  const dias30  = 30*24*3600*1000;
  const venc    = movs.filter(m => m.validade && new Date(m.validade).getTime() >= hoje && new Date(m.validade).getTime() <= hoje+dias30).length;
  document.getElementById('kpi-venc-value').textContent = venc;
}

// ─── Drill modal (itens) ─────────────────────────────────────────────────────

async function openDrillModal(itemOrName, label){
  const modal = document.getElementById('modal-details');
  modal.style.display = 'flex';
  document.getElementById('modal-title').textContent = `Movimentações: ${label}`;
  document.getElementById('modal-content').innerHTML = '<p>Carregando...</p>';
  document.getElementById('modal-export-pdf').onclick = () => exportDrillPdf(label);

  let url = new URL('listar_movimentacoes_item.php', location.href);
  if (Number(itemOrName)) url.searchParams.set('item_id', Number(itemOrName));
  else url.searchParams.set('nome', itemOrName);
  const start = document.getElementById('filter-start').value;
  const end   = document.getElementById('filter-end').value;
  if (start) url.searchParams.set('start', start);
  if (end)   url.searchParams.set('end', end);
  url.searchParams.set('page', 1);
  url.searchParams.set('per_page', 200);

  try {
    const res  = await fetch(url.toString(), {credentials:'same-origin'});
    const json = await res.json();
    if (json.status !== 'ok') { document.getElementById('modal-content').innerHTML = `<p>Erro: ${escapeHtml(json.error||'Resposta inválida')}</p>`; return; }
    const rows = json.data || [];
    if (!rows.length) { document.getElementById('modal-content').innerHTML = '<p>Nenhuma movimentação encontrada.</p>'; return; }

    const headers = ['Data','Tipo','Quantidade','Validade','Responsável','Recebido por','Observação'];
    const table   = document.createElement('table');
    table.className = 'mini-table';
    table.innerHTML = '<thead><tr>' + headers.map(h=>`<th>${escapeHtml(h)}</th>`).join('') + '</tr></thead>';
    const tbody = document.createElement('tbody');
    for (const r of rows) {
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${escapeHtml(r.data||'')}</td><td>${escapeHtml(r.tipo||'')}</td><td>${escapeHtml(String(r.quantidade||0))}</td><td>${escapeHtml(r.validade||'N/A')}</td><td>${escapeHtml(r.responsavel||'')}</td><td>${escapeHtml(r.recebido_por||'')}</td><td>${escapeHtml(r.observacao||'')}</td>`;
      tbody.appendChild(tr);
    }
    table.appendChild(tbody);
    document.getElementById('modal-content').innerHTML = '';
    document.getElementById('modal-content').appendChild(table);

    const pagDiv = document.getElementById('modal-pagination');
    pagDiv.innerHTML = '';
    if ((json.total||0) > (json.per_page||200)) {
      const info = document.createElement('div');
      info.textContent = `Mostrando página ${json.page} — total: ${json.total}`;
      pagDiv.appendChild(info);
    }
  } catch(e) {
    document.getElementById('modal-content').innerHTML = `<p>Erro: ${escapeHtml(e.message)}</p>`;
  }
}

function closeDrillModal(){
  document.getElementById('modal-details').style.display = 'none';
}

// ─── Exports ─────────────────────────────────────────────────────────────────

async function exportDrillPdf(title){
  const table = document.getElementById('modal-content').querySelector('table');
  if (!table) return alert('Tabela não encontrada.');
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF('p','pt','a4');
  doc.setFontSize(14);
  doc.text(title||'Relatório', 40, 40);
  doc.autoTable({ html:table, startY:60, theme:'striped', headStyles:{fillColor:[11,75,128]} });
  doc.save((title||'relatorio')+'.pdf');
}

function exportChartPNG(chartId, filename){
  const chart = state.charts[chartId];
  if (!chart) return alert('Gráfico não carregado');
  const a = document.createElement('a');
  a.href = chart.toBase64Image();
  a.download = filename || (chartId+'.png');
  document.body.appendChild(a); a.click(); a.remove();
}

async function exportDashboardPDF(){
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF('p','pt','a4');
  const ids = Object.keys(state.charts);
  doc.setFontSize(16); doc.text('Painel de Indicadores', 40, 40);
  for (let i=0; i<ids.length; i++) {
    const chart = state.charts[ids[i]];
    if (!chart) continue;
    doc.addImage(chart.toBase64Image(), 'PNG', 40, 64, doc.internal.pageSize.getWidth()-80, 180);
    if (i < ids.length-1) doc.addPage();
  }
  doc.save('painel_estoque_epi.pdf');
}

// ─── refreshAll ──────────────────────────────────────────────────────────────

async function refreshAll(){
  await updateKPIs();
  await renderEstoque();
  await renderMaisMovimentados();
  await renderUsuarios();
  await renderCusto();
  await renderEntradasSaidas();
  await renderVencimento();
}

// ─── showDetail ──────────────────────────────────────────────────────────────

function showDetail(type){
  if (type === 'estoque') {
    const rows = state.itens.map(i=>[i.nome+(i.numero_item?` (Nº ${i.numero_item})`:''), i.quantidade]);
    document.getElementById('modal-title').textContent = 'Estoque atual';
    document.getElementById('modal-content').innerHTML = '<table class="mini-table"><thead><tr><th>EPI</th><th>Qtd</th></tr></thead><tbody>'+rows.map(r=>`<tr><td>${escapeHtml(r[0])}</td><td>${escapeHtml(String(r[1]))}</td></tr>`).join('')+'</tbody></table>';
    document.getElementById('modal-details').style.display = 'flex';
    return;
  }
  const idMap = { 'mais-movimentados':'chartMaisMovimentados', 'entradas-saidas':'chartEntradasSaidas', 'vencimento':'chartVencimento', 'usuarios':'chartUsuariosMaisSolicitaram' };
  const cid = idMap[type];
  if (!cid) return;
  const ch = state.charts[cid];
  if (ch && ch.data && ch.data.labels && ch.data.labels.length) openDrillModal(null, ch.data.labels[0]);
  else alert('Gráfico ainda não carregado.');
}

// ─── Init ─────────────────────────────────────────────────────────────────────

async function init(){
  document.getElementById('btn-refresh').addEventListener('click', async ()=>{
    await loadData();
    await refreshAll();
  });
  document.getElementById('btn-export-all').addEventListener('click', exportDashboardPDF);

  document.querySelectorAll('[data-export]').forEach(btn=>{
    btn.addEventListener('click', ()=> exportChartPNG(btn.getAttribute('data-export'), btn.getAttribute('data-export')+'.png'));
  });

  document.querySelectorAll('.chart-type').forEach(sel=>{
    sel.addEventListener('change', ()=>{
      const target = sel.getAttribute('data-target');
      const old = state.charts[target];
      if (!old) return;
      const data = old.data; const options = old.options;
      old.destroy();
      state.charts[target] = new Chart(document.getElementById(target), { type:sel.value, data, options });
    });
  });

  document.querySelectorAll('[data-detail]').forEach(b=>{
    b.addEventListener('click', ()=> showDetail(b.getAttribute('data-detail')));
  });

  // Filtro de período do card entradas/saídas
  document.getElementById('filter-periodo-es').addEventListener('change', renderEntradasSaidas);

  document.getElementById('modalCloseBtn').addEventListener('click', closeDrillModal);
  document.getElementById('modal-details').addEventListener('click', e=>{ if(e.target===document.getElementById('modal-details')) closeDrillModal(); });

  await loadData();

  // Período padrão: último mês
  const end   = new Date();
  const start = new Date(end.getFullYear(), end.getMonth()-1, end.getDate());
  document.getElementById('filter-start').value = start.toISOString().slice(0,10);
  document.getElementById('filter-end').value   = end.toISOString().slice(0,10);

  await refreshAll();
}

document.addEventListener('DOMContentLoaded', init);
