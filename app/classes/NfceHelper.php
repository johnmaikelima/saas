<?php
/**
 * NfceHelper - Emissão de NFC-e via sped-nfe (multi-tenant SaaS)
 * Comunica direto com a SEFAZ sem ACBrMonitor
 */

use NFePHP\NFe\Make;
use NFePHP\NFe\Tools;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Complements;
use NFePHP\Common\Certificate;
use NFePHP\DA\NFe\Danfce;

class NfceHelper
{
    private int $tenantId;
    private ?Tools $tools = null;

    public function __construct(int $tenantId)
    {
        $this->tenantId = $tenantId;
    }

    /**
     * Monta a configuração JSON para o sped-nfe Tools
     */
    private function getConfigJson(array $empresa): string
    {
        $ambiente = (int) getConfig('nfce_ambiente', '2');
        $csc = getConfig('nfce_csc', '');
        $cscId = getConfig('nfce_csc_id', '');

        $uf = $empresa['estado'] ?? 'SP';
        $codUF = $this->getCodigoUF($uf);

        return json_encode([
            'atualizacao' => date('Y-m-d H:i:s'),
            'tpAmb'       => $ambiente,
            'razaosocial'  => $empresa['razao_social'] ?? '',
            'fantasia'     => $empresa['nome_fantasia'] ?? '',
            'siglaUF'      => $uf,
            'cnpj'         => limparCpfCnpj($empresa['cnpj'] ?? ''),
            'schemes'      => 'PL_009_V4',
            'versao'       => '4.00',
            'tokenIBPT'    => '',
            'CSC'          => $csc,
            'CSCid'        => $cscId,
            'proxyConf'    => [
                'proxyIp'   => '',
                'proxyPort' => '',
                'proxyUser' => '',
                'proxyPass' => '',
            ],
        ]);
    }

    /**
     * Inicializa o Tools do sped-nfe com certificado do tenant
     */
    private function initTools(?array $empresa = null): Tools
    {
        if ($this->tools) {
            return $this->tools;
        }

        if (!$empresa) {
            $empresa = $this->getEmpresaTenant();
        }

        $configJson = $this->getConfigJson($empresa);

        // Certificado por tenant
        $certDir = STORAGE_PATH . '/certificados/' . $this->tenantId;
        $certPath = $certDir . '/certificado.pfx';
        $certSenhaEncrypted = getConfig('certificado_senha', '');
        $certSenha = !empty($certSenhaEncrypted) ? decryptValue($certSenhaEncrypted) : '';

        if (!file_exists($certPath)) {
            throw new \Exception('Certificado digital nao encontrado. Configure em Configuracoes > Fiscal.');
        }

        $certContent = file_get_contents($certPath);
        $certificate = Certificate::readPfx($certContent, $certSenha);

        $this->tools = new Tools($configJson, $certificate);
        $this->tools->model('65'); // NFC-e

        return $this->tools;
    }

    /**
     * Busca dados do tenant (empresa emitente)
     */
    private function getEmpresaTenant(): array
    {
        $stmt = db()->prepare("SELECT * FROM tenants WHERE id = ?");
        $stmt->execute([$this->tenantId]);
        $empresa = $stmt->fetch();
        if (!$empresa) {
            throw new \Exception('Tenant nao encontrado');
        }
        return $empresa;
    }

