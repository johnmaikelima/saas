<?php
$pageTitle = 'Orçamento';
require_once __DIR__ . '/../../app/includes/auth.php';

$pdo = db();
$user = usuario();
$tid = tenantId();
$id = (int)($_GET['id'] ?? 0);

$orcamento = null;
$itens = [];

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM orcamentos WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$id, $tid]);
    $orcamento = $stmt->fetch();
    if (!$orcamento) {
        flashError('Orçamento não encontrado.');
        redirect('orcamentos/');
    }
    if ($orcamento['status'] === 'convertido') {
        flashWarning('Orçamentos convertidos não podem ser editados.');
        redirect('orcamentos/view.php?id=' . $id);
    }

    $stmtI = $pdo->prepare("SELECT oi.*, p.codigo_barras, p.estoque_atual
        FROM orcamento_itens oi
        LEFT JOIN produtos p ON p.id = oi.produto_id
        WHERE oi.orcamento_id = ? AND oi.tenant_id = ?
        ORDER BY oi.ordem, oi.id");
    $stmtI->execute([$id, $tid]);
    $itens = $stmtI->fetchAll();
}

// Proximo numero (se novo)
if (!$orcamento) {
    $stmtN = $pdo->prepare("SELECT COALESCE(MAX(numero),0)+1 FROM orcamentos WHERE tenant_id = ?");
    $stmtN->execute([$tid]);
    $proximoNumero = (int)$stmtN->fetchColumn();
} else {
    $proximoNumero = (int)$orcamento['numero'];
}

// Lista de clientes (carregada no select, busca AJAX para muitos)
$stmtCli = $pdo->prepare("SELECT id, nome, cpf_cnpj, telefone, email FROM clientes WHERE tenant_id = ? ORDER BY nome LIMIT 1000");
$stmtCli->execute([$tid]);
$clientes = $stmtCli->fetchAll();

$validadePadrao = (int) getConfig('orcamento_validade_dias', '15');
$dataOrcamento = $orcamento['data_orcamento'] ?? date('Y-m-d');
$validade = $orcamento['validade'] ?? date('Y-m-d', strtotime("+{$validadePadrao} days"));

$itensJson = json_encode(array_map(fn($i) => [
    'produto_id' => $i['produto_id'],
    'descricao' => $i['descricao'],
    'codigo_barras' => $i['codigo_barras'] ?? '',
    'quantidade' => (float)$i['quantidade'],
    'valor_unitario' => (float)$i['valor_unitario'],
    'desconto' => (float)$i['desconto'],
    'subtotal' => (float)$i['subtotal'],
], $itens), JSON_UNESCAPED_UNICODE);

require __DIR__ . '/../../app/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="page-title mb-0">
        <i class="fas fa-file-signature me-2"></i>
        <?= $id ? 'Editar Orçamento #' . str_pad($proximoNumero, 4, '0', STR_PAD_LEFT) : 'Novo Orçamento' ?>
    </h5>
    <a href="<?= baseUrl('orcamentos/') ?>" class="btn btn-secondary btn-sm">Voltar</a>
</div>

