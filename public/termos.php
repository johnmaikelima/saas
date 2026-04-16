<?php
$pageTitle = 'Termos de Uso e Política de Privacidade';
require_once __DIR__ . '/../app/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .terms-container {
            max-width: 900px;
            margin: 30px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 40px 50px;
        }
        .terms-container h1 { font-size: 1.8rem; color: #0f3460; font-weight: 700; }
        .terms-container h2 { font-size: 1.3rem; color: #1a1a2e; font-weight: 700; margin-top: 30px; }
        .terms-container h3 { font-size: 1.1rem; color: #333; font-weight: 600; margin-top: 20px; }
        .terms-container p, .terms-container li { font-size: 0.95rem; line-height: 1.7; color: #444; }
        .terms-container ul { padding-left: 20px; }
        .terms-container ul li { margin-bottom: 6px; }
        .version-info { background: #f0f4ff; border-radius: 8px; padding: 12px 16px; font-size: 0.85rem; color: #666; }
        @media (max-width: 768px) {
            .terms-container { padding: 20px; margin: 15px; }
        }
    </style>
</head>
<body>
<div class="terms-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-file-contract me-2"></i><?= e(APP_NAME) ?> — Termos de Uso</h1>
        <a href="/" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <div class="version-info mb-4">
        <strong>Versão:</strong> 1.0 &nbsp;|&nbsp; <strong>Última atualização:</strong> <?= date('d/m/Y') ?> &nbsp;|&nbsp;
        <strong>Razão social do fornecedor:</strong> Altustec LTDA — CNPJ: 27.111.744/0001-30
    </div>

    <p>Ao se cadastrar e utilizar a plataforma <strong><?= e(APP_NAME) ?></strong>, o CONTRATANTE declara ter lido, compreendido e aceito integralmente os presentes Termos de Uso e Política de Privacidade, em conformidade com a legislação brasileira vigente.</p>

    <!-- ===================== TERMOS DE USO ===================== -->
    <h2>1. Definições</h2>
    <ul>
        <li><strong>Plataforma:</strong> o sistema <?= e(APP_NAME) ?>, acessado via navegador web, que oferece funcionalidades de PDV (Ponto de Venda), gestão de estoque, cadastro de clientes e emissão de documentos fiscais eletrônicos.</li>
        <li><strong>Contratante:</strong> pessoa jurídica ou empresário individual que se cadastra na plataforma.</li>
        <li><strong>Usuário:</strong> qualquer pessoa física que acesse a plataforma em nome do Contratante.</li>
        <li><strong>Certificado Digital:</strong> certificado digital padrão ICP-Brasil, tipo A1 (arquivo .pfx/.p12), de titularidade e responsabilidade exclusiva do Contratante.</li>
        <li><strong>NFC-e:</strong> Nota Fiscal de Consumidor Eletrônica, documento fiscal digital emitido conforme legislação estadual e federal.</li>
    </ul>

    <h2>2. Objeto</h2>
    <p>A plataforma <?= e(APP_NAME) ?> oferece, na modalidade SaaS (Software como Serviço), ferramentas para gestão de vendas no varejo, incluindo:</p>
    <ul>
        <li>Ponto de Venda (PDV) com controle de caixa;</li>
        <li>Gestão de produtos e estoque;</li>
        <li>Cadastro de clientes;</li>
        <li>Emissão de NFC-e (Nota Fiscal de Consumidor Eletrônica);</li>
        <li>Relatórios de vendas e movimentações.</li>
    </ul>

    <h2>3. Cadastro e Responsabilidades do Contratante</h2>
    <p>3.1. O Contratante declara que todas as informações fornecidas no cadastro são verdadeiras, completas e atualizadas, responsabilizando-se civil e criminalmente pela veracidade dos dados.</p>
    <p>3.2. O Contratante é responsável por manter a confidencialidade de suas credenciais de acesso (login e senha), não devendo compartilhá-las com terceiros não autorizados.</p>
    <p>3.3. O Contratante é o único responsável pelas operações realizadas em sua conta, incluindo emissão de documentos fiscais, gestão de estoque e registros de vendas.</p>

    <h2>4. Certificado Digital e Documentos Fiscais</h2>
    <p>4.1. Para emissão de NFC-e, o Contratante deverá fornecer seu Certificado Digital A1 (arquivo .pfx ou .p12), emitido por Autoridade Certificadora credenciada pela ICP-Brasil, conforme estabelecido pela <strong>Medida Provisória nº 2.200-2/2001</strong> e regulamentações do ITI (Instituto Nacional de Tecnologia da Informação).</p>
    <p>4.2. <strong>O Certificado Digital é de titularidade e responsabilidade exclusiva do Contratante.</strong> A plataforma atua apenas como intermediária técnica para assinatura digital dos documentos fiscais e comunicação com a SEFAZ (Secretaria da Fazenda).</p>
    <p>4.3. O arquivo do Certificado Digital é armazenado de forma segura nos servidores da plataforma, em diretório protegido e inacessível via web. A senha do certificado é criptografada utilizando algoritmo AES-256-GCM antes de ser armazenada no banco de dados, e <strong>nunca é exibida ou transmitida em texto plano</strong>.</p>
    <p>4.4. O Contratante declara estar ciente de que:</p>
    <ul>
        <li>A emissão de NFC-e em seu nome tem validade jurídica e fiscal plena, equivalente a documento assinado digitalmente;</li>
        <li>É de sua exclusiva responsabilidade a correta configuração dos dados fiscais (CSC, série, CFOP, NCM, CEST, regime tributário, etc.);</li>
        <li>A plataforma não se responsabiliza por erros na emissão decorrentes de dados incorretos fornecidos pelo Contratante;</li>
        <li>O Contratante deve manter cópias de segurança (backup) de seus XMLs autorizados, conforme exigido pelo Ajuste SINIEF 07/2005;</li>
        <li>O prazo legal de guarda dos XMLs de NFC-e é de no mínimo <strong>5 (cinco) anos</strong>, conforme art. 173 e 174 do CTN (Código Tributário Nacional);</li>
        <li>O cancelamento do certificado digital ou sua expiração impossibilitará a emissão de novos documentos fiscais até a substituição por certificado válido.</li>
    </ul>
    <p>4.5. O Contratante pode remover seu Certificado Digital da plataforma a qualquer momento através das configurações do sistema. Após a remoção, o arquivo e a senha criptografada são excluídos permanentemente dos servidores.</p>

    <h2>5. Ambiente de Homologação e Produção</h2>
    <p>5.1. A plataforma permite emissão de NFC-e tanto em ambiente de <strong>Homologação</strong> (testes, sem validade fiscal) quanto em <strong>Produção</strong> (com validade fiscal plena).</p>
    <p>5.2. É responsabilidade do Contratante verificar em qual ambiente está operando antes de emitir documentos fiscais. Documentos emitidos em Produção possuem validade fiscal e tributária imediata.</p>

    <h2>6. Disponibilidade e Limitações</h2>
    <p>6.1. A plataforma é fornecida "como está" (<em>as is</em>), e será envidado o melhor esforço para manter a disponibilidade do serviço. Contudo, não se garante disponibilidade ininterrupta, podendo ocorrer indisponibilidades por manutenção, atualizações ou fatores fora do controle da plataforma.</p>
    <p>6.2. A emissão de NFC-e depende da disponibilidade dos servidores da SEFAZ de cada estado. Eventuais indisponibilidades da SEFAZ não são de responsabilidade da plataforma.</p>
    <p>6.3. Em caso de contingência (SEFAZ offline), o Contratante deverá adotar os procedimentos de contingência previstos na legislação fiscal de seu estado.</p>

    <h2>7. Planos, Pagamento e Cancelamento</h2>
    <p>7.1. O Contratante poderá usufruir de um período de teste gratuito de <strong>15 (quinze) dias</strong> a partir do cadastro.</p>
    <p>7.2. Após o período de teste, o acesso será vinculado à contratação de um plano pago, conforme as opções disponíveis no site.</p>
    <p>7.3. O cancelamento pode ser solicitado a qualquer momento. Após o cancelamento, os dados serão mantidos por <strong>90 (noventa) dias</strong> para eventual reativação, sendo excluídos permanentemente após esse prazo, salvo obrigações legais de guarda.</p>
    <p>7.4. XMLs de documentos fiscais emitidos serão mantidos pelo prazo legal mínimo, mesmo após o cancelamento da conta, para cumprimento de obrigações tributárias.</p>

    <!-- ===================== POLÍTICA DE PRIVACIDADE ===================== -->
    <h2>8. Política de Privacidade e Proteção de Dados (LGPD)</h2>
    <p>Em conformidade com a <strong>Lei nº 13.709/2018 (Lei Geral de Proteção de Dados Pessoais — LGPD)</strong>, informamos:</p>

    <h3>8.1. Dados Coletados</h3>
    <ul>
        <li><strong>Dados cadastrais:</strong> CNPJ, razão social, nome fantasia, endereço, telefone, e-mail, nome do responsável;</li>
        <li><strong>Dados de acesso:</strong> login, senha (armazenada com hash bcrypt, nunca em texto plano), IP de acesso, data/hora de login;</li>
        <li><strong>Dados fiscais:</strong> certificado digital A1 (arquivo criptografado), dados de configuração fiscal (CSC, série, regime tributário);</li>
        <li><strong>Dados operacionais:</strong> registros de vendas, produtos, clientes, movimentações de caixa;</li>
        <li><strong>Dados técnicos:</strong> endereço IP, navegador utilizado (User-Agent), logs de auditoria.</li>
    </ul>

    <h3>8.2. Finalidade do Tratamento</h3>
    <ul>
        <li>Execução do contrato de prestação do serviço SaaS (art. 7º, V, LGPD);</li>
        <li>Cumprimento de obrigação legal ou regulatória, especialmente tributária e fiscal (art. 7º, II, LGPD);</li>
        <li>Segurança e prevenção a fraudes (art. 7º, IX, LGPD);</li>
        <li>Comunicações relacionadas ao serviço contratado.</li>
    </ul>

    <h3>8.3. Compartilhamento de Dados</h3>
    <p>Os dados poderão ser compartilhados com:</p>
    <ul>
        <li><strong>SEFAZ (Secretaria da Fazenda):</strong> exclusivamente para transmissão de documentos fiscais eletrônicos, conforme exigência legal;</li>
        <li><strong>Processador de pagamento:</strong> dados mínimos necessários para cobrança do plano contratado;</li>
        <li><strong>Autoridades competentes:</strong> quando exigido por ordem judicial ou determinação legal.</li>
    </ul>
    <p>A plataforma <strong>não comercializa, não cede e não compartilha</strong> dados pessoais ou comerciais do Contratante com terceiros para fins de marketing, publicidade ou qualquer outra finalidade não descrita nestes termos.</p>

    <h3>8.4. Segurança dos Dados</h3>
    <p>A plataforma adota as seguintes medidas técnicas de segurança:</p>
    <ul>
        <li>Comunicação via HTTPS (TLS/SSL) em todas as páginas;</li>
        <li>Senhas armazenadas com hash bcrypt (custo 12), sem possibilidade de recuperação em texto plano;</li>
        <li>Certificados digitais armazenados em diretório protegido, fora do diretório público;</li>
        <li>Senha do certificado criptografada com AES-256-GCM;</li>
        <li>Proteção contra CSRF (Cross-Site Request Forgery) em todos os formulários;</li>
        <li>Isolamento de dados por tenant (empresa) — cada empresa acessa exclusivamente seus próprios dados;</li>
        <li>Logs de auditoria para operações sensíveis;</li>
        <li>Proteção contra ataques de força bruta (rate limiting);</li>
        <li>Sessões com fingerprint, cookies HTTP-only e SameSite=Strict.</li>
    </ul>

    <h3>8.5. Direitos do Titular (art. 18, LGPD)</h3>
    <p>O Contratante e seus usuários podem exercer os seguintes direitos a qualquer momento:</p>
    <ul>
        <li>Confirmação da existência de tratamento de dados;</li>
        <li>Acesso aos dados pessoais armazenados;</li>
        <li>Correção de dados incompletos, inexatos ou desatualizados;</li>
        <li>Anonimização, bloqueio ou eliminação de dados desnecessários;</li>
        <li>Portabilidade dos dados (exportação em formato estruturado);</li>
        <li>Eliminação dos dados pessoais tratados com consentimento;</li>
        <li>Revogação do consentimento.</li>
    </ul>
    <p>Para exercer esses direitos, o Contratante deve entrar em contato através do e-mail ou canal de suporte indicado na plataforma.</p>

    <h3>8.6. Retenção de Dados</h3>
    <ul>
        <li><strong>Dados fiscais (XMLs, NFC-e):</strong> mínimo de 5 anos, conforme legislação tributária (CTN, arts. 173 e 174);</li>
        <li><strong>Registros de acesso:</strong> 6 meses, conforme Marco Civil da Internet (Lei nº 12.965/2014, art. 15);</li>
        <li><strong>Dados cadastrais:</strong> durante a vigência do contrato + 90 dias após cancelamento;</li>
        <li><strong>Dados para obrigações legais:</strong> pelo prazo exigido pela legislação aplicável.</li>
    </ul>

    <h2>9. Propriedade Intelectual</h2>
    <p>9.1. Todo o software, código-fonte, design, marca e conteúdo da plataforma <?= e(APP_NAME) ?> são de propriedade exclusiva do fornecedor, protegidos pela <strong>Lei nº 9.609/1998</strong> (Lei do Software) e <strong>Lei nº 9.610/1998</strong> (Lei de Direitos Autorais).</p>
    <p>9.2. O Contratante recebe apenas uma licença limitada, não exclusiva e intransferível para uso da plataforma durante a vigência da contratação.</p>

    <h2>10. Limitação de Responsabilidade</h2>
    <p>10.1. A plataforma não se responsabiliza por:</p>
    <ul>
        <li>Prejuízos decorrentes de uso indevido do Certificado Digital pelo Contratante;</li>
        <li>Erros fiscais causados por configuração incorreta dos dados tributários;</li>
        <li>Indisponibilidade dos serviços da SEFAZ ou de terceiros;</li>
        <li>Perda de dados decorrente de ação do Contratante (exclusão de conta, remoção de arquivos);</li>
        <li>Danos indiretos, incidentais ou consequenciais.</li>
    </ul>
    <p>10.2. Em nenhuma hipótese a responsabilidade total da plataforma excederá o valor pago pelo Contratante nos últimos 12 (doze) meses de contratação.</p>

    <h2>11. Disposições Gerais</h2>
    <p>11.1. Estes termos são regidos pela legislação da República Federativa do Brasil.</p>
    <p>11.2. Fica eleito o foro da comarca do domicílio do fornecedor para dirimir quaisquer controvérsias, com renúncia a qualquer outro, por mais privilegiado que seja.</p>
    <p>11.3. A plataforma se reserva o direito de alterar estes Termos de Uso a qualquer momento, notificando o Contratante por e-mail ou aviso na plataforma com antecedência mínima de <strong>30 (trinta) dias</strong>.</p>
    <p>11.4. A continuidade do uso após alterações implica aceitação dos novos termos.</p>
    <p>11.5. Se qualquer disposição destes termos for considerada inválida ou inexequível, as demais disposições permanecerão em pleno vigor e efeito.</p>

    <h2>12. Legislação Aplicável</h2>
    <p>Estes Termos estão em conformidade com:</p>
    <ul>
        <li><strong>Lei nº 13.709/2018</strong> — Lei Geral de Proteção de Dados (LGPD);</li>
        <li><strong>Lei nº 12.965/2014</strong> — Marco Civil da Internet;</li>
        <li><strong>Lei nº 8.078/1990</strong> — Código de Defesa do Consumidor;</li>
        <li><strong>Medida Provisória nº 2.200-2/2001</strong> — ICP-Brasil e validade jurídica de documentos eletrônicos;</li>
        <li><strong>Ajuste SINIEF 07/2005</strong> — NF-e e NFC-e;</li>
        <li><strong>Lei nº 5.172/1966</strong> — Código Tributário Nacional;</li>
        <li><strong>Lei nº 9.609/1998</strong> — Proteção de programa de computador;</li>
        <li><strong>Lei nº 9.610/1998</strong> — Direitos autorais.</li>
    </ul>

    <hr class="my-4">
    <p class="text-center text-muted" style="font-size: 0.85rem;">
        <strong>Altustec LTDA</strong> — CNPJ: 27.111.744/0001-30<br>
        Ao clicar em "Aceito os Termos de Uso" durante o cadastro, o Contratante manifesta sua concordância livre, informada e inequívoca com todas as condições aqui estabelecidas.
    </p>
</div>
</body>
</html>
