/**
 * Kaixa SaaS - Logica do PDV, atalhos e AJAX
 */

const BASE_URL = document.getElementById('baseUrl').value;
const CSRF = document.getElementById('csrfToken').value;
const CAIXA_ID = document.getElementById('caixaId').value;

let itens = [];
let pagamentos = [];
let searchTimeout = null;
let searchIndex = -1;

// Clock
function updateClock() {
    const now = new Date();
    document.getElementById('clock').textContent =
        now.toLocaleDateString('pt-BR') + ' ' + now.toLocaleTimeString('pt-BR');
}
setInterval(updateClock, 1000);
updateClock();

// Beep sound
function beep() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.frequency.value = 1200;
        osc.type = 'sine';
        gain.gain.value = 0.1;
        osc.start();
        osc.stop(ctx.currentTime + 0.08);
    } catch(e) {}
}

// Busca de produto
const inputBusca = document.getElementById('inputBusca');

inputBusca.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const q = this.value.trim();
    if (q.length < 1) {
        fecharBusca();
        return;
    }
    searchTimeout = setTimeout(() => buscarProduto(q), 200);
});

inputBusca.addEventListener('keydown', function(e) {
    const results = document.querySelectorAll('.pdv-search-result-item');
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        searchIndex = Math.min(searchIndex + 1, results.length - 1);
        highlightResult(results);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        searchIndex = Math.max(searchIndex - 1, 0);
        highlightResult(results);
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (searchIndex >= 0 && results[searchIndex]) {
            results[searchIndex].click();
        } else if (results.length === 1) {
            results[0].click();
        }
    }
});

function highlightResult(results) {
    results.forEach((r, i) => {
        r.classList.toggle('active', i === searchIndex);
    });
    if (results[searchIndex]) {
        results[searchIndex].scrollIntoView({ block: 'nearest' });
    }
}

function buscarProduto(q) {
    q = q || inputBusca.value.trim();
    if (!q) return;

    fetch(`${BASE_URL}/pdv/buscar_produto.php?q=${encodeURIComponent(q)}`)
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('resultadosBusca');
            if (data.length === 0) {
                container.innerHTML = '<div class="p-3 text-center text-muted">Nenhum produto encontrado</div>';
                container.classList.remove('d-none');
                return;
            }

            // Se busca exata por codigo de barras (1 resultado), adicionar direto
            if (data.length === 1 && data[0].codigo_barras === q) {
                adicionarItem(data[0]);
                inputBusca.value = '';
                fecharBusca();
                return;
            }

            container.innerHTML = data.map(p => `
                <div class="pdv-search-result-item" onclick='selecionarProduto(${JSON.stringify(p)})'>
                    <div>
                        <div class="produto-nome">${esc(p.descricao)}</div>
                        <div class="produto-codigo">
                            Cod: ${p.id} ${p.codigo_barras ? '| EAN: '+p.codigo_barras : ''}
                            ${p.categoria_nome ? ' | <span class="badge" style="background:'+p.categoria_cor+'">'+esc(p.categoria_nome)+'</span>' : ''}
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="produto-preco">${formatMoney(p.preco_venda)}</div>
                        <div class="produto-estoque">Estoque: ${parseFloat(p.estoque_atual).toFixed(2)}</div>
                    </div>
                </div>
            `).join('');
            container.classList.remove('d-none');
            searchIndex = -1;
        })
        .catch(() => {});
}

function selecionarProduto(produto) {
    adicionarItem(produto);
    inputBusca.value = '';
    fecharBusca();
    inputBusca.focus();
}

function fecharBusca() {
    const container = document.getElementById('resultadosBusca');
    container.classList.add('d-none');
    container.innerHTML = '';
    searchIndex = -1;
}