<form id="formOrcamento">
    <?= csrfField() ?>
    <input type="hidden" name="id" value="<?= (int)$id ?>">

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><i class="fas fa-info-circle me-1"></i>Dados Gerais</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-2">
                            <label class="form-label small">Número</label>
                            <input type="text" class="form-control form-control-sm" value="#<?= str_pad($proximoNumero, 4, '0', STR_PAD_LEFT) ?>" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Data <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" name="data_orcamento" value="<?= e($dataOrcamento) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Validade <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" name="validade" value="<?= e($validade) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Vendedor</label>
                            <input type="text" class="form-control form-control-sm" value="<?= e($user['nome']) ?>" readonly>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small">Cliente</label>
                            <div class="input-group input-group-sm">
                                <select class="form-select form-select-sm" name="cliente_id" id="clienteSel">
                                    <option value="">-- Sem cliente / Consumidor --</option>
                                    <?php foreach ($clientes as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= ($orcamento['cliente_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>>
                                            <?= e($c['nome']) ?><?= $c['cpf_cnpj'] ? ' - ' . e($c['cpf_cnpj']) : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalNovoCliente" title="Cadastrar novo cliente">
                                    <i class="fas fa-user-plus me-1"></i>Novo
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-box me-1"></i>Itens</span>
                    <small class="text-muted">Digite o código de barras, ID ou nome</small>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-2">
                        <div class="col-md-9">
                            <input type="text" id="buscaProduto" class="form-control form-control-sm" placeholder="Buscar produto..." autocomplete="off">
                            <div id="produtoSugestoes" class="list-group position-absolute shadow-sm" style="z-index:100; max-height:300px; overflow-y:auto; display:none;"></div>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-outline-primary btn-sm w-100" id="btnItemManual">
                                <i class="fas fa-plus me-1"></i>Item manual
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40%">Descrição</th>
                                    <th style="width:12%">Qtd</th>
                                    <th style="width:15%">Vlr Unit</th>
                                    <th style="width:12%">Desc.</th>
                                    <th style="width:15%">Subtotal</th>
                                    <th style="width:6%"></th>
                                </tr>
                            </thead>
                            <tbody id="tblItens">
                                <tr id="emptyRow"><td colspan="6" class="text-center text-muted py-3">Nenhum item adicionado</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><i class="fas fa-file-alt me-1"></i>Condições e Observações</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small">Condições de Pagamento</label>
                            <textarea class="form-control form-control-sm" name="condicoes_pagamento" rows="2" placeholder="Ex: 50% de entrada + 30 dias"><?= e($orcamento['condicoes_pagamento'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Prazo de Entrega</label>
                            <textarea class="form-control form-control-sm" name="prazo_entrega" rows="2" placeholder="Ex: 7 dias úteis após confirmação"><?= e($orcamento['prazo_entrega'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small">Observações para o Cliente</label>
                            <textarea class="form-control form-control-sm" name="observacoes" rows="2"><?= e($orcamento['observacoes'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small text-muted">Observações Internas <i class="fas fa-lock ms-1" title="Não aparece no PDF do cliente"></i></label>
                            <textarea class="form-control form-control-sm" name="observacoes_internas" rows="2"><?= e($orcamento['observacoes_internas'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-3 sticky-top" style="top:15px;">
                <div class="card-header bg-light"><i class="fas fa-calculator me-1"></i>Totais</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span id="lblSubtotal">R$ 0,00</span>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small mb-1">Desconto Geral</label>
                        <div class="input-group input-group-sm">
                            <select class="form-select" name="desconto_tipo" id="descontoTipo" style="max-width:90px;">
                                <option value="valor" <?= ($orcamento['desconto_tipo'] ?? 'valor') === 'valor' ? 'selected' : '' ?>>R$</option>
                                <option value="percentual" <?= ($orcamento['desconto_tipo'] ?? '') === 'percentual' ? 'selected' : '' ?>>%</option>
                            </select>
                            <input type="number" step="0.01" min="0" class="form-control text-end" name="desconto_valor" id="descontoValor" value="<?= (float)($orcamento['desconto_valor'] ?? 0) ?>">
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fs-4 fw-bold text-success">
                        <span>Total:</span>
                        <span id="lblTotal">R$ 0,00</span>
                    </div>

                    <hr>
                    <button type="button" class="btn btn-primary w-100 mb-2" id="btnSalvar">
                        <i class="fas fa-save me-1"></i>Salvar
                    </button>
                    <button type="button" class="btn btn-outline-primary w-100" id="btnSalvarEnviar">
                        <i class="fas fa-paper-plane me-1"></i>Salvar e Enviar
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
const CSRF = '<?= csrfToken() ?>';
const ORCAMENTO_ID = <?= (int)$id ?>;
const ITENS_INICIAIS = <?= $itensJson ?: '[]' ?>;
let itens = [];

function formatMoney(v) {
    return 'R$ ' + Number(v || 0).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function renderItens() {
    const tbl = document.getElementById('tblItens');
    if (itens.length === 0) {
        tbl.innerHTML = '<tr id="emptyRow"><td colspan="6" class="text-center text-muted py-3">Nenhum item adicionado</td></tr>';
    } else {
        tbl.innerHTML = itens.map((it, idx) => `
            <tr>
                <td>
                    <input type="text" class="form-control form-control-sm" value="${it.descricao.replace(/"/g, '&quot;')}" onchange="atualizarItem(${idx}, 'descricao', this.value)">
                    ${it.codigo_barras ? '<small class="text-muted">'+it.codigo_barras+'</small>' : ''}
                </td>
                <td><input type="number" step="0.001" min="0.001" class="form-control form-control-sm text-end" value="${it.quantidade}" onchange="atualizarItem(${idx}, 'quantidade', this.value)"></td>
                <td><input type="number" step="0.01" min="0" class="form-control form-control-sm text-end" value="${it.valor_unitario}" onchange="atualizarItem(${idx}, 'valor_unitario', this.value)"></td>
                <td><input type="number" step="0.01" min="0" class="form-control form-control-sm text-end" value="${it.desconto}" onchange="atualizarItem(${idx}, 'desconto', this.value)"></td>
                <td class="text-end fw-bold">${formatMoney(it.subtotal)}</td>
                <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removerItem(${idx})"><i class="fas fa-trash"></i></button></td>
            </tr>
        `).join('');
    }
    calcularTotais();
}

function atualizarItem(idx, campo, valor) {
    if (['quantidade', 'valor_unitario', 'desconto'].includes(campo)) {
        itens[idx][campo] = parseFloat(valor) || 0;
    } else {
        itens[idx][campo] = valor;
    }
    itens[idx].subtotal = Math.max(0, (itens[idx].quantidade * itens[idx].valor_unitario) - itens[idx].desconto);
    renderItens();
}

function removerItem(idx) {
    itens.splice(idx, 1);
    renderItens();
}

function adicionarItem(produto) {
    const existente = itens.find(i => i.produto_id && i.produto_id == produto.id);
    if (existente) {
        existente.quantidade = parseFloat(existente.quantidade) + 1;
        existente.subtotal = Math.max(0, (existente.quantidade * existente.valor_unitario) - existente.desconto);
    } else {
        itens.push({
            produto_id: produto.id,
            descricao: produto.descricao,
            codigo_barras: produto.codigo_barras || '',
            quantidade: 1,
            valor_unitario: parseFloat(produto.preco_venda),
            desconto: 0,
            subtotal: parseFloat(produto.preco_venda)
        });
    }
    renderItens();
}

function calcularTotais() {
    const subtotal = itens.reduce((s, i) => s + parseFloat(i.subtotal || 0), 0);
    const descTipo = document.getElementById('descontoTipo').value;
    const descVal = parseFloat(document.getElementById('descontoValor').value) || 0;
    const desc = descTipo === 'percentual' ? (subtotal * descVal / 100) : descVal;
    const total = Math.max(0, subtotal - desc);
    document.getElementById('lblSubtotal').textContent = formatMoney(subtotal);
    document.getElementById('lblTotal').textContent = formatMoney(total);
}

// Busca de produto
let timerBusca;
document.getElementById('buscaProduto').addEventListener('input', (e) => {
    clearTimeout(timerBusca);
    const q = e.target.value.trim();
    const caixa = document.getElementById('produtoSugestoes');
    if (q.length < 2) { caixa.style.display = 'none'; return; }

    timerBusca = setTimeout(async () => {
        const res = await fetch('<?= baseUrl('pdv/buscar_produto.php') ?>?q=' + encodeURIComponent(q));
        const produtos = await res.json();
        if (produtos.length === 1 && produtos[0].codigo_barras === q) {
            adicionarItem(produtos[0]);
            e.target.value = '';
            caixa.style.display = 'none';
            return;
        }
        if (produtos.length === 0) {
            caixa.innerHTML = '<div class="list-group-item text-muted small">Nenhum produto encontrado</div>';
        } else {
            caixa.innerHTML = produtos.map(p => `
                <a href="#" class="list-group-item list-group-item-action small" data-id="${p.id}">
                    <strong>${p.descricao}</strong>
                    <span class="text-muted ms-2">${p.codigo_barras || ''}</span>
                    <span class="float-end fw-bold">${formatMoney(p.preco_venda)}</span>
                </a>
            `).join('');
            caixa.querySelectorAll('a').forEach(a => {
                a.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    const prod = produtos.find(p => p.id == a.dataset.id);
                    adicionarItem(prod);
                    document.getElementById('buscaProduto').value = '';
                    caixa.style.display = 'none';
                });
            });
        }
        caixa.style.display = 'block';
    }, 250);
});

document.addEventListener('click', (e) => {
    if (!e.target.closest('#buscaProduto') && !e.target.closest('#produtoSugestoes')) {
        document.getElementById('produtoSugestoes').style.display = 'none';
    }
});

// Item manual
document.getElementById('btnItemManual').addEventListener('click', () => {
    const desc = prompt('Descrição do item:');
    if (!desc) return;
    const valor = parseFloat(prompt('Valor unitário:', '0')) || 0;
    itens.push({
        produto_id: null,
        descricao: desc,
        codigo_barras: '',
        quantidade: 1,
        valor_unitario: valor,
        desconto: 0,
        subtotal: valor
    });
    renderItens();
});

document.getElementById('descontoTipo').addEventListener('change', calcularTotais);
document.getElementById('descontoValor').addEventListener('input', calcularTotais);

async function salvar(enviar = false) {
    if (itens.length === 0) {
        alert('Adicione pelo menos um item.');
        return;
    }
    const btn = enviar ? document.getElementById('btnSalvarEnviar') : document.getElementById('btnSalvar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvando...';

    const form = document.getElementById('formOrcamento');
    const payload = {
        id: ORCAMENTO_ID,
        data_orcamento: form.data_orcamento.value,
        validade: form.validade.value,
        cliente_id: form.cliente_id.value || null,
        desconto_tipo: form.desconto_tipo.value,
        desconto_valor: parseFloat(form.desconto_valor.value) || 0,
        condicoes_pagamento: form.condicoes_pagamento.value,
        prazo_entrega: form.prazo_entrega.value,
        observacoes: form.observacoes.value,
        observacoes_internas: form.observacoes_internas.value,
        itens: itens,
    };

    try {
        const res = await fetch('<?= baseUrl('orcamentos/salvar.php') ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-Token': CSRF},
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.ok) {
            if (enviar) {
                window.location.href = '<?= baseUrl('orcamentos/view.php') ?>?id=' + data.id + '&enviar=1';
            } else {
                window.location.href = '<?= baseUrl('orcamentos/view.php') ?>?id=' + data.id;
            }
        } else {
            alert(data.msg || 'Erro ao salvar.');
            btn.disabled = false;
            btn.innerHTML = enviar ? '<i class="fas fa-paper-plane me-1"></i>Salvar e Enviar' : '<i class="fas fa-save me-1"></i>Salvar';
        }
    } catch (e) {
        alert('Erro de rede: ' + e.message);
        btn.disabled = false;
    }
}

document.getElementById('btnSalvar').addEventListener('click', () => salvar(false));
document.getElementById('btnSalvarEnviar').addEventListener('click', () => salvar(true));

// Inicializar
itens = ITENS_INICIAIS.slice();
renderItens();

// Novo cliente via modal
window.addEventListener('cliente:criado', (ev) => {
    const sel = document.getElementById('clienteSel');
    const label = ev.detail.label || ev.detail.nome;
    const opt = new Option(label, ev.detail.id, true, true);
    sel.add(opt);
});
</script>

<?php require __DIR__ . '/../../app/includes/modal_cliente.php'; ?>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
