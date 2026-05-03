<?php
/**
 * Funções helpers globais - Balcão PDV
 * Todas as funções que acessam dados DEVEM usar tenantQuery/tenantInsert/tenantUpdate/tenantDelete
 */

/**
 * Redirecionar para URL interna
 */
function redirect(string $url): void {
    header("Location: " . APP_URL . '/' . ltrim($url, '/'));
    exit;
}

/**
 * Redirecionar para URL absoluta
 */
function redirectTo(string $url): void {
    header("Location: {$url}");
    exit;
}

/**
 * Obter configuração do banco (por tenant)
 */
function getConfig(string $chave, string $default = ''): string {
    static $cache = [];
    $tid = tenantId();
    $key = $tid . ':' . $chave;

    if (isset($cache[$key])) return $cache[$key];

    if ($tid <= 0) return $default;

    $stmt = db()->prepare("SELECT valor FROM configuracoes WHERE tenant_id = ? AND chave = ?");
    $stmt->execute([$tid, $chave]);
    $row = $stmt->fetch();
    $cache[$key] = $row ? $row['valor'] : $default;
    return $cache[$key];
}

/**
 * Salvar configuração (por tenant)
 */
function setConfig(string $chave, string $valor, string $grupo = 'sistema'): void {
    $tid = tenantId();
    if ($tid <= 0) return;

    $stmt = db()->prepare("INSERT INTO configuracoes (tenant_id, chave, valor, grupo) VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
    $stmt->execute([$tid, $chave, $valor, $grupo]);
}

/**
 * Obter dados do tenant (empresa)
 */
function getEmpresa(): array {
    $tid = tenantId();
    if ($tid <= 0) return [];

    $stmt = db()->prepare("SELECT * FROM tenants WHERE id = ?");
    $stmt->execute([$tid]);
    return $stmt->fetch() ?: [];
}

/**
 * Obter usuário logado
 */
function usuario(): ?array {
    return $_SESSION['usuario'] ?? null;
}

/**
 * Verificar se usuário tem perfil
 */
function temPerfil(string ...$perfis): bool {
    $user = usuario();
    return $user && in_array($user['perfil'], $perfis);
}

/**
 * Formatar valor em reais
 */
function formatMoney(float $valor): string {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

/**
 * Formatar data BR
 */
function formatDate(string $date): string {
    if (empty($date)) return '';
    return date('d/m/Y', strtotime($date));
}

/**
 * Formatar data e hora BR
 */
function formatDateTime(string $date): string {
    if (empty($date)) return '';
    return date('d/m/Y H:i', strtotime($date));
}

/**
 * Escape HTML (anti XSS)
 */
function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Obter caixa aberto do usuário (filtrado por tenant)
 */
function getCaixaAberto(?int $usuario_id = null): ?array {
    $usuario_id = $usuario_id ?? usuario()['id'];
    $tid = tenantId();
    if ($tid <= 0) return null;

    $stmt = db()->prepare("
        SELECT c.*, p.nome as pdv_nome
        FROM caixas c
        LEFT JOIN pdvs p ON p.id = c.pdv_id
        WHERE c.tenant_id = ? AND c.usuario_id = ? AND c.status = 'aberto'
        LIMIT 1
    ");
    $stmt->execute([$tid, $usuario_id]);
    return $stmt->fetch() ?: null;
}

/**
 * Verificar se existe caixa aberto (qualquer usuário do tenant)
 */
function existeCaixaAberto(): bool {
    $tid = tenantId();
    if ($tid <= 0) return false;

    $stmt = db()->prepare("SELECT COUNT(*) FROM caixas WHERE tenant_id = ? AND status = 'aberto'");
    $stmt->execute([$tid]);
    return (int)$stmt->fetchColumn() > 0;
}

/**
 * Gerar token CSRF
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar CSRF (timing-safe comparison)
 */
function verifyCsrf(): bool {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($token) || empty($_SESSION['csrf_token'])) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Input CSRF hidden
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

/**
 * Limpar CPF/CNPJ
 */
function limparCpfCnpj(string $doc): string {
    return preg_replace('/[^0-9]/', '', $doc);
}

/**
 * Formatar CPF
 */
function formatCpf(string $cpf): string {
    $cpf = limparCpfCnpj($cpf);
    if (strlen($cpf) === 11) {
        return substr($cpf,0,3).'.'.substr($cpf,3,3).'.'.substr($cpf,6,3).'-'.substr($cpf,9,2);
    }
    return $cpf;
}

/**
 * Formatar CNPJ
 */
function formatCnpj(string $cnpj): string {
    $cnpj = limparCpfCnpj($cnpj);
    if (strlen($cnpj) === 14) {
        return substr($cnpj,0,2).'.'.substr($cnpj,2,3).'.'.substr($cnpj,5,3).'/'.substr($cnpj,8,4).'-'.substr($cnpj,12,2);
    }
    return $cnpj;
}

/**
 * Formatar CPF ou CNPJ automaticamente
 */
function formatDoc(string $doc): string {
    $doc = limparCpfCnpj($doc);
    return strlen($doc) <= 11 ? formatCpf($doc) : formatCnpj($doc);
}

/**
 * Validar CPF
 */
function validaCpf(string $cpf): bool {
    $cpf = limparCpfCnpj($cpf);
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) return false;
    for ($t = 9; $t < 11; $t++) {
        $d = 0;
        for ($c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$t] != $d) return false;
    }
    return true;
}

/**
 * Validar CNPJ
 */
function validaCnpj(string $cnpj): bool {
    $cnpj = limparCpfCnpj($cnpj);
    if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) return false;
    $tamanho = strlen($cnpj) - 2;
    $numeros = substr($cnpj, 0, $tamanho);
    $digitos = substr($cnpj, $tamanho);
    $soma = 0; $pos = $tamanho - 7;
    for ($i = $tamanho; $i >= 1; $i--) {
        $soma += $numeros[$tamanho - $i] * $pos--;
        if ($pos < 2) $pos = 9;
    }
    if ($digitos[0] != ((($soma % 11) < 2) ? 0 : 11 - ($soma % 11))) return false;
    $tamanho++; $numeros = substr($cnpj, 0, $tamanho);
    $soma = 0; $pos = $tamanho - 7;
    for ($i = $tamanho; $i >= 1; $i--) {
        $soma += $numeros[$tamanho - $i] * $pos--;
        if ($pos < 2) $pos = 9;
    }
    return $digitos[1] == ((($soma % 11) < 2) ? 0 : 11 - ($soma % 11));
}

