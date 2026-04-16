<?php
$pageTitle = 'PDV';
require_once __DIR__ . '/../../app/includes/auth.php';

$user = usuario();
$caixa = getCaixaAberto();

if (!$caixa) {
    flashWarning('Abra o caixa antes de iniciar as vendas.');
    redirect('caixa/abrir.php');
}

$nomeLoja = getConfig('nome_loja', APP_NAME);
$empresa = getEmpresa();

// Verificar se NFC-e está configurado
$nfceConfigurado = false;
try {
    $nfceHelper = new NfceHelper(tenantId());
    $nfceCheck = $nfceHelper->verificarConfiguracao();
    $nfceConfigurado = $nfceCheck['ok'];
} catch (\Exception) {}
$nfceAmbiente = (int) getConfig('nfce_ambiente', '2');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDV - <?= e($nomeLoja) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= baseUrl('assets/css/pdv.css') ?>" rel="stylesheet">
</head>
<body class="pdv-body">
    <!-- Header PDV -->
    <div class="pdv-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-cash-register me-2"></i>
                <strong><?= e($nomeLoja) ?></strong>
                <span class="ms-3 text-muted small">Operador: <?= e($user['nome']) ?></span>
                <span class="ms-3 text-muted small">Caixa #<?= $caixa['id'] ?></span>
            </div>
            <div>
                <span class="badge bg-secondary me-2" id="clock"></span>
                <a href="<?= baseUrl('dashboard/') ?>" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Voltar
                </a>
            </div>
        </div>
    </div>

    <div class="pdv-container">
        <!-- Area de busca e itens -->
        <div class="pdv-main">
            <!-- Busca de produto -->
            <div class="pdv-search">
                <div class="input-group input-group-lg">
                    <span class="input-group-text">
                        <i class="fas fa-barcode"></i>
                    </span>
                    <input type="text" id="inputBusca" class="form-control"
                           placeholder="Código de barras, código interno ou nome do produto... (F2)"
                           autofocus autocomplete="off">
                    <button class="btn btn-primary" onclick="buscarProduto()">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <!-- Resultados busca -->
                <div id="resultadosBusca" class="pdv-search-results d-none"></div>
            </div>

            <!-- Lista de itens -->
            <div class="pdv-items-container">
                <table class="table table-hover mb-0" id="tabelaItens">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="8%">Código</th>
                            <th width="32%">Descrição</th>
                            <th width="12%">Qtd</th>
                            <th width="13%">Vlr. Unit.</th>
                            <th width="10%">Desc.</th>
                            <th width="13%">Subtotal</th>
                            <th width="7%"></th>
                        </tr>
                    </thead>
                    <tbody id="listaItens">
                        <tr id="semItens">
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fas fa-shopping-cart fa-3x mb-3 d-block opacity-25"></i>
                                Nenhum item adicionado. Leia o código de barras ou pressione F2 para buscar.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Painel lateral -->
        <div class="pdv-sidebar">
            <!-- Total -->
            <div class="pdv-total-box">
                <div class="text-muted small">TOTAL</div>
                <div class="pdv-total" id="totalVenda">R$ 0,00</div>
                <div class="text-muted small"><span id="qtdItens">0</span> ite(ns)</div>
            </div>

            <!-- Desconto geral -->
            <div class="pdv-section">
                <label class="form-label text-muted small mb-1">Desconto Geral (F4)</label>
                <div class="input-group input-group-sm">
                    <select class="form-select form-control-dark" id="descontoTipo" style="max-width:70px;">
                        <option value="valor">R$</option>
                        <option value="percentual">%</option>
                    </select>
                    <input type="number" class="form-control form-control-dark" id="descontoGeral"
                           value="0" min="0" step="0.01" onchange="calcularTotal()">
                </div>
            </div>

            <!-- CPF/CNPJ na nota -->
            <div class="pdv-section">
                <label class="form-label text-muted small mb-1">CPF/CNPJ na Nota</label>
                <input type="text" class="form-control form-control-sm form-control-dark"
                       id="cpfCnpjNota" placeholder="Opcional" maxlength="18">
            </div>

            <!-- Atalhos -->
            <div class="pdv-section">
                <div class="pdv-shortcuts">
                    <button class="btn btn-outline-success btn-sm w-100 mb-1" onclick="abrirPagamento()" id="btnFinalizar">
                        <i class="fas fa-check-circle me-1"></i>Finalizar (F9)
                    </button>
                    <button class="btn btn-outline-warning btn-sm w-100 mb-1" onclick="cancelarVenda()">
                        <i class="fas fa-times-circle me-1"></i>Cancelar Venda (ESC)
                    </button>
                    <button class="btn btn-outline-info btn-sm w-100 mb-1" onclick="mostrarAjuda()">
                        <i class="fas fa-question-circle me-1"></i>Ajuda (F1)
                    </button>
                </div>
            </div>

            <!-- Subtotais -->
            <div class="pdv-section mt-auto">
                <div class="d-flex justify-content-between small text-muted">
                    <span>Subtotal:</span><span id="subtotalDisplay">R$ 0,00</span>
                </div>
                <div class="d-flex justify-content-between small text-muted">
                    <span>Desconto:</span><span id="descontoDisplay">R$ 0,00</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Pagamento -->
    <div class="modal fade" id="modalPagamento" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content pdv-modal">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-credit-card me-2"></i>Pagamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-7">
                            <h4 class="text-center mb-3">Total: <span class="text-success" id="totalPagar">R$ 0,00</span></h4>

                            <!-- Pagamentos adicionados -->
                            <div id="pagamentosLista" class="mb-3"></div>

                            <!-- Adicionar pagamento -->
                            <div class="card bg-secondary bg-opacity-25 mb-3">
                                <div class="card-body p-3">
                                    <div class="row g-2">
                                        <div class="col-5">
                                            <select class="form-select form-control-dark" id="formaPagamento">
                                                <option value="dinheiro">Dinheiro</option>
                                                <option value="pix">PIX</option>
                                                <option value="debito">Cartão Débito</option>
                                                <option value="credito">Cartão Crédito</option>
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <input type="number" class="form-control form-control-dark"
                                                   id="valorPagamento" placeholder="Valor" step="0.01" min="0">
                                        </div>
                                        <div class="col-3">
                                            <button class="btn btn-primary w-100" onclick="adicionarPagamento()">
                                                <i class="fas fa-plus"></i> Add
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Resumo -->
                            <div class="d-flex justify-content-between mb-1">
                                <span>Total Pago:</span>
                                <span class="fw-bold" id="totalPago">R$ 0,00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Restante:</span>
                                <span class="fw-bold text-warning" id="restantePagar">R$ 0,00</span>
                            </div>
                            <div class="d-flex justify-content-between" id="trocoLine" style="display:none!important">
                                <span>Troco:</span>
                                <span class="fw-bold text-info" id="trocoValor">R$ 0,00</span>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <!-- Atalhos rapidos -->
                            <h6 class="text-muted">Pagamento rápido:</h6>
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-success" onclick="pagamentoRapido('dinheiro')">
                                    <i class="fas fa-money-bill me-2"></i>Dinheiro (total)
                                </button>
                                <button class="btn btn-outline-primary" onclick="pagamentoRapido('pix')">
                                    <i class="fas fa-qrcode me-2"></i>PIX (total)
                                </button>
                                <button class="btn btn-outline-info" onclick="pagamentoRapido('debito')">
                                    <i class="fas fa-credit-card me-2"></i>Débito (total)
                                </button>
                                <button class="btn btn-outline-warning" onclick="pagamentoRapido('credito')">
                                    <i class="fas fa-credit-card me-2"></i>Crédito (total)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <div>
                        <?php if ($nfceConfigurado): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="emitirNfce" checked>
                            <label class="form-check-label" for="emitirNfce">
                                <i class="fas fa-file-invoice me-1"></i>Emitir NFC-e
                                <?php if ($nfceAmbiente === 2): ?>
                                    <span class="badge bg-warning text-dark ms-1">Homologação</span>
                                <?php endif; ?>
                            </label>
                        </div>
                        <?php else: ?>
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>NFC-e não configurada
                        </small>
                        <?php endif; ?>
                    </div>
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-success btn-lg" onclick="finalizarVenda()" id="btnConfirmarVenda">
                            <i class="fas fa-check me-2"></i>Confirmar Venda
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ajuda -->
    <div class="modal fade" id="modalAjuda" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content pdv-modal">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-keyboard me-2"></i>Atalhos de Teclado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-sm">
                        <tr><td><kbd>F1</kbd></td><td>Ajuda</td></tr>
                        <tr><td><kbd>F2</kbd></td><td>Buscar Produto</td></tr>
                        <tr><td><kbd>F4</kbd></td><td>Desconto</td></tr>
                        <tr><td><kbd>F5</kbd></td><td>Cancelar último item</td></tr>
                        <tr><td><kbd>F9</kbd></td><td>Finalizar Venda</td></tr>
                        <tr><td><kbd>F10</kbd></td><td>Pagamento misto</td></tr>
                        <tr><td><kbd>ESC</kbd></td><td>Cancelar venda inteira</td></tr>
                        <tr><td><kbd>Enter</kbd></td><td>Adicionar produto (na busca)</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Quantidade -->
    <div class="modal fade" id="modalQtd" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content pdv-modal">
                <div class="modal-header">
                    <h5 class="modal-title">Quantidade</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="number" class="form-control form-control-lg form-control-dark text-center"
                           id="inputQtdModal" min="0.001" step="0.001" value="1">
                    <input type="hidden" id="qtdItemIndex">
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="confirmarQtd()">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Audio beep -->
    <audio id="beepSound" preload="auto">
        <source src="data:audio/wav;base64,UklGRl9vT19teleWQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQ==" type="audio/wav">
    </audio>

    <input type="hidden" id="caixaId" value="<?= $caixa['id'] ?>">
    <input type="hidden" id="csrfToken" value="<?= csrfToken() ?>">
    <input type="hidden" id="baseUrl" value="<?= APP_URL ?>">
    <input type="hidden" id="nfceConfigurado" value="<?= $nfceConfigurado ? '1' : '0' ?>">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= baseUrl('assets/js/pdv.js') ?>"></script>
</body>
</html>