// Adicionar item a lista
function adicionarItem(produto) {
    // Verificar se ja existe
    const idx = itens.findIndex(i => i.produto_id === produto.id);
    if (idx >= 0) {
        itens[idx].quantidade += 1;
        itens[idx].subtotal = (itens[idx].quantidade * itens[idx].valor_unitario) - itens[idx].desconto;
    } else {
        itens.push({
            produto_id: produto.id,
            codigo_barras: produto.codigo_barras || '',
            descricao: produto.descricao,
            unidade: produto.unidade || 'UN',
            quantidade: 1,
            valor_unitario: parseFloat(produto.preco_venda),
            desconto: 0,
            subtotal: parseFloat(produto.preco_venda),
        });
    }

    beep();
    renderItens();
    calcularTotal();
}

// Renderizar lista de itens
function renderItens() {
    const tbody = document.getElementById('listaItens');

    if (itens.length === 0) {
        tbody.innerHTML = `<tr id="semItens">
            <td colspan="8" class="text-center text-muted py-5">
                <i class="fas fa-shopping-cart fa-3x mb-3 d-block opacity-25"></i>
                Nenhum item adicionado.
            </td></tr>`;
        return;
    }

    tbody.innerHTML = itens.map((item, i) => `
        <tr class="item-added">
            <td>${i + 1}</td>
            <td><small>${esc(item.codigo_barras || item.produto_id)}</small></td>
            <td>${esc(item.descricao)}</td>
            <td>
                <div class="qtd-control">
                    <button class="btn btn-outline-secondary btn-sm" onclick="alterarQtd(${i}, -1)">-</button>
                    <span class="qtd-value" onclick="editarQtd(${i})">${item.quantidade}</span>
                    <button class="btn btn-outline-secondary btn-sm" onclick="alterarQtd(${i}, 1)">+</button>
                </div>
            </td>
            <td>${formatMoney(item.valor_unitario)}</td>
            <td>
                <input type="number" class="form-control form-control-sm"
                       value="${item.desconto}" min="0" step="0.01" style="width:80px"
                       onchange="setDescItem(${i}, this.value)">
            </td>
            <td class="fw-bold">${formatMoney(item.subtotal)}</td>
            <td>
                <button class="btn-remove-item" onclick="removerItem(${i})" title="Remover (F5)">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        </tr>
    `).join('');

    // Scroll para ultimo item
    const container = document.querySelector('.pdv-items-container');
    container.scrollTop = container.scrollHeight;
}

// Alterar quantidade
function alterarQtd(index, delta) {
    const novaQtd = itens[index].quantidade + delta;
    if (novaQtd <= 0) {
        removerItem(index);
        return;
    }
    itens[index].quantidade = novaQtd;
    itens[index].subtotal = (novaQtd * itens[index].valor_unitario) - itens[index].desconto;
    renderItens();
    calcularTotal();
}

// Editar quantidade via modal
function editarQtd(index) {
    document.getElementById('qtdItemIndex').value = index;
    document.getElementById('inputQtdModal').value = itens[index].quantidade;
    const modal = new bootstrap.Modal(document.getElementById('modalQtd'));
    modal.show();
    setTimeout(() => document.getElementById('inputQtdModal').select(), 300);
}

function confirmarQtd() {
    const index = parseInt(document.getElementById('qtdItemIndex').value);
    const qtd = parseFloat(document.getElementById('inputQtdModal').value);
    if (qtd > 0) {
        itens[index].quantidade = qtd;
        itens[index].subtotal = (qtd * itens[index].valor_unitario) - itens[index].desconto;
        renderItens();
        calcularTotal();
    }
    bootstrap.Modal.getInstance(document.getElementById('modalQtd')).hide();
    inputBusca.focus();
}

// Desconto por item
function setDescItem(index, val) {
    itens[index].desconto = parseFloat(val) || 0;
    itens[index].subtotal = (itens[index].quantidade * itens[index].valor_unitario) - itens[index].desconto;
    calcularTotal();
}

// Remover item
function removerItem(index) {
    itens.splice(index, 1);
    renderItens();
    calcularTotal();
    inputBusca.focus();
}