/**
 * Paginação segura (sempre filtra por tenant via tenantQuery)
 */
function paginate(string $query, array $params, int $page, int $perPage = 20): array {
    $tid = tenantId();
    if ($tid <= 0) {
        return ['data' => [], 'total' => 0, 'page' => 1, 'perPage' => $perPage, 'totalPages' => 1];
    }

    // Injetar tenant_id na query de contagem
    $countSql = $query;
    if (preg_match('/\bWHERE\b/i', $countSql)) {
        $countSql = preg_replace('/\bWHERE\b/i', 'WHERE tenant_id = ? AND', $countSql, 1);
    } else {
        // Adicionar WHERE antes de ORDER BY/GROUP BY se existir
        if (preg_match('/\bORDER\s+BY\b/i', $countSql)) {
            $countSql = preg_replace('/\bORDER\s+BY\b/i', 'WHERE tenant_id = ? ORDER BY', $countSql, 1);
        } else {
            $countSql .= ' WHERE tenant_id = ?';
        }
    }

    $countParams = array_merge([$tid], $params);

    $stmt = db()->prepare("SELECT COUNT(*) FROM ({$countSql}) AS t");
    $stmt->execute($countParams);
    $total = (int)$stmt->fetchColumn();

    $totalPages = max(1, ceil($total / $perPage));
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;

    $dataSql = $countSql . " LIMIT {$perPage} OFFSET {$offset}";
    $stmt = db()->prepare($dataSql);
    $stmt->execute($countParams);
    $data = $stmt->fetchAll();

    return [
        'data' => $data,
        'total' => $total,
        'page' => $page,
        'perPage' => $perPage,
        'totalPages' => $totalPages,
    ];
}

/**
 * Renderizar paginação HTML
 */
function renderPagination(array $pag, string $baseUrl = '?'): string {
    if ($pag['totalPages'] <= 1) return '';
    $html = '<nav><ul class="pagination pagination-sm justify-content-center">';
    $sep = str_contains($baseUrl, '?') ? '&' : '?';

    if ($pag['page'] > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . e($baseUrl) . $sep . 'page=' . ($pag['page']-1) . '">&laquo;</a></li>';
    }
    for ($i = max(1, $pag['page']-2); $i <= min($pag['totalPages'], $pag['page']+2); $i++) {
        $active = $i === $pag['page'] ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . e($baseUrl) . $sep . 'page=' . $i . '">' . $i . '</a></li>';
    }
    if ($pag['page'] < $pag['totalPages']) {
        $html .= '<li class="page-item"><a class="page-link" href="' . e($baseUrl) . $sep . 'page=' . ($pag['page']+1) . '">&raquo;</a></li>';
    }
    $html .= '</ul></nav>';
    return $html;
}

/**
 * Upload de arquivo seguro
 */
function uploadFile(array $file, string $destDir, array $allowedTypes = ['image/jpeg','image/png','image/webp']): string|false {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;

    // Verificar tipo MIME real (não confiar no type enviado pelo browser)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $realMime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($realMime, $allowedTypes)) return false;

    // Limitar tamanho (5MB para imagens)
    if ($file['size'] > 5 * 1024 * 1024) return false;

    // Gerar nome seguro (sem preservar o nome original)
    $ext = match($realMime) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => 'bin'
    };
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest = UPLOAD_PATH . '/' . $destDir . '/' . $name;

    if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return $destDir . '/' . $name;
    }
    return false;
}

/**
 * Obter URL base
 */
function baseUrl(string $path = ''): string {
    return APP_URL . '/' . ltrim($path, '/');
}

/**
 * Data atual local
 */
function agora(): string {
    return date('Y-m-d H:i:s');
}

/**
 * Sanitizar string (anti XSS em inputs)
 */
function sanitize(?string $str): string {
    return trim(strip_tags($str ?? ''));
}

/**
 * Encriptar valor sensível (AES-256-GCM)
 * Usa APP_KEY ou PAINEL_API_SECRET como chave de encriptação
 */
function encryptValue(string $value): string {
    $key = hash('sha256', $_ENV['APP_KEY'] ?? PAINEL_API_SECRET ?? 'kaixa-default-key', true);
    $iv = random_bytes(12);
    $tag = '';
    $encrypted = openssl_encrypt($value, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return base64_encode($iv . $tag . $encrypted);
}

/**
 * Decriptar valor sensível (AES-256-GCM)
 */
function decryptValue(string $encoded): string {
    $key = hash('sha256', $_ENV['APP_KEY'] ?? PAINEL_API_SECRET ?? 'kaixa-default-key', true);
    $data = base64_decode($encoded);
    if ($data === false || strlen($data) < 28) return '';
    $iv = substr($data, 0, 12);
    $tag = substr($data, 12, 16);
    $encrypted = substr($data, 28);
    $decrypted = openssl_decrypt($encrypted, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return $decrypted !== false ? $decrypted : '';
}
