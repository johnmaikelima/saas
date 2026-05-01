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
            <div class="d-flex align-items-center gap-3">
                <div class="pdv-brand">
                    <i class="fas fa-cash-register"></i>
                    <strong><?= e($nomeLoja) ?></strong>
                </div>
                <div class="pdv-header-meta">
                    <span><i class="fas fa-user me-1"></i><?= e($user['nome']) ?></span>
                    <span><i class="fas fa-cash-register me-1"></i>Caixa #<?= $caixa['id'] ?></span>
                </div>
            </div>
            <div class="pdv-status-bar">
                <span class="pdv-status-text" id="pdvStatus">CAIXA LIVRE</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-secondary" id="clock"></span>
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
            <div class="pdv-panel pdv-panel-search">
                <div class="pdv-panel-label"><i class="fas fa-barcode me-1"></i>Código de Barras / Busca</div>
                <div class="pdv-search">
                    <div class="input-group input-group-lg">
                        <input type="text" id="inputBusca" class="form-control"
                               placeholder="Leia o código de barras ou digite o nome do produto..."
                               autofocus autocomplete="off">
                        <button class="btn btn-primary" onclick="buscarProduto()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <div id="resultadosBusca" class="pdv-search-results d-none"></div>
                </div>
            </div>

            <!-- Lista de itens -->
            <div class="pdv-panel pdv-panel-lista">
                <div class="pdv-panel-label pdv-panel-label-primary"><i class="fas fa-list me-1"></i>Lista de Produtos</div>
                <div class="pdv-items-container">
                    <table class="table table-hover mb-0" id="tabelaItens">
                        <thead>
                            <tr>
                                <th width="5%">Item</th>
                                <th width="13%">Código</th>
                                <th width="32%">Descrição</th>
                                <th width="10%" class="text-center">Qtd</th>
                                <th width="13%" class="text-end">Vlr. Unit.</th>
                                <th width="10%" class="text-end">Desc.</th>
                                <th width="13%" class="text-end">Total</th>
                                <th width="4%"></th>
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
        </div>

        <!-- Painel lateral -->
        <div class="pdv-sidebar">
            <!-- Total -->
            <div class="pdv-panel pdv-panel-total">
                <div class="pdv-panel-label pdv-panel-label-success"><i class="fas fa-sack-dollar me-1"></i>Subtotal da Venda</div>
                <div class="pdv-total-box">
                    <div class="pdv-total" id="totalVenda">R$ 0,00</div>
                    <div class="pdv-total-meta"><span id="qtdItens">0</span> item(ns)</div>
                </div>
            </div>

            <!-- Desconto geral -->
            <div class="pdv-panel">
                <div class="pdv-panel-label">Desconto Geral (F4)</div>
                <div class="pdv-section">
                    <div class="input-group">
                        <select class="form-select form-control-dark" id="descontoTipo" style="max-width:70px;">
                            <option value="valor">R$</option>
                            <option value="percentual">%</option>
                        </select>
                        <input type="number" class="form-control form-control-dark" id="descontoGeral"
                               value="0" min="0" step="0.01" onchange="calcularTotal()">
                    </div>
                </div>
            </div>

            <!-- CPF/CNPJ na nota -->
            <div class="pdv-panel">
                <div class="pdv-panel-label">CPF/CNPJ na Nota</div>
                <div class="pdv-section">
                    <input type="text" class="form-control form-control-dark"
                           id="cpfCnpjNota" placeholder="Opcional" maxlength="18">
                </div>
            </div>

            <!-- Resumo -->
            <div class="pdv-panel pdv-panel-resumo mt-auto">
                <div class="pdv-resumo-row">
                    <span>Subtotal:</span><span id="subtotalDisplay">R$ 0,00</span>
                </div>
                <div class="pdv-resumo-row">
                    <span>Desconto:</span><span id="descontoDisplay">R$ 0,00</span>
                </div>
            </div>

            <!-- Botão Finalizar -->
            <button class="btn btn-success pdv-btn-finalizar-main" onclick="abrirPagamento()" id="btnFinalizar">
                <i class="fas fa-check-circle me-2"></i>FINALIZAR (F9)
            </button>
        </div>
    </div>

    <!-- Barra de Atalhos (rodape fixo) -->
    <div class="pdv-keybar">
        <div class="pdv-keybar-group">
            <span class="pdv-key"><kbd>F1</kbd> Ajuda</span>
            <span class="pdv-key"><kbd>F2</kbd> Buscar</span>
            <span class="pdv-key"><kbd>F4</kbd> Desconto</span>
            <span class="pdv-key"><kbd>F5</kbd> Excluir Item</span>
        </div>
        <div class="pdv-keybar-group">
            <span class="pdv-key pdv-key-primary"><kbd>F9</kbd> Finalizar</span>
            <span class="pdv-key"><kbd>F10</kbd> Pagto Misto</span>
        </div>
        <div class="pdv-keybar-group">
            <span class="pdv-key pdv-key-danger"><kbd>ESC</kbd> Cancelar Venda</span>
            <span class="pdv-key"><kbd>Enter</kbd> Adicionar</span>
        </div>
    </div>

    <!-- Modal Pagamento -->
    <div class="modal fade" id="modalPagamento" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content pdv-modal pdv-pagamento-modal">
                <div class="modal-header py-3">
                    <h3 class="modal-title mb-0"><i class="fas fa-credit-card me-2"></i>Pagamento</h3>
                    <button type="button" class="btn-close btn-close-lg" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <div class="col-md-7">
                            <div class="pdv-total-display text-center mb-4">
                                <div class="small text-muted text-uppercase fw-semibold">Total a Pagar</div>
                                <div class="pdv-total-value text-success" id="totalPagar">R$ 0,00</div>
                            </div>

                            <!-- Pagamentos adicionados -->
                            <div id="pagamentosLista" class="mb-3"></div>

                            <!-- Adicionar pagamento -->
                            <div class="card bg-secondary bg-opacity-25 mb-3">
                                <div class="card-body p-3">
                                    <div class="row g-2">
                                        <div class="col-5">
                                            <select class="form-select form-select-lg form-control-dark" id="formaPagamento">
                                                <option value="dinheiro">Dinheiro</option>
                                                <option value="pix">PIX</option>
                                                <option value="debito">Cartão Débito</option>
                                                <option value="credito">Cartão Crédito</option>
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <input type="number" class="form-control form-control-lg form-control-dark"
                                                   id="valorPagamento" placeholder="Valor" step="0.01" min="0">
                                        </div>
                                        <div class="col-3">
                                            <button class="btn btn-primary btn-lg w-100" onclick="adicionarPagamento()">
                                                <i class="fas fa-plus"></i> Add
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Resumo -->
                            <div class="pdv-resumo">
                                <div class="d-flex justify-content-between">
                                    <span>Total Pago:</span>
                                    <span class="fw-bold" id="totalPago">R$ 0,00</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Restante:</span>
                                    <span class="fw-bold text-warning" id="restantePagar">R$ 0,00</span>
                                </div>
                                <div class="d-flex justify-content-between" id="trocoLine" hidden>
                                    <span>Troco:</span>
                                    <span class="fw-bold text-info" id="trocoValor">R$ 0,00</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <!-- Atalhos rapidos -->
                            <h5 class="text-muted mb-3"><i class="fas fa-bolt me-1"></i>Pagamento rápido</h5>
                            <div class="d-grid gap-2 pdv-rapido-btns">
                                <button class="btn btn-outline-success btn-lg" onclick="pagamentoRapido('dinheiro')">
                                    <i class="fas fa-money-bill me-2"></i>Dinheiro (total)
                                </button>
                                <button class="btn btn-outline-primary btn-lg" onclick="pagamentoRapido('pix')">
                                    <i class="fas fa-qrcode me-2"></i>PIX (total)
                                </button>
                                <button class="btn btn-outline-info btn-lg" onclick="pagamentoRapido('debito')">
                                    <i class="fas fa-credit-card me-2"></i>Débito (total)
                                </button>
                                <button class="btn btn-outline-warning btn-lg" onclick="pagamentoRapido('credito')">
                                    <i class="fas fa-credit-card me-2"></i>Crédito (total)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-column align-items-stretch p-4">
                    <?php if ($nfceConfigurado && $nfceAmbiente === 2): ?>
                        <div class="text-center mb-2">
                            <span class="badge bg-warning text-dark fs-6"><i class="fas fa-flask me-1"></i>NFC-e em Homologação</span>
                        </div>
                    <?php endif; ?>
                    <div class="row g-2">
                        <?php if ($nfceConfigurado): ?>
                        <div class="col-md-7">
                            <button type="button" class="btn btn-success pdv-btn-finalizar w-100" onclick="finalizarVenda(true)" id="btnFinalizarComNfce">
                                <i class="fas fa-file-invoice me-2"></i>Finalizar + NFC-e
                            </button>
                        </div>
                        <div class="col-md-5">
                            <button type="button" class="btn btn-outline-secondary pdv-btn-finalizar w-100" onclick="finalizarVenda(false)" id="btnFinalizarSemNota">
                                <i class="fas fa-check me-2"></i>Sem Nota
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="col-12">
                            <button type="button" class="btn btn-success pdv-btn-finalizar w-100" onclick="finalizarVenda(false)" id="btnFinalizarSemNota">
                                <i class="fas fa-check me-2"></i>Finalizar Venda (sem NFC-e)
                            </button>
                            <div class="text-center mt-2">
                                <small class="text-muted"><i class="fas fa-info-circle me-1"></i>NFC-e não configurada</small>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="col-12 text-end">
                            <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal">Cancelar (ESC)</button>
                        </div>
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