    /**
     * Emitir NFC-e - monta XML, assina e envia para SEFAZ
     */
    public function emitir(array $venda, array $itens): array
    {
        $empresa = $this->getEmpresaTenant();
        $numero = (int) getConfig('nfce_numero', '1');
        $serie = (int) getConfig('nfce_serie', '1');
        $ambiente = (int) getConfig('nfce_ambiente', '2');

        // Registrar no banco como pendente
        $nfceId = tenantInsert('nfce', [
            'venda_id' => $venda['id'],
            'numero'   => $numero,
            'serie'    => $serie,
            'ambiente' => $ambiente,
            'status'   => 'pendente',
        ]);

        try {
            $tools = $this->initTools($empresa);

            // Montar XML da NFC-e
            $xml = $this->montarXml($venda, $itens, $empresa, $numero, $serie, $ambiente);

            // Salvar XML de envio
            $this->updateNfce($nfceId, 'pendente', '', '', $xml, '');

            // Assinar XML
            $xmlAssinado = $tools->signNFe($xml);

            // Enviar para SEFAZ (síncrono)
            $idLote = str_pad($numero, 15, '0', STR_PAD_LEFT);
            $resp = $tools->sefazEnviaLote([$xmlAssinado], $idLote, 1);

            $this->log($nfceId, 'sefazEnviaLote', $xmlAssinado, $resp, true);

            // Processar resposta
            $st = new Standardize($resp);
            $std = $st->toStd();

            if ($std->cStat != 104) {
                $msg = "SEFAZ: [{$std->cStat}] " . ($std->xMotivo ?? 'Erro desconhecido');
                $this->updateNfce($nfceId, 'erro', '', '', $xmlAssinado, $msg);
                return ['ok' => false, 'msg' => $msg, 'nfce_id' => $nfceId];
            }

            $protNFe = $std->protNFe ?? null;
            if (!$protNFe) {
                $this->updateNfce($nfceId, 'erro', '', '', $xmlAssinado, 'Sem protocolo na resposta');
                return ['ok' => false, 'msg' => 'SEFAZ nao retornou protocolo', 'nfce_id' => $nfceId];
            }

            $infProt = $protNFe->infProt;
            $cStat = $infProt->cStat;
            $xMotivo = $infProt->xMotivo ?? '';
            $chave = $infProt->chNFe ?? '';
            $proto = $infProt->nProt ?? '';

            if ($cStat != 100) {
                $msg = "Rejeitada: [{$cStat}] {$xMotivo}";
                $this->updateNfce($nfceId, 'erro', $chave, '', $xmlAssinado, $msg);
                return ['ok' => false, 'msg' => $msg, 'nfce_id' => $nfceId];
            }

            // AUTORIZADA - complementar XML com protocolo
            $xmlAutorizado = Complements::toAuthorize($xmlAssinado, $resp);

            // Salvar XML em arquivo
            $xmlDir = STORAGE_PATH . '/xml/autorizadas/' . $this->tenantId . '/' . date('Y/m');
            if (!is_dir($xmlDir)) {
                mkdir($xmlDir, 0755, true);
            }
            file_put_contents($xmlDir . '/' . $chave . '-nfce.xml', $xmlAutorizado);

            $this->updateNfce($nfceId, 'autorizada', $chave, $proto, $xmlAutorizado, '');

            // Vincular NFC-e à venda
            $pdo = db();
            $pdo->prepare("UPDATE vendas SET nfce_id = ? WHERE id = ? AND tenant_id = ?")
                ->execute([$nfceId, $venda['id'], $this->tenantId]);

            // Incrementar número
            setConfig('nfce_numero', (string)($numero + 1), 'fiscal');

            return [
                'ok'        => true,
                'msg'       => 'NFC-e emitida com sucesso!',
                'nfce_id'   => $nfceId,
                'chave'     => $chave,
                'protocolo' => $proto,
            ];

        } catch (\Exception $e) {
            $msg = 'Erro: ' . $e->getMessage();
            $this->updateNfce($nfceId, 'erro', '', '', '', $msg);
            $this->log($nfceId, 'emitir_erro', '', $msg, false);
            return ['ok' => false, 'msg' => $msg, 'nfce_id' => $nfceId];
        }
    }

    /**
     * Montar XML da NFC-e usando sped-nfe Make
     */
    private function montarXml(array $venda, array $itens, array $empresa, int $numero, int $serie, int $ambiente): string
    {
        $nfe = new Make();

        $cnpjEmitente = limparCpfCnpj($empresa['cnpj'] ?? '');
        $uf = $empresa['estado'] ?? 'SP';
        $codUF = $this->getCodigoUF($uf);
        $codMun = $empresa['codigo_ibge'] ?? $this->getCodigoMunicipio($empresa['cidade'] ?? '', $uf);
        $crt = (int)($empresa['regime_tributario'] ?? 1);
        $dhEmi = date('Y-m-d\TH:i:sP');
        $cNF = str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);

