let equipamentos = [];
let html5QrCode = null;

function carregarEquipamentosBackend() {
    return fetch('listar_itens.php')
        .then(r => r.json())
        .then(lista => {
            equipamentos = lista;
            return lista;
        });
}

function adicionarEquipamento() {
    const nome = document.getElementById('nome-equipamento').value.trim();
    const numero = document.getElementById('numero-item').value.trim();
    const quantidade = parseInt(document.getElementById('quantidade-inicial').value) || 0;
    if(!nome) { alert('Informe o nome do EPI.'); return; }
    if(!numero) { alert('Informe o Nº do EPI.'); return; }
    if(equipamentos.find(e => e.nome.toLowerCase() === nome.toLowerCase() && e.numero_item === numero)) {
        alert('EPI já cadastrado com esse nome e Nº do EPI!');
        return;
    }
    fetch('adicionar_item.php', {
        method: "POST",
        body: new URLSearchParams({nome, numero, quantidade})
    })
    .then(r => r.json())
    .then(data => {
        if(data.status === 'ok') {
            document.getElementById('nome-equipamento').value = '';
            document.getElementById('numero-item').value = '';
            document.getElementById('quantidade-inicial').value = 0;
            atualizarTudo();
            atualizarDashboard();
        } else {
            alert("Erro ao adicionar: " + (data.mensagem || JSON.stringify(data)));
        }
    })
    .catch(err => alert("Erro ao adicionar: " + err));
}

function atualizarSelectEquipamento() {
    const select = document.getElementById('select-equipamento');
    select.innerHTML = '';
    equipamentos.forEach((eq) => {
        select.innerHTML += `<option value="${eq.id}" data-numero="${eq.numero_item}" data-nome="${eq.nome}">${eq.nome} (Nº ${eq.numero_item})</option>`;
    });
}

function movimentarEstoque() {
    if(equipamentos.length === 0) { alert('Cadastre um EPI antes.'); return; }
    const item_id = document.getElementById('select-equipamento').value;
    const tipo = document.getElementById('tipo-movimento').value;
    const quantidade = parseInt(document.getElementById('quantidade-movimento').value);
    const usuario = document.getElementById('usuario').value.trim();
    const validade = document.getElementById('validade-equipamento').value;
    const recebido_por = document.getElementById('recebido-por').value.trim();
    const observacao = document.getElementById('observacao-movimentacao').value.trim();
    if(!usuario) { alert('Informe o responsável.'); return; }
    if(isNaN(quantidade) || quantidade <= 0) { alert('Quantidade inválida.'); return; }
    const eq = equipamentos.find(e => e.id == item_id);
    if(!eq) { alert('EPI não encontrado!'); return; }
    if(tipo === 'saida' && eq.quantidade < quantidade) {
        alert('Estoque insuficiente!');
        return;
    }
    fetch('movimentar.php', {
        method: "POST",
        body: new URLSearchParams({
            item_id, tipo, quantidade,
            validade, responsavel: usuario,
            recebido_por, observacao
        })
    })
    .then(r => r.json())
    .then(data => {
        if(data.status === 'ok') {
            document.getElementById('quantidade-movimento').value = 1;
            document.getElementById('usuario').value = '';
            document.getElementById('validade-equipamento').value = '';
            document.getElementById('recebido-por').value = '';
            document.getElementById('observacao-movimentacao').value = '';
            atualizarTudo();
            atualizarDashboard();
        } else {
            alert("Erro: " + (data.mensagem || JSON.stringify(data)));
        }
    })
    .catch(err => alert("Erro ao movimentar: " + err));
}

function atualizarTudo() {
    carregarEquipamentosBackend().then(() => {
        atualizarSelectEquipamento();
        atualizarDashboard();
    });
}

