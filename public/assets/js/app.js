/**
 * Balcão PDV SaaS - JavaScript Global
 */

// Mascaras
function maskCpfCnpj(el) {
    el.addEventListener('input', function() {
        let v = this.value.replace(/\D/g, '');
        if (v.length <= 11) {
            v = v.replace(/(\d{3})(\d)/, '$1.$2');
            v = v.replace(/(\d{3})(\d)/, '$1.$2');
            v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        } else {
            v = v.replace(/^(\d{2})(\d)/, '$1.$2');
            v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
            v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
            v = v.replace(/(\d{4})(\d)/, '$1-$2');
        }
        this.value = v;
    });
}

function maskTelefone(el) {
    el.addEventListener('input', function() {
        let v = this.value.replace(/\D/g, '');
        if (v.length > 10) {
            v = v.replace(/^(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
        } else {
            v = v.replace(/^(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
        }
        this.value = v;
    });
}

function maskCep(el) {
    el.addEventListener('input', function() {
        let v = this.value.replace(/\D/g, '');
        v = v.replace(/^(\d{5})(\d)/, '$1-$2');
        this.value = v;
    });
}

// Busca CEP via ViaCEP
function buscarCep(cep, callback) {
    cep = cep.replace(/\D/g, '');
    if (cep.length !== 8) return;

    fetch(`https://viacep.com.br/ws/${cep}/json/`)
        .then(r => r.json())
        .then(data => {
            if (!data.erro) {
                callback({
                    endereco: data.logradouro,
                    bairro: data.bairro,
                    cidade: data.localidade,
                    estado: data.uf
                });
            }
        })
        .catch(() => {});
}

// Consulta CNPJ via ReceitaWS
function consultarCnpj(cnpj, callback) {
    cnpj = cnpj.replace(/\D/g, '');
    if (cnpj.length !== 14) return;

    fetch(`https://receitaws.com.br/v1/cnpj/${cnpj}`)
        .then(r => r.json())
        .then(data => {
            if (data.status !== 'ERROR') {
                callback(data);
            }
        })
        .catch(() => {});
}

// Formatar moeda
function formatMoney(value) {
    return 'R$ ' + parseFloat(value).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

// Confirmar exclusao
function confirmarExclusao(url, msg) {
    if (confirm(msg || 'Tem certeza que deseja excluir?')) {
        window.location.href = url;
    }
}

// Auto-aplicar mascaras
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-mask="cpfcnpj"]').forEach(maskCpfCnpj);
    document.querySelectorAll('[data-mask="telefone"]').forEach(maskTelefone);
    document.querySelectorAll('[data-mask="cep"]').forEach(el => {
        maskCep(el);
        el.addEventListener('blur', function() {
            buscarCep(this.value, function(data) {
                const form = el.closest('form');
                if (form) {
                    if (form.querySelector('[name="endereco"]')) form.querySelector('[name="endereco"]').value = data.endereco;
                    if (form.querySelector('[name="bairro"]')) form.querySelector('[name="bairro"]').value = data.bairro;
                    if (form.querySelector('[name="cidade"]')) form.querySelector('[name="cidade"]').value = data.cidade;
                    if (form.querySelector('[name="estado"]')) form.querySelector('[name="estado"]').value = data.estado;
                }
            });
        });
    });

    // Auto-dismiss alerts
    document.querySelectorAll('.alert-dismissible').forEach(function(el) {
        setTimeout(() => {
            const alert = bootstrap.Alert.getOrCreateInstance(el);
            alert.close();
        }, 5000);
    });

    // Calculo margem automatica
    const precoCusto = document.getElementById('preco_custo');
    const precoVenda = document.getElementById('preco_venda');
    const margemLucro = document.getElementById('margem_lucro');

    if (precoCusto && precoVenda && margemLucro) {
        function calcMargem() {
            const custo = parseFloat(precoCusto.value) || 0;
            const venda = parseFloat(precoVenda.value) || 0;
            if (custo > 0) {
                margemLucro.value = (((venda - custo) / custo) * 100).toFixed(2);
            }
        }
        precoCusto.addEventListener('input', calcMargem);
        precoVenda.addEventListener('input', calcMargem);
    }
});