        // infNFe
        $std = new \stdClass();
        $std->versao = '4.00';
        $std->Id = null;
        $std->pk_nItem = null;
        $nfe->taginfNFe($std);

        // Identificação
        $std = new \stdClass();
        $std->cUF = $codUF;
        $std->cNF = $cNF;
        $std->natOp = 'VENDA';
        $std->mod = 65;
        $std->serie = $serie;
        $std->nNF = $numero;
        $std->dhEmi = $dhEmi;
        $std->dhSaiEnt = null;
        $std->tpNF = 1;
        $std->idDest = 1;
        $std->cMunFG = $codMun;
        $std->tpImp = 4;
        $std->tpEmis = 1;
        $std->cDV = 0;
        $std->tpAmb = $ambiente;
        $std->finNFe = 1;
        $std->indFinal = 1;
        $std->indPres = 1;
        $std->indIntermed = 0;
        $std->procEmi = 0;
        $std->verProc = 'Kaixa SaaS 1.0';
        $nfe->tagide($std);

        // Emitente
        $std = new \stdClass();
        $std->xNome = $empresa['razao_social'] ?? '';
        $std->xFant = $empresa['nome_fantasia'] ?? '';
        $ieLimpa = preg_replace('/\D/', '', $empresa['ie'] ?? '');
        $std->IE = !empty($ieLimpa) ? $ieLimpa : 'ISENTO';
        $std->IEST = null;
        $std->IM = $empresa['im'] ?? null;
        $std->CNAE = null;
        $std->CRT = $crt;
        $std->CNPJ = $cnpjEmitente;
        $std->CPF = null;
        $nfe->tagemit($std);

        // Endereço emitente
        $std = new \stdClass();
        $std->xLgr = $empresa['endereco'] ?? '';
        $std->nro = $empresa['numero'] ?? 'SN';
        $std->xCpl = $empresa['complemento'] ?? null;
        $std->xBairro = $empresa['bairro'] ?? '';
        $std->cMun = $codMun;
        $std->xMun = $empresa['cidade'] ?? '';
        $std->UF = $uf;
        $std->CEP = preg_replace('/\D/', '', $empresa['cep'] ?? '');
        $std->cPais = '1058';
        $std->xPais = 'BRASIL';
        $std->fone = preg_replace('/\D/', '', $empresa['telefone'] ?? '') ?: null;
        $nfe->tagenderEmit($std);

        // Destinatário (opcional na NFC-e)
        $cpfNota = $venda['cpf_cnpj_nota'] ?? '';
        if (!empty($cpfNota)) {
            $doc = limparCpfCnpj($cpfNota);
            $std = new \stdClass();
            $std->xNome = null;
            $std->indIEDest = 9;
            $std->IE = null;
            $std->ISUF = null;
            $std->IM = null;
            $std->email = null;
            if (strlen($doc) === 14) {
                $std->CNPJ = $doc;
                $std->CPF = null;
            } else {
                $std->CPF = $doc;
                $std->CNPJ = null;
            }
            $std->idEstrangeiro = null;
            $nfe->tagdest($std);
        }

        // Itens
        $totalProd = 0;
        $totalDesc = 0;