function atualizarDashboard() {
    let total = equipamentos.reduce((sum, eq) => sum + Number(eq.quantidade || 0), 0);
    document.getElementById('card-total-estoque').innerText = total;

    fetch('listar_movimentacoes.php')
    .then(r => r.json())
    .then(movs => {
        let movMap = {};
        movs.forEach(m => {
            let nome = m.nome || '';
            if (!movMap[nome]) movMap[nome] = 0;
            movMap[nome] += Number(m.quantidade || 0);
        });
        let mais = Object.entries(movMap)
            .sort((a, b) => b[1] - a[1])
            .slice(0, 3);
        let html = mais.length
            ? mais.map(([nome, qtd], i) =>
                  `<span style="display:block;font-size:1.05em;font-weight:600;">${i+1}. ${nome}<span style="color:#1976d2;font-weight:700;font-size:0.93em;"> (${qtd})</span></span>`
              ).join('')
            : '-';
        document.getElementById('card-mais-movimentados').innerHTML = html;
    });

    const hoje = new Date();
    const dias30 = 30 * 24 * 60 * 60 * 1000;
    fetch('listar_movimentacoes.php')
    .then(r => r.json())
    .then(movs => {
        let vencendo = movs.filter(m => {
            if (!m.validade) return false;
            let dataVal = new Date(m.validade);
            let eq = equipamentos.find(e => e.nome === m.nome);
            if(!eq || eq.quantidade <= 0) return false;
            return (dataVal - hoje) >= 0 && (dataVal - hoje) <= dias30;
        });
        document.getElementById('card-vencendo').innerText = vencendo.length;
    });

    let baixo = equipamentos.filter(e => Number(e.quantidade || 0) <= 2).length;
    document.getElementById('card-estoque-baixo').innerText = baixo;

    let card = document.getElementById('card-estoque-baixo-card');
    if(baixo > 0) {
        card.classList.add('pulsante');
    } else {
        card.classList.remove('pulsante');
    }
}

function mostrarModalEstoqueBaixo() {
    const criticos = equipamentos.filter(e => Number(e.quantidade || 0) <= 2);
    let html = '';
    if(criticos.length === 0) {
        html = '<p style="color:#43a047;font-weight:600;">Nenhum EPI com estoque crítico!</p>';
    } else {
        html = `<table>
          <tr><th>Nome</th><th>Nº</th><th style="text-align:right;">Qtd</th></tr>
          ${criticos.map(e=>`<tr><td>${e.nome}</td><td>${e.numero_item}</td><td style="text-align:right;color:#d32f2f;font-weight:700;">${e.quantidade}</td></tr>`).join('')}
        </table>`;
    }
    document.getElementById('modal-estoque-baixo-lista').innerHTML = html;
    document.getElementById('modal-estoque-baixo').style.display = 'flex';
}

function fecharModalEstoqueBaixo() {
    document.getElementById('modal-estoque-baixo').style.display = 'none';
}

document.addEventListener("DOMContentLoaded", ()=>{
    atualizarTudo();
    document.getElementById('card-estoque-baixo-card').onclick = mostrarModalEstoqueBaixo;
    document.getElementById('modal-estoque-baixo').onclick = function(e){
        if(e.target === this) fecharModalEstoqueBaixo();
    }
    document.getElementById('btn-ler-qr').onclick = abrirQRModal;
});

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
        errorMessage => {
            if(errorMessage) document.getElementById('qr-status').textContent = errorMessage;
        }
    ).catch(err => {
        document.getElementById('qr-status').textContent = "Erro ao acessar câmera: " + err;
    });
}

function fecharQRModal() {
    document.getElementById('qr-modal-bg').style.display = 'none';
    if (html5QrCode) html5QrCode.stop().then(()=> {
        html5QrCode.clear();
    });
}

function selecionarEPIporQR(qrCodeMessage) {
    let qr = qrCodeMessage.trim().toLowerCase();
    let select = document.getElementById('select-equipamento');
    let found = false;
    for (let opt of select.options) {
        let numero = (opt.getAttribute('data-numero') || '').trim().toLowerCase();
        let nome   = (opt.getAttribute('data-nome') || '').trim().toLowerCase();
        if(qr === numero || qr === nome || nome.includes(qr) || numero.includes(qr)) {
            select.value = opt.value;
            found = true;
            break;
        }
    }
    if (!found) {
        document.getElementById('qr-status').textContent = "EPI não encontrado pelo QR Code!";
    }
}
