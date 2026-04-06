<?php
require 'conexao.php';
$cols = $pdo->query("SELECT id, nome, matricula, funcao, area FROM colaboradores ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
?>
<! DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Ficha do Colaborador - Recibo de Entrega de EPI</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

/* Configuração para impressão A4 */
@page { 
    size: A4 portrait; 
    margin: 10mm; 
}

@media print {
    html, body {
        width: 210mm;
        height: 297mm;
        margin: 0;
        padding: 0;
        background: #fff;
    }
    . ficha-container {
        width: 100%;
        height: auto;
        border: none;
        box-shadow: none;
        margin: 0;
        padding: 0;
    }
    .actions { display: none ! important; }
    .no-print { display: none !important; }
}

body { 
    font-family: Arial, sans-serif; 
    font-size: 9px; 
    background: #e0e0e0; 
    padding: 20px;
    display: flex;
    justify-content: center;
}

/* Container A4 - 210mm x 297mm */
.ficha-container {
    width: 210mm;
    min-height: 297mm;
    background: #fff;
    border: 1px solid #000;
    padding: 0;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

/* Tabela principal que contém todo o formulário */
.ficha-table {
    width: 100%;
    border-collapse: collapse;
}
.ficha-table td, .ficha-table th {
    border: 1px solid #000;
    padding: 4px 6px;
    vertical-align: top;
    font-size: 9px;
}

/* Cabeçalho: Logo + Título */
.header-logo {
    width: 65px;
    text-align: center;
    vertical-align: middle;
    padding: 6px;
}
.header-logo svg { width: 45px; height: 40px; }
.header-logo . logo-text { font-size: 11px; font-weight: bold; margin-top: 2px; }
.header-title {
    text-align: center;
    font-size: 16px;
    font-weight: bold;
    vertical-align: middle;
    letter-spacing: 1px;
}

/* Info colaborador */
.info-label { font-size: 8px; color: #333; font-weight: 600; }
.info-input {
    width: 100%;
    border: none;
    font-size: 10px;
    padding: 3px 0;
    background: transparent;
}
.info-input:focus { outline: none; background: #fffef0; }

/* Declaração */
.declaracao-titulo {
    text-align: center;
    font-weight: bold;
    font-size: 9px;
    border-bottom: 1px solid #000;
    padding: 4px;
    background: #fff;
}
.declaracao-texto {
    font-size: 8px;
    line-height: 1.35;
    padding: 6px 8px;
}
.declaracao-texto ul {
    margin: 4px 0 4px 16px;
    padding: 0;
}
.declaracao-texto li { margin-bottom: 1px; }

/* Local/Data e Assinatura */
.local-assinatura td {
    height: 50px;
    vertical-align: top;
    padding: 5px 8px;
}
.local-label { font-size: 8px; font-weight: bold; }
.local-campos { margin-top: 6px; font-size: 9px; }
.local-campos input {
    border: none;
    border-bottom: 1px solid #000;
    width: 45px;
    text-align: center;
    font-size: 9px;
    background: transparent;
}
.local-campos . cidade { width: 55px; text-align: left; }
.assinatura-linha {
    border-bottom: 1px solid #000;
    height: 28px;
    margin-top: 10px;
}

/* Tabela de EPIs */
.epi-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 8px;
}
.epi-table th, .epi-table td {
    border: 1px solid #000;
    padding: 3px 4px;
    text-align: center;
    vertical-align: middle;
}
.epi-table th {
    background: #fff;
    font-weight: bold;
    font-size: 7px;
}
.epi-table . col-desc { width: 30%; text-align: left; }
. epi-table .col-ca { width: 32px; }
.epi-table .col-data { width: 68px; }
.epi-table .col-motivo { width: 22px; }
.epi-table .col-rubrica { width: 85px; }

. epi-table td input[type="text"],
.epi-table td input[type="date"] {
    width: 100%;
    border: none;
    font-size: 8px;
    text-align: center;
    padding: 2px;
    background: transparent;
}
.epi-table td input[type="text"]:focus,
.epi-table td input[type="date"]:focus {
    background: #fffef0;
    outline: none;
}
.epi-table td. col-desc input { text-align: left; }
.epi-table td input[type="checkbox"] {
    width: 12px;
    height: 12px;
    margin: 0;
}

/* Legenda */
.legenda {
    font-size: 7px;
    padding: 5px 8px;
    border-top: 1px solid #000;
    display: flex;
    justify-content: space-between;
    background: #fff;
}
. legenda span { white-space: nowrap; }

/* Botões (não aparecem na impressão) */
.actions {
    padding: 15px;
    display: flex;
    gap: 12px;
    justify-content: center;
    background: #f5f5f5;
    border-top: 1px solid #ddd;
}
.btn {
    padding: 12px 24px;
    border-radius: 6px;
    border: none;
    font-weight: bold;
    cursor: pointer;
    font-size: 14px;
}
.btn-primary { background: #1976d2; color: #fff; }
.btn-primary:hover { background: #1565c0; }
.btn-secondary { background: #777; color: #fff; }
.btn-secondary:hover { background: #555; }
</style>
</head>
<body>

<div class="ficha-container">
    <form id="fichaForm" method="post" action="salvar_ficha_form.php">
        <input type="hidden" name="status" id="statusField" value="pending">

        <!-- ESTRUTURA PRINCIPAL EM TABELA -->
        <table class="ficha-table">
            <!-- CABEÇALHO: Logo + Título -->
            <tr>
                <td class="header-logo" rowspan="1">
                    <svg viewBox="0 0 50 45" xmlns="http://www.w3. org/2000/svg">
                        <circle cx="25" cy="17" r="14" fill="none" stroke="#000" stroke-width="2"/>
                        <polygon points="25,5 20,17 24,17 21,29 31,14 27,14 30,5" fill="#000"/>
                    </svg>
                    <div class="logo-text">Light</div>
                </td>
                <td class="header-title" colspan="4">RECIBO DE ENTREGA DE EPI</td>
            </tr>

            <!-- INFO COLABORADOR -->
            <tr>
                <td colspan="2" style="width:45%;">
                    <div class="info-label">Nome</div>
                    <input type="text" class="info-input" name="nome_colaborador" id="nome_colaborador" required>
                </td>
                <td style="width:12%;">
                    <div class="info-label">Matrícula</div>
                    <input type="text" class="info-input" name="matricula" id="matricula">
                </td>
                <td style="width:25%;">
                    <div class="info-label">Função</div>
                    <input type="text" class="info-input" name="funcao" id="funcao">
                </td>
                <td style="width:10%;">
                    <div class="info-label">Área</div>
                    <input type="text" class="info-input" name="area" id="area">
                </td>
            </tr>

            <!-- DECLARAÇÃO -->
            <tr>
                <td colspan="5" style="padding:0;">
                    <div class="declaracao-titulo">Declaração do Empregado</div>
                    <div class="declaracao-texto">
                        Declaro ter recebido, sem ônus, os Equipamentos de Proteção Individual – EPI's, abaixo especificado, e o treinamento relativo à sua utilização adequada, além de:
                        <ul>
                            <li>Estar ciente da obrigatoriedade do seu uso, constituindo <strong>ATO FALTOSO</strong> a recusa injustificada do uso do EPI, conforme Lei vigente n. º 6514 de 22/12/77 – Cap. V – Art. 158;</li>
                            <li>Usá-lo apenas para a finalidade a que se destina;</li>
                            <li>Responsabilizar-me pela guarda e conservação;</li>
                            <li>Solicitar a sua substituição sempre que o mesmo se torne impróprio para o uso;</li>
                            <li>Não alterar as características originais do equipamento;</li>
                            <li>Devolvê-los no caso de rescisão do Contrato de Trabalho;</li>
                            <li>Restituir à Empresa o prejuízo decorrente do extravio ou danos causados no EPI por uso ou acondicionamento indevido. </li>
                        </ul>
                    </div>
                </td>
            </tr>

            <!-- LOCAL/DATA E ASSINATURA -->
            <tr class="local-assinatura">
                <td colspan="2" style="width:40%;">
                    <div class="local-label">Local e Data</div>
                    <div class="local-campos">
                        <input type="text" name="local" class="cidade" placeholder="Rio... ">
                        <span>, </span>
                        <input type="text" name="dia" maxlength="2" style="width:22px;">
                        <span> / </span>
                        <input type="text" name="mes" maxlength="2" style="width:22px;">
                        <span> / </span>
                        <input type="text" name="ano" maxlength="4" style="width:38px;">
                    </div>
                </td>
                <td colspan="3" style="width:60%;">
                    <div class="local-label">Assinatura do Empregado</div>
                    <div class="assinatura-linha"></div>
                </td>
            </tr>
        </table>

        <input type="hidden" name="local_data" id="local_data">

        <!-- TABELA DE EPIs -->
        <table class="epi-table">
            <thead>
                <tr>
                    <th rowspan="2" class="col-desc">DESCRIÇÃO DO EPI CONFORME CATÁLOGO</th>
                    <th rowspan="2" class="col-ca">Nº<br>C.A. </th>
                    <th colspan="2">DATA</th>
                    <th colspan="6">MOTIVO</th>
                    <th rowspan="2" class="col-rubrica">RUBRICA DO<br>EMPREGADO</th>
                </tr>
                <tr>
                    <th class="col-data">ENTREGA</th>
                    <th class="col-data">DEVOLUÇÃO</th>
                    <th class="col-motivo">P</th>
                    <th class="col-motivo">U</th>
                    <th class="col-motivo">E</th>
                    <th class="col-motivo">F</th>
                    <th class="col-motivo">T</th>
                    <th class="col-motivo">D</th>
                </tr>
            </thead>
            <tbody>
                <?php for ($i = 0; $i < 14; $i++) { ?>
                <tr>
                    <td class="col-desc"><input type="text" name="linhas[<?php echo $i; ?>][descricao]"></td>
                    <td class="col-ca"><input type="text" name="linhas[<? php echo $i; ?>][numero_ca]"></td>
                    <td class="col-data"><input type="date" name="linhas[<?php echo $i; ?>][data_entrega]"></td>
                    <td class="col-data"><input type="date" name="linhas[<?php echo $i; ?>][data_devolucao]"></td>
                    <td class="col-motivo"><input type="checkbox" name="linhas[<?php echo $i; ?>][motivo_p]" value="1"></td>
                    <td class="col-motivo"><input type="checkbox" name="linhas[<?php echo $i; ?>][motivo_u]" value="1"></td>
                    <td class="col-motivo"><input type="checkbox" name="linhas[<?php echo $i; ?>][motivo_e]" value="1"></td>
                    <td class="col-motivo"><input type="checkbox" name="linhas[<? php echo $i; ?>][motivo_f]" value="1"></td>
                    <td class="col-motivo"><input type="checkbox" name="linhas[<?php echo $i; ?>][motivo_t]" value="1"></td>
                    <td class="col-motivo"><input type="checkbox" name="linhas[<?php echo $i; ?>][motivo_d]" value="1"></td>
                    <td class="col-rubrica"><input type="text" name="linhas[<?php echo $i; ?>][rubrica]"></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>

        <!-- LEGENDA -->
        <div class="legenda">
            <span><strong>P</strong> - Primeiro Fornecimento</span>
            <span><strong>U</strong> - Desgaste pelo Uso</span>
            <span><strong>E</strong> - Extraviado</span>
            <span><strong>F</strong> - Danificado</span>
            <span><strong>T</strong> - Teste Dielétrico</span>
            <span><strong>D</strong> - Defeito Fabricação</span>
        </div>

        <!-- BOTÕES -->
        <div class="actions no-print">
            <button type="button" class="btn btn-secondary" onclick="salvar('pending')">Salvar Rascunho</button>
            <button type="button" class="btn btn-primary" onclick="confirmar()">Confirmar e Registrar Saídas</button>
        </div>
    </form>
</div>

<script>
function coletarDados() {
    var form = document.getElementById('fichaForm');
    var fd = new FormData(form);
    var obj = {};
    fd.forEach(function(v, k) {
        if (k.indexOf('linhas') === 0) {
            var m = k.match(/linhas\[(\d+)\]\[(.+)\]/);
            if (m) {
                var idx = m[1];
                var field = m[2];
                obj. linhas = obj.linhas || {};
                obj.linhas[idx] = obj.linhas[idx] || {};
                obj.linhas[idx][field] = v;
                return;
            }
        }
        obj[k] = v;
    });
    if (obj.linhas) {
        obj.linhas = Object.keys(obj.linhas).sort(function(a, b) { return a - b; }). map(function(i) { return obj.linhas[i]; });
    } else {
        obj.linhas = [];
    }
    var dia = document.querySelector('input[name="dia"]'). value || '';
    var mes = document. querySelector('input[name="mes"]').value || '';
    var ano = document.querySelector('input[name="ano"]').value || '';
    if (ano && mes && dia) {
        obj.local_data = ano + '-' + mes. padStart(2, '0') + '-' + dia.padStart(2, '0');
    }
    return obj;
}

function salvar(status) {
    document.getElementById('statusField').value = status;
    var payload = coletarDados();
    payload.status = status;
    fetch('salvar_ficha_form.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(function(r) { return r.json(); })
    .then(function(resp) {
        if (resp. status === 'ok') {
            alert('Ficha salva (ID: ' + resp.ficha_id + ')');
        } else {
            alert('Erro: ' + (resp.error || JSON.stringify(resp)));
        }
    })
    .catch(function(e) { alert('Erro na requisição: ' + e); });
}

function confirmar() {
    if (! confirm('Ao confirmar, as linhas com data de entrega serão registradas como saídas no estoque. Deseja prosseguir?')) return;
    salvar('confirmed');
}
</script>
</body>
</html>