        foreach ($itens as $i => $item) {
            $nItem = $i + 1;
            $vProd = round($item['quantidade'] * $item['valor_unitario'], 2);
            $vDesc = round($item['desconto'] ?? 0, 2);
            $totalProd += $vProd;
            $totalDesc += $vDesc;

            $codBarras = !empty($item['codigo_barras']) ? $item['codigo_barras'] : 'SEM GTIN';

            // Produto
            $std = new \stdClass();
            $std->item = $nItem;
            $std->cProd = $item['produto_id'];
            $std->cEAN = $codBarras;
            $std->cBarra = null;
            $std->xProd = $ambiente == 2
                ? 'NOTA FISCAL EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL'
                : substr($item['descricao'], 0, 120);
            $std->NCM = $item['ncm'] ?? '00000000';
            $std->cBenef = null;
            $std->EXTIPI = null;
            $std->CFOP = $item['cfop'] ?? '5102';
            $std->uCom = $item['unidade'] ?? 'UN';
            $std->qCom = number_format($item['quantidade'], 4, '.', '');
            $std->vUnCom = number_format($item['valor_unitario'], 4, '.', '');
            $std->vProd = number_format($vProd, 2, '.', '');
            $std->cEANTrib = $codBarras;
            $std->cBarraTrib = null;
            $std->uTrib = $item['unidade'] ?? 'UN';
            $std->qTrib = number_format($item['quantidade'], 4, '.', '');
            $std->vUnTrib = number_format($item['valor_unitario'], 4, '.', '');
            $std->vFrete = null;
            $std->vSeg = null;
            $std->vDesc = $vDesc > 0 ? number_format($vDesc, 2, '.', '') : null;
            $std->vOutro = null;
            $std->indTot = 1;
            if (!empty($item['cest'])) {
                $std->CEST = $item['cest'];
            }
            $nfe->tagprod($std);

            // ICMS
            if ($crt == 1 || $crt == 2) {
                // Simples Nacional - CSOSN
                $std = new \stdClass();
                $std->item = $nItem;
                $std->orig = 0;
                $std->CSOSN = $item['cst_csosn'] ?? '102';
                $std->modBC = null;
                $std->vBC = null;
                $std->pRedBC = null;
                $std->pICMS = null;
                $std->vICMS = null;
                $std->pCredSN = null;
                $std->vCredICMSSN = null;
                $std->modBCST = null;
                $std->pMVAST = null;
                $std->pRedBCST = null;
                $std->vBCST = null;
                $std->pICMSST = null;
                $std->vICMSST = null;
                $std->vBCFCPST = null;
                $std->pFCPST = null;
                $std->vFCPST = null;
                $nfe->tagICMSSN($std);
            } else {
                // Regime Normal - CST
                $std = new \stdClass();
                $std->item = $nItem;
                $std->orig = 0;
                $std->CST = $item['cst_csosn'] ?? '00';
                $std->modBC = 3;
                $std->vBC = 0.00;
                $std->pICMS = 0.00;
                $std->vICMS = 0.00;
                $nfe->tagICMS($std);
            }

            // PIS
            $std = new \stdClass();
            $std->item = $nItem;
            $std->CST = '99';
            $std->vBC = 0.00;
            $std->pPIS = 0.00;
            $std->vPIS = 0.00;
            $nfe->tagPIS($std);

            // COFINS
            $std = new \stdClass();
            $std->item = $nItem;
            $std->CST = '99';
            $std->vBC = 0.00;
            $std->pCOFINS = 0.00;
            $std->vCOFINS = 0.00;
            $nfe->tagCOFINS($std);
        }

        // Totais
        $std = new \stdClass();
        $std->vBC = 0.00;
        $std->vICMS = 0.00;
        $std->vICMSDeson = 0.00;
        $std->vFCPUFDest = null;
        $std->vICMSUFDest = null;
        $std->vICMSUFRemet = null;
        $std->vFCP = 0.00;
        $std->vBCST = 0.00;
        $std->vST = 0.00;
        $std->vFCPST = 0.00;
        $std->vFCPSTRet = 0.00;
        $std->vProd = number_format($totalProd, 2, '.', '');
        $std->vFrete = 0.00;
        $std->vSeg = 0.00;
        $std->vDesc = $totalDesc > 0 ? number_format($totalDesc, 2, '.', '') : 0.00;
        $std->vII = 0.00;
        $std->vIPI = 0.00;
        $std->vIPIDevol = 0.00;
        $std->vPIS = 0.00;
        $std->vCOFINS = 0.00;
        $std->vOutro = 0.00;
        $std->vNF = number_format($venda['total'], 2, '.', '');
        $std->vTotTrib = null;
        $nfe->tagICMSTot($std);