// Calcular total
function calcularTotal() {
    let subtotal = itens.reduce((sum, i) => sum + i.subtotal, 0);
    const descontoTipo = document.getElementById('descontoTipo').value;
    const descontoVal = parseFloat(document.getElementById('descontoGeral').value) || 0;
    let desconto = descontoTipo === 'percentual' ? (subtotal * descontoVal / 100) : descontoVal;
    let total = Math.max(0, subtotal - desconto);

    document.getElementById('subtotalDisplay').textContent = formatMoney(subtotal);
    document.getElementById('descontoDisplay').textContent = formatMoney(desconto);
    document.getElementById('totalVenda').textContent = formatMoney(total);
    document.getElementById('qtdItens').textContent = itens.length;
}

// Abrir modal pagamento
function abrirPagamento() {
    if (itens.length === 0) {
        alert('Adicione itens antes de finalizar.');
        return;
    }
    pagamentos = [];
    renderPagamentos();

    const total = getTotal();
    document.getElementById('totalPagar').textContent = formatMoney(total);
    document.getElementById('valorPagamento').value = total.toFixed(2);

    const modal = new bootstrap.Modal(document.getElementById('modalPagamento'));
    modal.show();
}

// Pagamento rapido
function pagamentoRapido(forma) {
    pagamentos = [{ forma: forma, valor: getTotal() }];
    renderPagamentos();
    finalizarVenda();
}

// Adicionar pagamento
function adicionarPagamento() {
    const forma = document.getElementById('formaPagamento').value;
    const valor = parseFloat(document.getElementById('valorPagamento').value) || 0;
    if (valor <= 0) { alert('Informe o valor'); return; }

    pagamentos.push({ forma, valor });
    renderPagamentos();

    // Atualizar valor restante
    const restante = getRestante();
    document.getElementById('valorPagamento').value = restante > 0 ? restante.toFixed(2) : '0.00';
}

function removerPagamento(index) {
    pagamentos.splice(index, 1);
    renderPagamentos();
}

function renderPagamentos() {
    const container = document.getElementById('pagamentosLista');
    const total = getTotal();
    const pago = pagamentos.reduce((s, p) => s + p.valor, 0);
    const restante = Math.max(0, total - pago);
    const troco = Math.max(0, pago - total);

    container.innerHTML = pagamentos.map((p, i) => `
        <div class="pagamento-item">
            <span><i class="fas ${getIconePag(p.forma)} me-2"></i>${getNomePag(p.forma)}</span>
            <span>
                ${formatMoney(p.valor)}
                <button class="btn-remove-pag ms-2" onclick="removerPagamento(${i})"><i class="fas fa-times"></i></button>
            </span>
        </div>
    `).join('');

    document.getElementById('totalPago').textContent = formatMoney(pago);
    document.getElementById('restantePagar').textContent = formatMoney(restante);

    const trocoLine = document.getElementById('trocoLine');
    if (troco > 0) {
        trocoLine.style.display = 'flex';
        document.getElementById('trocoValor').textContent = formatMoney(troco);
    } else {
        trocoLine.style.display = 'none';
    }

    // Habilitar/desabilitar botao confirmar
    document.getElementById('btnConfirmarVenda').disabled = pago < total - 0.01;
}

