<?php
$pageTitle = 'Produto';
require_once __DIR__ . '/../../app/includes/auth.php';

$pdo = db();
$tid = tenantId();
$id = (int)($_GET['id'] ?? 0);
$produto = null;

if ($id > 0) {
    $produto = tenantFind('produtos', $id);
    if (!$produto) {
        flashError('Produto não encontrado.');
        redirect('produtos/');
    }
    $pageTitle = 'Editar Produto';
} else {
    $pageTitle = 'Novo Produto';
}

// Categorias para select
$stmtCat = $pdo->prepare("SELECT * FROM categorias WHERE tenant_id = ? AND ativo = 1 ORDER BY nome");
$stmtCat->execute([$tid]);
$categorias = $stmtCat->fetchAll();

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flashError('Token inválido.');
        redirect('produtos/form.php' . ($id ? "?id={$id}" : ''));
    }

    $data = [
        'codigo_barras'  => sanitize($_POST['codigo_barras'] ?? ''),
        'descricao'      => sanitize($_POST['descricao'] ?? ''),
        'descricao_curta' => sanitize($_POST['descricao_curta'] ?? ''),
        'unidade'        => sanitize($_POST['unidade'] ?? 'UN'),
        'categoria_id'   => (int)($_POST['categoria_id'] ?? 0) ?: null,
        'preco_custo'    => (float)($_POST['preco_custo'] ?? 0),
        'preco_venda'    => (float)($_POST['preco_venda'] ?? 0),
        'estoque_minimo' => (int)($_POST['estoque_minimo'] ?? 0),
        'ncm'            => sanitize($_POST['ncm'] ?? ''),
        'cfop'           => sanitize($_POST['cfop'] ?? ''),
        'cest'           => sanitize($_POST['cest'] ?? ''),
        'cst_csosn'      => sanitize($_POST['cst'] ?? ''),
        'ativo'          => isset($_POST['ativo']) ? 1 : 0,
    ];

    // Calcular margem
    if ($data['preco_custo'] > 0) {
        $data['margem_lucro'] = (($data['preco_venda'] - $data['preco_custo']) / $data['preco_custo']) * 100;
    } else {
        $data['margem_lucro'] = 0;
    }

    // Validações
    if (empty($data['descricao'])) {
        flashError('A descrição é obrigatória.');
        redirect('produtos/form.php' . ($id ? "?id={$id}" : ''));
    }

    if ($data['preco_venda'] <= 0) {
        flashError('O preço de venda deve ser maior que zero.');
        redirect('produtos/form.php' . ($id ? "?id={$id}" : ''));
    }

    // Upload de foto
    if (!empty($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $fotoPath = uploadFile($_FILES['foto'], 'produtos');
        if ($fotoPath) {
            $data['foto'] = $fotoPath;
        } else {
            flashError('Erro no upload da imagem. Verifique o formato (JPG, PNG ou WebP) e tamanho (máx. 5MB).');
            redirect('produtos/form.php' . ($id ? "?id={$id}" : ''));
        }
    }

    if ($id > 0) {
        tenantUpdate('produtos', $data, $id);
        flashSuccess('Produto atualizado com sucesso!');
    } else {
        // Estoque inicial apenas na criação
        $data['estoque_atual'] = (int)($_POST['estoque_atual'] ?? 0);
        tenantInsert('produtos', $data);
        flashSuccess('Produto cadastrado com sucesso!');
    }

    redirect('produtos/');
}

require __DIR__ . '/../../app/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">
        <i class="fas fa-barcode me-2"></i><?= $id ? 'Editar Produto' : 'Novo Produto' ?>
    </h4>
    <a href="<?= baseUrl('produtos/') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i>Voltar
    </a>
</div>