        // Transporte
        $std = new \stdClass();
        $std->modFrete = 9;
        $nfe->tagtransp($std);

        // Pagamentos
        $std = new \stdClass();
        $std->vTroco = ($venda['troco'] ?? 0) > 0 ? number_format($venda['troco'], 2, '.', '') : null;
        $nfe->tagpag($std);

        if (!empty($venda['pagamentos'])) {
            foreach ($venda['pagamentos'] as $pag) {
                $tPag = match ($pag['forma']) {
                    'dinheiro' => '01',
                    'debito'   => '04',
                    'credito'  => '03',
                    'pix'      => '17',
                    default    => '99',
                };

                $std = new \stdClass();
                $std->indPag = ($pag['forma'] === 'credito') ? 1 : 0;
                $std->tPag = $tPag;
                $std->vPag = number_format($pag['valor'], 2, '.', '');

                if ($tPag !== '01') {
                    $std->tpIntegra = 2;
                    $std->tBand = '99';
                }

                $nfe->tagdetPag($std);
            }
        }

        // Info adicional
        $std = new \stdClass();
        $std->infAdFisco = null;
        $std->infCpl = null;
        $nfe->taginfAdic($std);

        $xml = $nfe->getXML();
        if (!$xml) {
            $erros = $nfe->getErrors();
            throw new \Exception('Erro ao montar XML: ' . implode(', ', $erros));
        }