// Finalizar venda
function finalizarVenda() {
    const total = getTotal();
    const pago = pagamentos.reduce((s, p) => s + p.valor, 0);

    if (pagamentos.length === 0) {
        alert('Adicione pelo menos uma forma de pagamento.');
        return;
    }

    if (pago < total - 0.01) {
        alert('Valor pago insuficiente.');
        return;
    }

    const btn = document.getElementById('btnConfirmarVenda');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processando...';

    // Verificar se deve emitir NFC-e
    const emitirNfceCheckbox = document.getElementById('emitirNfce');
    const emitirNfce = emitirNfceCheckbox ? emitirNfceCheckbox.checked : false;

    const data = {
        itens: itens,
        pagamentos: pagamentos,
        desconto_tipo: document.getElementById('descontoTipo').value,
        desconto_valor: parseFloat(document.getElementById('descontoGeral').value) || 0,
        cpf_cnpj: document.getElementById('cpfCnpjNota').value,
        caixa_id: CAIXA_ID,
        emitir_nfce: emitirNfce,
    };

    if (emitirNfce) {
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Emitindo NFC-e...';
    }

    fetch(`${BASE_URL}/pdv/finalizar.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF
        },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(result => {
        if (result.ok) {
            let msg = result.msg;
            if (result.troco > 0) {
                msg += `\n\nTROCO: ${formatMoney(result.troco)}`;
            }

            // Se emitiu NFC-e com sucesso, oferecer DANFCE
            if (result.nfce && result.nfce.ok && result.nfce.chave) {
                msg += `\n\nChave NFC-e: ${result.nfce.chave}`;
                alert(msg);
                // Abrir DANFCE em nova aba
                window.open(`${BASE_URL}/nfce/danfce.php?chave=${result.nfce.chave}`, '_blank');
            } else {
                alert(msg);
            }

            novaVenda();
        } else {
            alert('Erro: ' + result.msg);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check me-2"></i>Confirmar Venda';
        }
    })
    .catch(err => {
        alert('Erro de conexao: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check me-2"></i>Confirmar Venda';
    });
}

// Nova venda (limpar tudo)
function novaVenda() {
    itens = [];
    pagamentos = [];
    document.getElementById('descontoGeral').value = '0';
    document.getElementById('cpfCnpjNota').value = '';
    renderItens();
    calcularTotal();
    try { bootstrap.Modal.getInstance(document.getElementById('modalPagamento')).hide(); } catch(e) {}
    inputBusca.value = '';
    inputBusca.focus();
}

// Cancelar venda
function cancelarVenda() {
    if (itens.length === 0) return;
    if (confirm('Cancelar toda a venda? Todos os itens serao removidos.')) {
        novaVenda();
    }
}

// Helpers
function getTotal() {
    let subtotal = itens.reduce((sum, i) => sum + i.subtotal, 0);
    const descontoTipo = document.getElementById('descontoTipo').value;
    const descontoVal = parseFloat(document.getElementById('descontoGeral').value) || 0;
    let desconto = descontoTipo === 'percentual' ? (subtotal * descontoVal / 100) : descontoVal;
    return Math.max(0, subtotal - desconto);
}

function getRestante() {
    const total = getTotal();
    const pago = pagamentos.reduce((s, p) => s + p.valor, 0);
    return Math.max(0, total - pago);
}

function formatMoney(v) {
    return 'R$ ' + parseFloat(v).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function esc(s) {
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
}

function getIconePag(forma) {
    return { dinheiro: 'fa-money-bill', pix: 'fa-qrcode', debito: 'fa-credit-card', credito: 'fa-credit-card' }[forma] || 'fa-money-bill';
}

function getNomePag(forma) {
    return { dinheiro: 'Dinheiro', pix: 'PIX', debito: 'Debito', credito: 'Credito' }[forma] || forma;
}

function mostrarAjuda() {
    new bootstrap.Modal(document.getElementById('modalAjuda')).show();
}

// Atalhos de teclado
document.addEventListener('keydown', function(e) {
    // Ignorar se estiver em modal de quantidade
    if (document.getElementById('modalQtd').classList.contains('show')) return;

    switch(e.key) {
        case 'F1':
            e.preventDefault();
            mostrarAjuda();
            break;
        case 'F2':
            e.preventDefault();
            inputBusca.focus();
            inputBusca.select();
            break;
        case 'F4':
            e.preventDefault();
            document.getElementById('descontoGeral').focus();
            break;
        case 'F5':
            e.preventDefault();
            if (itens.length > 0) {
                removerItem(itens.length - 1);
            }
            break;
        case 'F9':
            e.preventDefault();
            abrirPagamento();
            break;
        case 'F10':
            e.preventDefault();
            abrirPagamento();
            break;
        case 'Escape':
            e.preventDefault();
            const modalPag = document.getElementById('modalPagamento');
            if (modalPag.classList.contains('show')) {
                bootstrap.Modal.getInstance(modalPag).hide();
            } else {
                cancelarVenda();
            }
            break;
    }
});

// Fechar busca ao clicar fora
document.addEventListener('click', function(e) {
    if (!e.target.closest('.pdv-search')) {
        fecharBusca();
    }
});

// Focus automatico no input de busca
inputBusca.focus();
