<?php
$pageTitle = 'Orçamento';
require_once __DIR__ . '/../../app/includes/auth.php';

$pdo = db();
$user = usuario();
$tid = tenantId();
$id = (int)($_GET['id'] ?? 0);
$abrirEnviar = !empty($_GET['enviar']);

$stmt = $pdo->prepare("SELECT o.*, u.nome as vendedor_nome,
    c.nome as cliente_nome, c.cpf_cnpj as cliente_doc, c.telefone as cliente_tel,
    c.celular as cliente_cel, c.email as cliente_email, c.endereco as cliente_endereco,
    c.cidade as cliente_cidade, c.estado as cliente_estado
    FROM orcamentos o
    LEFT JOIN usuarios u ON u.id = o.usuario_id
    LEFT JOIN clientes c ON c.id = o.cliente_id
    WHERE o.id = ? AND o.tenant_id = ?");
$stmt->execute([$id, $tid]);
$orc = $stmt->fetch();
if (!$orc) {
    flashError('Orçamento não encontrado.');
    redirect('orcamentos/');
}

$stmtI = $pdo->prepare("SELECT oi.*, p.codigo_barras
    FROM orcamento_itens oi
    LEFT JOIN produtos p ON p.id = oi.produto_id
    WHERE oi.orcamento_id = ? AND oi.tenant_id = ?
    ORDER BY oi.ordem, oi.id");
$stmtI->execute([$id, $tid]);
$itens = $stmtI->fetchAll();

$stmtH = $pdo->prepare("SELECT h.*, u.nome as usuario_nome
    FROM orcamento_historico h
    LEFT JOIN usuarios u ON u.id = h.usuario_id
    WHERE h.orcamento_id = ? AND h.tenant_id = ?
    ORDER BY h.criado_em DESC");
$stmtH->execute([$id, $tid]);
$historico = $stmtH->fetchAll();

$statusColors = [
    'pendente' => 'warning', 'aprovado' => 'info', 'convertido' => 'success',
    'recusado' => 'danger', 'expirado' => 'secondary', 'cancelado' => 'dark'
];
$cor = $statusColors[$orc['status']] ?? 'secondary';
$linkPublico = baseUrl('orcamento-publico/?t=' . $orc['token_publico']);

$podeEditar = !in_array($orc['status'], ['convertido','cancelado']);
$podeAprovar = $orc['status'] === 'pendente';
$podeConverter = in_array($orc['status'], ['aprovado','pendente']);
$podeCancelar = !in_array($orc['status'], ['convertido','cancelado']);

require __DIR__ . '/../../app/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="page-title mb-0">
        <i class="fas fa-file-signature me-2"></i>Orçamento #<?= str_pad($orc['numero'], 4, '0', STR_PAD_LEFT) ?>
        <span class="badge bg-<?= $cor ?> ms-2"><?= ucfirst(e($orc['status'])) ?></span>
    </h5>
    <div class="d-flex gap-1 flex-wrap">
        <a href="<?= baseUrl('orcamentos/pdf.php?id=' . $id) ?>" class="btn btn-outline-secondary btn-sm" target="_blank">
            <i class="fas fa-file-pdf me-1"></i>PDF
        </a>
        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEnviar">
            <i class="fas fa-paper-plane me-1"></i>Enviar
        </button>
        <?php if ($podeEditar): ?>
            <a href="form.php?id=<?= $id ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-edit me-1"></i>Editar</a>
        <?php endif; ?>
        <button class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalDuplicar">
            <i class="fas fa-copy me-1"></i>Duplicar
        </button>
        <?php if ($podeAprovar): ?>
            <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAprovar">
                <i class="fas fa-check me-1"></i>Aprovar
            </button>
            <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalRecusar">
                <i class="fas fa-times me-1"></i>Recusar
            </button>
        <?php endif; ?>
        <?php if ($podeConverter): ?>
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalConverter">
                <i class="fas fa-exchange-alt me-1"></i>Converter em Venda
            </button>
        <?php endif; ?>
        <?php if ($podeCancelar && temPerfil('admin','gerente')): ?>
            <button class="btn btn-outline-dark btn-sm" data-bs-toggle="modal" data-bs-target="#modalCancelar">
                <i class="fas fa-ban me-1"></i>Cancelar
            </button>
        <?php endif; ?>
        <a href="<?= baseUrl('orcamentos/') ?>" class="btn btn-secondary btn-sm">Voltar</a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light d-flex justify-content-between">
                <span><i class="fas fa-info-circle me-1"></i>Informações</span>
                <?php if ($orc['venda_id']): ?>
                    <a href="<?= baseUrl('vendas/view.php?id=' . $orc['venda_id']) ?>" class="small">
                        <i class="fas fa-external-link-alt me-1"></i>Ver Venda #<?= $orc['venda_id'] ?>
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-3"><strong>Data:</strong> <?= formatDate($orc['data_orcamento']) ?></div>
                    <div class="col-md-3"><strong>Validade:</strong> <?= formatDate($orc['validade']) ?></div>
                    <div class="col-md-3"><strong>Vendedor:</strong> <?= e($orc['vendedor_nome'] ?: '-') ?></div>
                    <div class="col-md-3"><strong>Criado:</strong> <?= formatDateTime($orc['criado_em']) ?></div>
                    <?php if ($orc['cliente_nome']): ?>
                        <div class="col-md-6 mt-2"><strong>Cliente:</strong> <?= e($orc['cliente_nome']) ?></div>
                        <?php if ($orc['cliente_doc']): ?><div class="col-md-6 mt-2"><strong>CPF/CNPJ:</strong> <?= e($orc['cliente_doc']) ?></div><?php endif; ?>
                        <?php if ($orc['cliente_cel'] || $orc['cliente_tel']): ?><div class="col-md-6 mt-1"><strong>Telefone:</strong> <?= e($orc['cliente_cel'] ?: $orc['cliente_tel']) ?></div><?php endif; ?>
                        <?php if ($orc['cliente_email']): ?><div class="col-md-6 mt-1"><strong>Email:</strong> <?= e($orc['cliente_email']) ?></div><?php endif; ?>
                    <?php else: ?>
                        <div class="col-12 mt-2 text-muted"><strong>Cliente:</strong> Consumidor não identificado</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light">Itens</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>#</th><th>Descrição</th><th class="text-end">Qtd</th><th class="text-end">Vlr Unit</th><th class="text-end">Desc.</th><th class="text-end">Subtotal</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($itens as $i => $item): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <?= e($item['descricao']) ?>
                                <?php if ($item['codigo_barras']): ?><small class="text-muted d-block"><?= e($item['codigo_barras']) ?></small><?php endif; ?>
                            </td>
                            <td class="text-end"><?= rtrim(rtrim(number_format($item['quantidade'], 3, ',', '.'), '0'), ',') ?></td>
                            <td class="text-end"><?= formatMoney($item['valor_unitario']) ?></td>
                            <td class="text-end"><?= formatMoney($item['desconto']) ?></td>
                            <td class="text-end fw-bold"><?= formatMoney($item['subtotal']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($orc['condicoes_pagamento'] || $orc['prazo_entrega'] || $orc['observacoes']): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light">Condições e Observações</div>
            <div class="card-body">
                <?php if ($orc['condicoes_pagamento']): ?>
                    <p class="mb-2"><strong>Pagamento:</strong><br><?= nl2br(e($orc['condicoes_pagamento'])) ?></p>
                <?php endif; ?>
                <?php if ($orc['prazo_entrega']): ?>
                    <p class="mb-2"><strong>Prazo de entrega:</strong><br><?= nl2br(e($orc['prazo_entrega'])) ?></p>
                <?php endif; ?>
                <?php if ($orc['observacoes']): ?>
                    <p class="mb-0"><strong>Observações:</strong><br><?= nl2br(e($orc['observacoes'])) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($orc['observacoes_internas']): ?>
        <div class="card shadow-sm mb-3 border-warning">
            <div class="card-header bg-warning-subtle"><i class="fas fa-lock me-1"></i>Observações Internas</div>
            <div class="card-body">
                <p class="mb-0 small"><?= nl2br(e($orc['observacoes_internas'])) ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light">Totais</div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal:</span><span><?= formatMoney($orc['subtotal']) ?></span>
                </div>
                <?php if ($orc['desconto_valor'] > 0): ?>
                    <div class="d-flex justify-content-between mb-2 text-danger">
                        <span>Desconto (<?= $orc['desconto_tipo'] === 'percentual' ? number_format($orc['desconto_valor'], 2, ',', '.') . '%' : 'R$' ?>):</span>
                        <span>-<?= formatMoney($orc['desconto_tipo'] === 'percentual' ? ($orc['subtotal'] * $orc['desconto_valor'] / 100) : $orc['desconto_valor']) ?></span>
                    </div>
                <?php endif; ?>
                <hr>
                <div class="d-flex justify-content-between fw-bold fs-4 text-success">
                    <span>Total:</span><span><?= formatMoney($orc['total']) ?></span>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light"><i class="fas fa-link me-1"></i>Link Público</div>
            <div class="card-body">
                <p class="small text-muted mb-2">Envie este link para o cliente aprovar ou recusar online:</p>
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" value="<?= e($linkPublico) ?>" readonly id="linkPublico">
                    <button class="btn btn-outline-primary" onclick="copiarLink()"><i class="fas fa-copy"></i></button>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light"><i class="fas fa-history me-1"></i>Histórico</div>
            <div class="card-body" style="max-height:300px; overflow-y:auto;">
                <?php if (empty($historico)): ?>
                    <p class="text-muted small mb-0">Nenhum registro.</p>
                <?php endif; ?>
                <?php foreach ($historico as $h):
                    $acaoIcones = [
                        'criado' => 'fa-plus-circle text-success',
                        'editado' => 'fa-edit text-warning',
                        'enviado' => 'fa-paper-plane text-info',
                        'aprovado' => 'fa-check-circle text-success',
                        'recusado' => 'fa-times-circle text-danger',
                        'convertido' => 'fa-exchange-alt text-success',
                        'duplicado' => 'fa-copy text-info',
                        'cancelado' => 'fa-ban text-dark',
                    ];
                    $icon = $acaoIcones[$h['acao']] ?? 'fa-circle';
                ?>
                    <div class="d-flex mb-2 small">
                        <i class="fas <?= $icon ?> me-2 mt-1"></i>
                        <div class="flex-grow-1">
                            <strong><?= ucfirst(e($h['acao'])) ?></strong>
                            <?php if ($h['descricao']): ?><span class="text-muted"> - <?= e($h['descricao']) ?></span><?php endif; ?>
                            <div class="text-muted" style="font-size:0.75rem;">
                                <?= formatDateTime($h['criado_em']) ?>
                                <?php if ($h['usuario_nome']): ?> · <?= e($h['usuario_nome']) ?><?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Enviar -->
<div class="modal fade" id="modalEnviar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-paper-plane me-1"></i>Enviar Orçamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Envie o orçamento ao cliente por WhatsApp ou Email.</p>
                <div class="d-grid gap-2">
                    <?php
                    $msgWhats = "Olá! Segue o orçamento #" . str_pad($orc['numero'],4,'0',STR_PAD_LEFT) .
                        " no valor de " . formatMoney($orc['total']) .
                        ". Validade: " . formatDate($orc['validade']) .
                        ".\nVocê pode visualizar e aprovar online: " . $linkPublico;
                    $telefone = preg_replace('/\D/', '', $orc['cliente_cel'] ?: $orc['cliente_tel'] ?: '');
                    if ($telefone && !str_starts_with($telefone, '55')) $telefone = '55' . $telefone;
                    $whatsUrl = 'https://wa.me/' . ($telefone ?: '') . '?text=' . urlencode($msgWhats);
                    ?>
                    <a href="<?= e($whatsUrl) ?>" target="_blank" class="btn btn-success" onclick="marcarEnviado('whatsapp')">
                        <i class="fab fa-whatsapp me-2"></i>Abrir no WhatsApp
                    </a>
                    <?php if ($orc['cliente_email']): ?>
                        <?php
                        $assunto = "Orçamento #" . str_pad($orc['numero'],4,'0',STR_PAD_LEFT);
                        $corpo = "Olá,\n\nSegue o orçamento #" . str_pad($orc['numero'],4,'0',STR_PAD_LEFT) .
                            " no valor de " . formatMoney($orc['total']) .
                            ".\nValidade: " . formatDate($orc['validade']) .
                            "\n\nVocê pode visualizar e aprovar online pelo link: " . $linkPublico .
                            "\n\nAtenciosamente.";
                        $mailto = 'mailto:' . $orc['cliente_email'] . '?subject=' . rawurlencode($assunto) . '&body=' . rawurlencode($corpo);
                        ?>
                        <a href="<?= e($mailto) ?>" class="btn btn-primary" onclick="marcarEnviado('email')">
                            <i class="fas fa-envelope me-2"></i>Enviar por Email
                        </a>
                    <?php else: ?>
                        <button class="btn btn-primary" disabled><i class="fas fa-envelope me-2"></i>Cliente sem email</button>
                    <?php endif; ?>
                    <button class="btn btn-outline-secondary" onclick="copiarLink(); marcarEnviado('link')">
                        <i class="fas fa-copy me-2"></i>Copiar Link Público
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Aprovar -->
<?php if ($podeAprovar): ?>
<div class="modal fade" id="modalAprovar" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="acao.php">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="acao" value="aprovar">
            <div class="modal-header"><h5 class="modal-title">Aprovar Orçamento</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p>Marcar orçamento como <strong>aprovado</strong>?</p>
                <p class="text-muted small">Após aprovado, você poderá convertê-lo em venda.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success">Aprovar</button>
            </div>
        </form>
    </div>
</div>
<div class="modal fade" id="modalRecusar" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="acao.php">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="acao" value="recusar">
            <div class="modal-header"><h5 class="modal-title">Recusar Orçamento</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label class="form-label">Motivo (opcional)</label>
                <textarea name="motivo" class="form-control" rows="3"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                <button type="submit" class="btn btn-danger">Recusar</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Modal Converter -->
<?php if ($podeConverter): ?>
<div class="modal fade" id="modalConverter" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="converter.php">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="modal-header"><h5 class="modal-title">Converter em Venda</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p>Este orçamento será convertido em venda concluída.</p>
                <div class="alert alert-info small mb-3">
                    <i class="fas fa-info-circle me-1"></i>
                    É necessário ter um caixa aberto. O estoque será baixado automaticamente.
                </div>
                <label class="form-label">Forma de Pagamento</label>
                <select name="forma_pagamento" class="form-select" required>
                    <option value="dinheiro">Dinheiro</option>
                    <option value="pix">PIX</option>
                    <option value="debito">Cartão Débito</option>
                    <option value="credito">Cartão Crédito</option>
                    <option value="boleto">Boleto</option>
                    <option value="outro">Outro</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success">Converter em Venda</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Modal Duplicar -->
<div class="modal fade" id="modalDuplicar" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="acao.php">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="acao" value="duplicar">
            <div class="modal-header"><h5 class="modal-title">Duplicar Orçamento</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p>Criar um novo orçamento copiando os itens e condições deste?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-info">Duplicar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Cancelar -->
<?php if ($podeCancelar && temPerfil('admin','gerente')): ?>
<div class="modal fade" id="modalCancelar" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="acao.php">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="acao" value="cancelar">
            <div class="modal-header"><h5 class="modal-title">Cancelar Orçamento</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="text-danger">Esta ação não pode ser desfeita.</p>
                <label class="form-label">Motivo</label>
                <textarea name="motivo" class="form-control" rows="2"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                <button type="submit" class="btn btn-dark">Cancelar Orçamento</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function copiarLink() {
    const input = document.getElementById('linkPublico');
    input.select();
    document.execCommand('copy');
    const btn = input.nextElementSibling;
    btn.innerHTML = '<i class="fas fa-check"></i>';
    setTimeout(() => btn.innerHTML = '<i class="fas fa-copy"></i>', 1500);
}

function marcarEnviado(canal) {
    fetch('<?= baseUrl('orcamentos/acao.php') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'csrf_token=<?= csrfToken() ?>&id=<?= $id ?>&acao=enviar&canal=' + encodeURIComponent(canal) + '&ajax=1'
    });
}

<?php if ($abrirEnviar): ?>
    new bootstrap.Modal(document.getElementById('modalEnviar')).show();
<?php endif; ?>
</script>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
