<?php
/**
 * Modal reutilizavel para cadastro rapido de cliente via AJAX.
 * Uso: incluir este arquivo onde o modal deve aparecer e disparar com
 *   data-bs-toggle="modal" data-bs-target="#modalNovoCliente"
 *
 * Apos salvar dispara window.dispatchEvent(new CustomEvent('cliente:criado', { detail: cliente }))
 */
$estadosModal = ['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'];
?>
<div class="modal fade" id="modalNovoCliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Novo Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNovoCliente">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <div class="modal-body">
                    <div id="modalClienteErro" class="alert alert-danger py-2 d-none"></div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Nome <span class="text-danger">*</span></label>
                            <input type="text" name="nome" class="form-control form-control-sm" required maxlength="255">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">CPF/CNPJ</label>
                            <input type="text" name="cpf_cnpj" class="form-control form-control-sm" maxlength="18" id="mc_cpfCnpj">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">RG/IE</label>
                            <input type="text" name="rg_ie" class="form-control form-control-sm" maxlength="20">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Telefone</label>
                            <input type="text" name="telefone" class="form-control form-control-sm" maxlength="15" id="mc_tel">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Celular</label>
                            <input type="text" name="celular" class="form-control form-control-sm" maxlength="15" id="mc_cel">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Email</label>
                            <input type="email" name="email" class="form-control form-control-sm" maxlength="255">
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="row g-2">
                        <div class="col-md-2">
                            <label class="form-label small mb-1">CEP</label>
                            <input type="text" name="cep" class="form-control form-control-sm" maxlength="9" id="mc_cep">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Endereço</label>
                            <input type="text" name="endereco" class="form-control form-control-sm" maxlength="255">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Número</label>
                            <input type="text" name="numero" class="form-control form-control-sm" maxlength="10">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Complemento</label>
                            <input type="text" name="complemento" class="form-control form-control-sm" maxlength="100">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small mb-1">Bairro</label>
                            <input type="text" name="bairro" class="form-control form-control-sm" maxlength="100">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small mb-1">Cidade</label>
                            <input type="text" name="cidade" class="form-control form-control-sm" maxlength="100">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Estado</label>
                            <select name="estado" class="form-select form-select-sm">
                                <option value="">--</option>
                                <?php foreach ($estadosModal as $uf): ?>
                                    <option value="<?= $uf ?>"><?= $uf ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small mb-1">Observações</label>
                            <textarea name="observacoes" class="form-control form-control-sm" rows="2" maxlength="1000"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnSalvarCliente">
                        <i class="fas fa-save me-1"></i>Salvar Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    const form = document.getElementById('formNovoCliente');
    const erro = document.getElementById('modalClienteErro');
    const btn = document.getElementById('btnSalvarCliente');

    // Mascaras simples
    function maskCpfCnpj(v) {
        v = v.replace(/\D/g, '').slice(0, 14);
        if (v.length <= 11) {
            return v.replace(/^(\d{3})(\d{3})(\d{3})(\d{1,2})$/, '$1.$2.$3-$4')
                    .replace(/^(\d{3})(\d{3})(\d{1,3})$/, '$1.$2.$3')
                    .replace(/^(\d{3})(\d{1,3})$/, '$1.$2');
        }
        return v.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{1,2})$/, '$1.$2.$3/$4-$5')
                .replace(/^(\d{2})(\d{3})(\d{3})(\d{1,4})$/, '$1.$2.$3/$4');
    }
    function maskTel(v) {
        v = v.replace(/\D/g, '').slice(0, 11);
        if (v.length > 6) return v.replace(/^(\d{2})(\d{4,5})(\d{4})$/, '($1) $2-$3');
        if (v.length > 2) return v.replace(/^(\d{2})(\d+)$/, '($1) $2');
        return v;
    }
    function maskCep(v) {
        v = v.replace(/\D/g, '').slice(0, 8);
        return v.replace(/^(\d{5})(\d{1,3})$/, '$1-$2');
    }
    const cpf = document.getElementById('mc_cpfCnpj');
    const tel = document.getElementById('mc_tel');
    const cel = document.getElementById('mc_cel');
    const cep = document.getElementById('mc_cep');
    cpf && cpf.addEventListener('input', e => e.target.value = maskCpfCnpj(e.target.value));
    tel && tel.addEventListener('input', e => e.target.value = maskTel(e.target.value));
    cel && cel.addEventListener('input', e => e.target.value = maskTel(e.target.value));
    cep && cep.addEventListener('input', e => e.target.value = maskCep(e.target.value));

    // Buscar endereco via ViaCEP
    cep && cep.addEventListener('blur', async () => {
        const num = cep.value.replace(/\D/g, '');
        if (num.length !== 8) return;
        try {
            const res = await fetch('https://viacep.com.br/ws/' + num + '/json/');
            const d = await res.json();
            if (d.erro) return;
            if (d.logradouro) form.endereco.value = d.logradouro;
            if (d.bairro) form.bairro.value = d.bairro;
            if (d.localidade) form.cidade.value = d.localidade;
            if (d.uf) form.estado.value = d.uf;
        } catch (_) {}
    });

    form.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        erro.classList.add('d-none');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvando...';

        const fd = new FormData(form);
        try {
            const res = await fetch('<?= baseUrl('clientes/salvar_ajax.php') ?>', {
                method: 'POST',
                headers: {'X-CSRF-Token': '<?= csrfToken() ?>'},
                body: fd
            });
            const data = await res.json();
            if (data.ok) {
                bootstrap.Modal.getInstance(document.getElementById('modalNovoCliente')).hide();
                form.reset();
                window.dispatchEvent(new CustomEvent('cliente:criado', { detail: data }));
            } else {
                erro.textContent = data.msg || 'Erro ao salvar.';
                erro.classList.remove('d-none');
            }
        } catch (e) {
            erro.textContent = 'Erro de rede.';
            erro.classList.remove('d-none');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-1"></i>Salvar Cliente';
        }
    });
})();
</script>