<form method="POST" enctype="multipart/form-data">
    <?= csrfField() ?>

    <div class="card shadow mb-3">
        <div class="card-header"><i class="fas fa-info-circle me-2"></i>Informações Básicas</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Código de Barras (EAN)</label>
                    <input type="text" name="codigo_barras" class="form-control" value="<?= e($produto['codigo_barras'] ?? '') ?>" maxlength="20">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Descrição <span class="text-danger">*</span></label>
                    <input type="text" name="descricao" class="form-control" value="<?= e($produto['descricao'] ?? '') ?>" required maxlength="255">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Unidade</label>
                    <select name="unidade" class="form-select">
                        <?php
                        $unidades = ['UN'=>'Unidade','KG'=>'Quilograma','LT'=>'Litro','MT'=>'Metro','CX'=>'Caixa','PC'=>'Pacote','PR'=>'Par'];
                        $unAtual = $produto['unidade'] ?? 'UN';
                        foreach ($unidades as $sigla => $nome):
                        ?>
                            <option value="<?= $sigla ?>" <?= $unAtual === $sigla ? 'selected' : '' ?>><?= $sigla ?> - <?= $nome ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Descrição Curta</label>
                    <input type="text" name="descricao_curta" class="form-control" value="<?= e($produto['descricao_curta'] ?? '') ?>" maxlength="100">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Categoria</label>
                    <select name="categoria_id" class="form-select">
                        <option value="">Sem categoria</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($produto['categoria_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>><?= e($cat['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Foto</label>
                    <input type="file" name="foto" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <?php if (!empty($produto['foto'])): ?>
                        <small class="text-muted">Atual: <?= e($produto['foto']) ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-3">
        <div class="card-header"><i class="fas fa-dollar-sign me-2"></i>Preços e Estoque</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Preço de Custo</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="number" name="preco_custo" class="form-control" step="0.01" min="0" value="<?= number_format((float)($produto['preco_custo'] ?? 0), 2, '.', '') ?>" id="precoCusto">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Preço de Venda <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="number" name="preco_venda" class="form-control" step="0.01" min="0.01" value="<?= number_format((float)($produto['preco_venda'] ?? 0), 2, '.', '') ?>" required id="precoVenda">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Margem (%)</label>
                    <input type="text" class="form-control" id="margemLucro" readonly>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Estoque Mínimo</label>
                    <input type="number" name="estoque_minimo" class="form-control" min="0" value="<?= (int)($produto['estoque_minimo'] ?? 0) ?>">
                </div>
                <?php if (!$id): ?>
                <div class="col-md-2">
                    <label class="form-label">Estoque Inicial</label>
                    <input type="number" name="estoque_atual" class="form-control" min="0" value="0">
                </div>
                <?php else: ?>
                <div class="col-md-2">
                    <label class="form-label">Estoque Atual</label>
                    <input type="number" class="form-control" value="<?= (int)($produto['estoque_atual'] ?? 0) ?>" readonly disabled>
                    <small class="text-muted">Use o módulo de estoque</small>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card shadow mb-3">
        <div class="card-header"><i class="fas fa-file-invoice me-2"></i>Dados Fiscais</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">NCM</label>
                    <input type="text" name="ncm" class="form-control" value="<?= e($produto['ncm'] ?? '') ?>" maxlength="10">
                </div>
                <div class="col-md-3">
                    <label class="form-label">CFOP</label>
                    <input type="text" name="cfop" class="form-control" value="<?= e($produto['cfop'] ?? '') ?>" maxlength="10">
                </div>
                <div class="col-md-3">
                    <label class="form-label">CEST</label>
                    <input type="text" name="cest" class="form-control" value="<?= e($produto['cest'] ?? '') ?>" maxlength="10">
                </div>
                <div class="col-md-3">
                    <label class="form-label">CST</label>
                    <input type="text" name="cst" class="form-control" value="<?= e($produto['cst_csosn'] ?? '') ?>" maxlength="10">
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-3">
        <div class="card-body">
            <div class="form-check form-switch">
                <input type="checkbox" name="ativo" class="form-check-input" id="ativo" <?= ($produto['ativo'] ?? 1) ? 'checked' : '' ?>>
                <label class="form-check-label" for="ativo">Produto Ativo</label>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Salvar</button>
        <a href="<?= baseUrl('produtos/') ?>" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

<script>
function calcularMargem() {
    const custo = parseFloat(document.getElementById('precoCusto').value) || 0;
    const venda = parseFloat(document.getElementById('precoVenda').value) || 0;
    const margem = custo > 0 ? ((venda - custo) / custo * 100).toFixed(2) : '0.00';
    document.getElementById('margemLucro').value = margem + '%';
}
document.getElementById('precoCusto').addEventListener('input', calcularMargem);
document.getElementById('precoVenda').addEventListener('input', calcularMargem);
calcularMargem();
</script>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