        return $xml;
    }

    /**
     * Cancelar NFC-e autorizada
     */
    public function cancelar(int $nfceId, string $justificativa): array
    {
        $nfce = tenantFind('nfce', $nfceId);
        if (!$nfce || $nfce['status'] !== 'autorizada') {
            return ['ok' => false, 'msg' => 'NFC-e nao encontrada ou nao pode ser cancelada'];
        }

        if (strlen($justificativa) < 15) {
            return ['ok' => false, 'msg' => 'Justificativa deve ter no minimo 15 caracteres'];
        }

        try {
            $tools = $this->initTools();
            $chave = $nfce['chave_acesso'];
            $proto = $nfce['protocolo'];

            $resp = $tools->sefazCancela($chave, $justificativa, $proto);

            $this->log($nfceId, 'sefazCancela', $justificativa, $resp, true);

            $st = new Standardize($resp);
            $std = $st->toStd();

            $retEvento = $std->retEvento ?? null;
            if (!$retEvento) {
                return ['ok' => false, 'msg' => 'SEFAZ nao retornou evento de cancelamento'];
            }

            $infEvento = $retEvento->infEvento;
            $cStat = $infEvento->cStat;

            if ($cStat == 135 || $cStat == 155) {
                // Cancelado com sucesso
                $this->updateNfce($nfceId, 'cancelada', $chave, $proto, $nfce['xml_autorizado'], '');

                // Salvar XML de cancelamento
                $xmlDir = STORAGE_PATH . '/xml/canceladas/' . $this->tenantId . '/' . date('Y/m');
                if (!is_dir($xmlDir)) {
                    mkdir($xmlDir, 0755, true);
                }
                file_put_contents($xmlDir . '/' . $chave . '-canc.xml', $resp);

                return ['ok' => true, 'msg' => 'NFC-e cancelada com sucesso'];
            }

            $msg = "Erro cancelamento: [{$cStat}] " . ($infEvento->xMotivo ?? '');
            return ['ok' => false, 'msg' => $msg];

        } catch (\Exception $e) {
            $this->log($nfceId, 'cancelar_erro', '', $e->getMessage(), false);
            return ['ok' => false, 'msg' => 'Erro ao cancelar: ' . $e->getMessage()];
        }
    }

    /**
     * Inutilizar faixa de numeração
     */
    public function inutilizar(int $inicio, int $fim, string $motivo): array
    {
        if (strlen($motivo) < 15) {
            return ['ok' => false, 'msg' => 'Motivo deve ter no minimo 15 caracteres'];
        }

        try {
            $tools = $this->initTools();
            $serie = (int) getConfig('nfce_serie', '1');
            $ano = (int) date('y');

            $resp = $tools->sefazInutiliza($serie, $inicio, $fim, $motivo, $ano);

            $this->log(null, 'sefazInutiliza', "{$inicio}-{$fim}", $resp, true);

            $st = new Standardize($resp);
            $std = $st->toStd();

            $cStat = $std->infInut->cStat ?? '999';
            if ($cStat == 102) {
                $proto = $std->infInut->nProt ?? '';

                tenantInsert('nfce_inutilizadas', [
                    'numero_inicio' => $inicio,
                    'numero_fim'    => $fim,
                    'serie'         => $serie,
                    'protocolo'     => $proto,
                    'motivo'        => $motivo,
                ]);

                // Salvar XML
                $xmlDir = STORAGE_PATH . '/xml/inutilizadas/' . $this->tenantId . '/' . date('Y');
                if (!is_dir($xmlDir)) {
                    mkdir($xmlDir, 0755, true);
                }
                file_put_contents($xmlDir . "/inut-{$inicio}-{$fim}.xml", $resp);

                return ['ok' => true, 'msg' => "Numeros {$inicio} a {$fim} inutilizados com sucesso"];
            }

            $msg = "Erro inutilizacao: [{$cStat}] " . ($std->infInut->xMotivo ?? '');
            return ['ok' => false, 'msg' => $msg];

        } catch (\Exception $e) {
            $this->log(null, 'inutilizar_erro', '', $e->getMessage(), false);
            return ['ok' => false, 'msg' => 'Erro ao inutilizar: ' . $e->getMessage()];
        }
    }

    /**
     * Consultar NFC-e na SEFAZ pela chave
     */
    public function consultar(string $chave): array
    {
        try {
            $tools = $this->initTools();
            $resp = $tools->sefazConsultaChave($chave);

            $st = new Standardize($resp);
            $std = $st->toStd();

            $cStat = $std->cStat ?? '999';
            $xMotivo = $std->xMotivo ?? '';

            return [
                'ok'        => true,
                'msg'       => "[{$cStat}] {$xMotivo}",
                'cStat'     => $cStat,
                'protocolo' => $std->protNFe->infProt->nProt ?? '',
            ];
        } catch (\Exception $e) {
            return ['ok' => false, 'msg' => 'Erro na consulta: ' . $e->getMessage()];
        }
    }

    /**
     * Verificar status do serviço SEFAZ
     */
    public function statusServico(): array
    {
        try {
            $tools = $this->initTools();
            $resp = $tools->sefazStatus();

            $st = new Standardize($resp);
            $std = $st->toStd();

            $cStat = $std->cStat ?? '999';
            $xMotivo = $std->xMotivo ?? '';

            return [
                'online'   => ($cStat == 107),
                'mensagem' => "[{$cStat}] {$xMotivo}",
                'tMed'     => $std->tMed ?? 0,
            ];
        } catch (\Exception $e) {
            return ['online' => false, 'mensagem' => 'Erro: ' . $e->getMessage()];
        }
    }

    /**
     * Gerar PDF do DANFCE para impressão/download
     */
    public function gerarDanfce(string $xml): ?string
    {
        try {
            $danfce = new Danfce($xml);
            $largura = (int) getConfig('impressora_largura', '80');
            $danfce->setPaperWidth($largura === 58 ? 58 : 80);

            return $danfce->render();
        } catch (\Exception $e) {
            $this->log(null, 'danfce_erro', '', $e->getMessage(), false);
            return null;
        }
    }

    /**
     * Buscar XML autorizado pela chave (do banco)
     */
    public function getXmlAutorizado(string $chave): ?string
    {
        $stmt = db()->prepare("SELECT xml_autorizado FROM nfce WHERE chave_acesso = ? AND tenant_id = ? AND status = 'autorizada'");
        $stmt->execute([$chave, $this->tenantId]);
        $nfce = $stmt->fetch();
        return $nfce['xml_autorizado'] ?? null;
    }

    /**
     * Verifica se o tenant tem configuração fiscal completa
     */
    public function verificarConfiguracao(): array
    {
        $erros = [];
        $empresa = $this->getEmpresaTenant();

        if (empty($empresa['cnpj'])) {
            $erros[] = 'CNPJ da empresa nao cadastrado';
        }
        if (empty($empresa['razao_social'])) {
            $erros[] = 'Razao social nao cadastrada';
        }
        if (empty($empresa['estado'])) {
            $erros[] = 'Estado (UF) nao cadastrado';
        }
        if (empty($empresa['cidade'])) {
            $erros[] = 'Cidade nao cadastrada';
        }
        if (empty($empresa['endereco'])) {
            $erros[] = 'Endereco nao cadastrado';
        }

        $certPath = STORAGE_PATH . '/certificados/' . $this->tenantId . '/certificado.pfx';
        if (!file_exists($certPath)) {
            $erros[] = 'Certificado digital A1 nao enviado';
        }

        if (empty(getConfig('nfce_csc', ''))) {
            $erros[] = 'CSC (Token) nao configurado';
        }
        if (empty(getConfig('nfce_csc_id', ''))) {
            $erros[] = 'CSC ID (ID do Token) nao configurado';
        }

        return [
            'ok'    => empty($erros),
            'erros' => $erros,
        ];
    }

    // ===== Helpers privados =====

    private function updateNfce(int $id, string $status, string $chave, string $proto, string $xml, string $erro): void
    {
        db()->prepare("UPDATE nfce SET status=?, chave_acesso=?, protocolo=?, xml_autorizado=?, mensagem_erro=? WHERE id=? AND tenant_id=?")
            ->execute([$status, $chave, $proto, $xml, $erro, $id, $this->tenantId]);
    }

    private function log(?int $nfceId, string $comando, string $requestData, string $responseData, bool $sucesso): void
    {
        try {
            $stmt = db()->prepare("INSERT INTO nfce_logs (tenant_id, nfce_id, comando, request_data, response_data, sucesso) VALUES (?,?,?,?,?,?)");
            $stmt->execute([
                $this->tenantId,
                $nfceId,
                substr($comando, 0, 50),
                substr($requestData, 0, 65000),
                substr($responseData, 0, 65000),
                $sucesso ? 1 : 0,
            ]);
        } catch (\Exception) {
            // silenciar erros de log
        }
    }

    private function getCodigoUF(string $uf): int
    {
        $codigos = [
            'RO'=>11,'AC'=>12,'AM'=>13,'RR'=>14,'PA'=>15,'AP'=>16,'TO'=>17,
            'MA'=>21,'PI'=>22,'CE'=>23,'RN'=>24,'PB'=>25,'PE'=>26,'AL'=>27,'SE'=>28,'BA'=>29,
            'MG'=>31,'ES'=>32,'RJ'=>33,'SP'=>35,
            'PR'=>41,'SC'=>42,'RS'=>43,
            'MS'=>50,'MT'=>51,'GO'=>52,'DF'=>53,
        ];
        return $codigos[$uf] ?? 35;
    }

    private function getCodigoMunicipio(string $cidade, string $uf): string
    {
        $capitais = [
            'SP'=>'3550308','RJ'=>'3304557','MG'=>'3106200','BA'=>'2927408','PR'=>'4106902',
            'RS'=>'4314902','PE'=>'2611606','CE'=>'2304400','PA'=>'1501402','MA'=>'2111300',
            'GO'=>'5208707','AM'=>'1302603','SC'=>'4205407','PB'=>'2507507','ES'=>'3205309',
            'AL'=>'2704302','RN'=>'2408102','PI'=>'2211001','MT'=>'5103403','MS'=>'5002704',
            'DF'=>'5300108','SE'=>'2800308','RO'=>'1100205','TO'=>'1721000','AC'=>'1200401',
            'AP'=>'1600303','RR'=>'1400100',
        ];

        // Tentar buscar código IBGE do config do tenant
        $codigoConfig = getConfig('codigo_ibge_municipio', '');
        if (!empty($codigoConfig)) {
            return $codigoConfig;
        }

        return $capitais[$uf] ?? '3550308';
    }
}
