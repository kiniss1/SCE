<?php
session_start();
if (empty($_SESSION['logado'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['u'] ?? '') === 'admin' && ($_POST['p'] ?? '') === 'admin') {
        $_SESSION['logado'] = true;
    } else {
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Login</title>
<style>*{box-sizing:border-box;margin:0;padding:0}body{font-family:Roboto,Arial,sans-serif;background:linear-gradient(135deg,#082f52,#1565c0);min-height:100vh;display:flex;align-items:center;justify-content:center}.box{background:#fff;border-radius:16px;padding:36px 32px;width:360px;box-shadow:0 8px 36px rgba(0,0,0,0.2)}.logo{text-align:center;margin-bottom:24px}.logo h1{font-size:1.3rem;font-weight:800;color:#0b4b80}.logo p{font-size:0.8rem;color:#607080;margin-top:4px}label{display:block;font-size:0.75rem;font-weight:700;color:#607080;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;margin-top:14px}input{width:100%;padding:11px 14px;border:1.5px solid #d6e8f7;border-radius:10px;font-size:1rem;outline:none}input:focus{border-color:#1565c0;box-shadow:0 0 0 3px rgba(21,101,192,0.1)}button{margin-top:20px;width:100%;padding:13px;background:linear-gradient(135deg,#082f52,#1565c0);color:#fff;border:none;border-radius:10px;font-size:1rem;font-weight:700;cursor:pointer}.erro{background:#ffebee;border:1px solid #ffcdd2;border-radius:8px;padding:10px;font-size:0.87rem;color:#e53935;font-weight:600;margin-bottom:14px;text-align:center}</style>
</head><body><div class="box"><div class="logo"><h1>GEM — Controle de EPI</h1><p>Gestão de Estoque e Materiais</p></div>';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') echo '<div class="erro">Usuário ou senha incorretos.</div>';
        echo '<form method="POST"><label>Usuário</label><input type="text" name="u" autofocus autocomplete="username"><label>Senha</label><input type="password" name="p" autocomplete="current-password"><button type="submit">Entrar</button></form></div></body></html>';
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8" />
    <title>Gráficos de Controle de EPI</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="graficos.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
</head>
<body>
<header><h1>Gráficos de Controle de EPI</h1></header>

<nav class="menu">
    <div class="nav-links">
        <a href="/" class="menu-btn"><span class="material-icons">home</span>Início</a>
        <a href="estoque.php" class="menu-btn"><span class="material-icons">inventory_2</span>Estoque Atual</a>
        <a href="historico.php" class="menu-btn"><span class="material-icons">history</span>Histórico</a>
        <a href="descarte.php" class="menu-btn"><span class="material-icons">delete_forever</span>Descarte</a>
        <a href="graficos.php" class="menu-btn active"><span class="material-icons">bar_chart</span>Gráficos</a>
    
      <a href="logout.php" class="menu-btn" style="color:#e53935!important;border-color:#ffcdd2;" title="Sair"><span class="material-icons">logout</span>Sair</a>
    </div>
</nav>

<main class="container" role="main">

    <div class="painel-header">
        <div class="painel-header-top">
            <span class="material-icons">bar_chart</span>
            <h2>Painel Visual de Indicadores</h2>
        </div>
        <section class="filters">
            <div class="filters-left">
                <div class="filter-group">
                    <span class="filter-label">Período</span>
                    <div class="period-inputs">
                        <input type="date" id="filter-start">
                        <span class="sep">→</span>
                        <input type="date" id="filter-end">
                    </div>
                </div>

            </div>
            <div class="filters-right">
                <button id="btn-refresh" class="btn"><span class="material-icons">refresh</span> Atualizar</button>
                <button id="btn-export-all" class="btn btn-outline"><span class="material-icons">download</span> Exportar tudo</button>
            </div>
        </section>
    </div>

    <section class="kpi-row">
        <div class="kpi-card" id="kpi-total">
            <div class="kpi-title">EPIs em estoque</div>
            <div class="kpi-value" id="kpi-total-value">—</div>
            <div class="kpi-sub">Total de unidades</div>
        </div>
        <div class="kpi-card" id="kpi-baixo">
            <div class="kpi-title">Estoque baixo</div>
            <div class="kpi-value" id="kpi-baixo-value">—</div>
            <div class="kpi-sub">Itens com qtd ≤ 2</div>
        </div>
        <div class="kpi-card" id="kpi-top">
            <div class="kpi-title">Top movimentado</div>
            <div class="kpi-value" id="kpi-top-value">—</div>
            <div class="kpi-sub">Mais saídas</div>
        </div>
        <div class="kpi-card" id="kpi-venc">
            <div class="kpi-title">Próx. vencimento</div>
            <div class="kpi-value" id="kpi-venc-value">—</div>
            <div class="kpi-sub">até 30 dias</div>
        </div>
        <div class="kpi-card" id="kpi-custo">
            <div class="kpi-title">Custo Total (Período)</div>
            <div class="kpi-value" id="kpi-custo-value">—</div>
            <div class="kpi-sub">EPIs liberados no período</div>
        </div>
        <div class="kpi-card" id="kpi-custo-estoque" style="border-top-color:#00897b;">
            <div class="kpi-title">Valor em Estoque</div>
            <div class="kpi-value" id="kpi-custo-estoque-value" style="color:#00897b;font-size:1.2rem;">—</div>
            <div class="kpi-sub">Custo dos EPIs em estoque agora</div>
        </div>
    </section>

    <section class="charts-grid">

        <article class="chart-card" id="card-estoque-total">
            <div class="card-header">
                <h3>Total de EPIs em Estoque</h3>
                <div class="card-actions">
                    <select class="chart-type" data-target="chartEstoqueTotal">
                        <option value="bar">Horizontal</option>
                        <option value="pie">Pizza</option>
                    </select>
                    <button class="btn btn-sm" data-export="chartEstoqueTotal">Exportar PNG</button>
                </div>
            </div>
            <div class="card-body"><canvas id="chartEstoqueTotal"></canvas></div>
            <div class="card-footer"><button class="btn btn-outline" data-detail="estoque">Ver detalhes</button></div>
        </article>

        <article class="chart-card" id="card-mais-movimentados">
            <div class="card-header">
                <h3>Top EPIs Mais Movimentados</h3>
                <div class="card-actions">
                    <select class="chart-type" data-target="chartMaisMovimentados">
                        <option value="bar">Bar</option>
                        <option value="line">Linha</option>
                    </select>
                    <button class="btn btn-sm" data-export="chartMaisMovimentados">Exportar PNG</button>
                </div>
            </div>
            <div class="card-body"><canvas id="chartMaisMovimentados"></canvas></div>
            <div class="card-footer"><button class="btn btn-outline" data-detail="mais-movimentados">Ver detalhes</button></div>
        </article>

        <article class="chart-card" id="card-usuarios">
            <div class="card-header">
                <h3>Usuários que mais receberam EPIs</h3>
                <div class="card-actions">
                    <select class="chart-type" data-target="chartUsuariosMaisSolicitaram">
                        <option value="bar">Bar</option>
                        <option value="pie">Pizza</option>
                    </select>
                    <button class="btn btn-sm" data-export="chartUsuariosMaisSolicitaram">Exportar PNG</button>
                </div>
            </div>
            <div class="card-body"><canvas id="chartUsuariosMaisSolicitaram"></canvas></div>
            <div class="card-footer"><button class="btn btn-outline" data-detail="usuarios">Ver detalhes</button></div>
        </article>

        <article class="chart-card" id="card-custo">
            <div class="card-header">
                <h3>Custo Total por EPI Liberado (R$)</h3>
                <div class="card-actions">
                    <button class="btn btn-sm" data-export="chartCusto">Exportar PNG</button>
                </div>
            </div>
            <div class="card-body"><canvas id="chartCusto"></canvas></div>
            <div class="card-footer">
                <small style="color:#888;font-size:0.8rem;">Baseado no custo cadastrado em Estoque Atual</small>
            </div>
        </article>

        <article class="chart-card" id="card-entradas-saidas">
            <div class="card-header">
                <h3>Entradas / Saídas por Dia</h3>
                <div class="card-actions">
                    <select id="filter-periodo-es" style="background:var(--accent);border:1px solid #c9e0f5;border-radius:6px;padding:4px 7px;font-size:0.82rem;color:var(--primary);font-weight:600;cursor:pointer;outline:none;">
                        <option value="1">Último mês</option>
                        <option value="2">Últimos 2 meses</option>
                        <option value="3">Últimos 3 meses</option>
                    </select>
                    <select class="chart-type" data-target="chartEntradasSaidas">
                        <option value="line">Linha</option>
                        <option value="bar">Bar</option>
                    </select>
                    <button class="btn btn-sm" data-export="chartEntradasSaidas">Exportar PNG</button>
                </div>
            </div>
            <div class="card-body"><canvas id="chartEntradasSaidas"></canvas></div>
            <div class="card-footer"><button class="btn btn-outline" data-detail="entradas-saidas">Ver detalhes</button></div>
        </article>

        <article class="chart-card" id="card-vencimento">
            <div class="card-header">
                <h3>EPIs Próximos do Vencimento (30 dias)</h3>
                <div class="card-actions">
                    <select class="chart-type" data-target="chartVencimento">
                        <option value="bar">Bar</option>
                        <option value="pie">Pizza</option>
                    </select>
                    <button class="btn btn-sm" data-export="chartVencimento">Exportar PNG</button>
                </div>
            </div>
            <div class="card-body"><canvas id="chartVencimento"></canvas></div>
            <div class="card-footer"><button class="btn btn-outline" data-detail="vencimento">Ver detalhes</button></div>
        </article>

    </section>
</main>

<div id="modal-details" class="modal-bg" style="display:none;">
    <div class="modal-details" role="dialog" aria-modal="true">
        <span class="modal-details-close" id="modalCloseBtn">&times;</span>
        <h3 id="modal-title">Detalhes</h3>
        <div id="modal-actions" style="margin-bottom:8px;">
            <button id="modal-export-pdf" class="btn btn-outline">Exportar tabela como PDF</button>
        </div>
        <div id="modal-content" style="max-height:60vh;overflow:auto;"></div>
        <div id="modal-pagination" style="margin-top:10px;display:flex;gap:8px;justify-content:center;"></div>
    </div>
</div>

<script src="graficos.js"></script>
</body>
</html>
