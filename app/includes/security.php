<?php
/**
 * Funções de segurança do SaaS
 * Rate limiting, audit log, proteção de sessão
 */

// ============================================
//   Rate Limiting (por IP, armazenado no banco)
// ============================================

/**
 * Verifica rate limit. Retorna true se bloqueado.
 * @param string $action Ação (ex: 'login', 'register')
 * @param int $maxAttempts Máximo de tentativas
 * @param int $windowSeconds Janela de tempo em segundos
 */
function rateLimited(string $action, int $maxAttempts = 5, int $windowSeconds = 300): bool {
    $ip = getClientIp();
    $key = $action . ':' . $ip;

    try {
        $pdo = db();

        // Limpar registros expirados
        $pdo->prepare("DELETE FROM rate_limits WHERE expires_at < NOW()")->execute();

        // Contar tentativas
        $stmt = $pdo->prepare("SELECT attempts FROM rate_limits WHERE action_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();

        if ($row && (int)$row['attempts'] >= $maxAttempts) {
            return true; // Bloqueado
        }

        return false;
    } catch (PDOException $e) {
        // Tabela pode não existir ainda
        return false;
    }
}

/**
 * Registra uma tentativa de ação (para rate limiting).
 */
function rateLimitHit(string $action, int $windowSeconds = 300): void {
    $ip = getClientIp();
    $key = $action . ':' . $ip;

    try {
        $pdo = db();
        $expires = date('Y-m-d H:i:s', time() + $windowSeconds);

        $stmt = $pdo->prepare("SELECT id, attempts FROM rate_limits WHERE action_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();

        if ($row) {
            $pdo->prepare("UPDATE rate_limits SET attempts = attempts + 1, expires_at = ? WHERE id = ?")
                ->execute([$expires, $row['id']]);
        } else {
            $pdo->prepare("INSERT INTO rate_limits (action_key, attempts, expires_at) VALUES (?, 1, ?)")
                ->execute([$key, $expires]);
        }
    } catch (PDOException $e) {
        // Ignorar se tabela não existe
    }
}

/**
 * Limpa rate limit para uma ação (ex: após login bem-sucedido).
 */
function rateLimitClear(string $action): void {
    $ip = getClientIp();
    $key = $action . ':' . $ip;

    try {
        db()->prepare("DELETE FROM rate_limits WHERE action_key = ?")->execute([$key]);
    } catch (PDOException $e) {
        // Ignorar
    }
}

// ============================================
//   Obter IP real do cliente
// ============================================
function getClientIp(): string {
    // Confiar em X-Forwarded-For apenas se vindo de proxy confiável (Coolify/Docker)
    $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $trustedProxies = ['127.0.0.1', '::1', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'];

    $isTrusted = false;
    foreach ($trustedProxies as $proxy) {
        if (str_contains($proxy, '/')) {
            // CIDR check simplificado
            [$subnet, $mask] = explode('/', $proxy);
            if ((ip2long($remoteAddr) & ~((1 << (32 - (int)$mask)) - 1)) === ip2long($subnet)) {
                $isTrusted = true;
                break;
            }
        } elseif ($remoteAddr === $proxy) {
            $isTrusted = true;
            break;
        }
    }

    if ($isTrusted && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $remoteAddr;
}

// ============================================
//   Audit Log
// ============================================

/**
 * Registrar ação no log de auditoria.
 */
function auditLog(string $acao, string $detalhes = '', ?int $tenantId = null, ?int $usuarioId = null): void {
    $tenantId = $tenantId ?? tenantId();
    $usuarioId = $usuarioId ?? ($_SESSION['usuario']['id'] ?? null);
    $ip = getClientIp();
    $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

    try {
        $stmt = db()->prepare("INSERT INTO audit_log (tenant_id, usuario_id, acao, detalhes, ip, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tenantId ?: null, $usuarioId, $acao, $detalhes, $ip, $userAgent]);
    } catch (PDOException $e) {
        error_log("Audit log error: " . $e->getMessage());
    }
}

// ============================================
//   Proteção de sessão
// ============================================

/**
 * Regenerar sessão (chamar no login e em mudanças de privilégio).
 */
function regenerateSession(): void {
    session_regenerate_id(true);
}

/**
 * Verificar se a sessão é válida (anti session fixation/hijacking).
 */
function validateSession(): bool {
    // Verificar fingerprint do navegador
    $fingerprint = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));

    if (!isset($_SESSION['_fingerprint'])) {
        $_SESSION['_fingerprint'] = $fingerprint;
        return true;
    }

    if ($_SESSION['_fingerprint'] !== $fingerprint) {
        // Sessão possivelmente sequestrada
        session_destroy();
        return false;
    }

    // Verificar expiração da sessão (além do cookie)
    if (isset($_SESSION['_last_activity']) && (time() - $_SESSION['_last_activity'] > SESSION_LIFETIME)) {
        session_destroy();
        return false;
    }

    $_SESSION['_last_activity'] = time();
    return true;
}

// ============================================
//   Validação de entrada
// ============================================

/**
 * Sanitizar e validar email.
 */
function validateEmail(string $email): string|false {
    $email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);
    return $email ?: false;
}

/**
 * Verificar força da senha.
 * Mínimo 8 caracteres, pelo menos 1 número e 1 letra.
 */
function validatePassword(string $senha): bool {
    return strlen($senha) >= 8
        && preg_match('/[A-Za-z]/', $senha)
        && preg_match('/[0-9]/', $senha);
}

/**
 * Hash seguro de senha.
 */
function hashPassword(string $senha): string {
    return password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verificar senha.
 */
function verifyPassword(string $senha, string $hash): bool {
    return password_verify($senha, $hash);
}
