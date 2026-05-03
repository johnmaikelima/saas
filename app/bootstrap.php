<?php
/**
 * Bootstrap do SaaS Balcão PDV - Multi-tenant com segurança
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/flash.php';
require_once __DIR__ . '/includes/security.php';

// Autoload do Composer (sped-nfe, sped-da)
$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// ============================================
//   Headers de segurança (todas as respostas)
// ============================================
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// ============================================
//   Sessão segura
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_start([
        'cookie_lifetime' => SESSION_LIFETIME,
        'gc_maxlifetime' => SESSION_LIFETIME,
        'cookie_httponly' => true,
        'cookie_secure' => $isSecure,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
        'use_only_cookies' => true,
    ]);
}

// ============================================
//   Conexão PDO MySQL
// ============================================
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

// ============================================
//   Multi-tenant: funções centralizadas
// ============================================

/**
 * Retorna o tenant_id da sessão. NUNCA aceitar via input.
 */
function tenantId(): int {
    return (int)($_SESSION['tenant_id'] ?? 0);
}

/**
 * Redireciona para login se não houver tenant ativo.
 */
function requireTenant(): void {
    if (tenantId() <= 0) {
        redirect('auth/login.php');
    }
}

/**
 * Query segura com tenant_id injetado automaticamente.
 * Evita que qualquer query esqueça o filtro de tenant.
 *
 * Uso: tenantQuery("SELECT * FROM produtos WHERE ativo = 1 ORDER BY descricao")
 * Resultado: "SELECT * FROM produtos WHERE tenant_id = ? AND ativo = 1 ORDER BY descricao"
 * O tenant_id é adicionado como primeiro parâmetro automaticamente.
 */
function tenantQuery(string $sql, array $params = []): \PDOStatement {
    $tid = tenantId();
    if ($tid <= 0) {
        throw new \RuntimeException('Tenant não identificado. Acesso negado.');
    }

    // Injetar tenant_id no WHERE
    // Suporta: WHERE ..., sem WHERE (adiciona), e subqueries
    if (preg_match('/\bWHERE\b/i', $sql)) {
        $sql = preg_replace('/\bWHERE\b/i', 'WHERE tenant_id = ? AND', $sql, 1);
    } elseif (preg_match('/\bORDER\s+BY\b/i', $sql)) {
        $sql = preg_replace('/\bORDER\s+BY\b/i', 'WHERE tenant_id = ? ORDER BY', $sql, 1);
    } elseif (preg_match('/\bGROUP\s+BY\b/i', $sql)) {
        $sql = preg_replace('/\bGROUP\s+BY\b/i', 'WHERE tenant_id = ? GROUP BY', $sql, 1);
    } elseif (preg_match('/\bLIMIT\b/i', $sql)) {
        $sql = preg_replace('/\bLIMIT\b/i', 'WHERE tenant_id = ? LIMIT', $sql, 1);
    } else {
        $sql .= ' WHERE tenant_id = ?';
    }

    array_unshift($params, $tid);
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * INSERT seguro com tenant_id injetado automaticamente.
 */
function tenantInsert(string $table, array $data): int {
    $tid = tenantId();
    if ($tid <= 0) {
        throw new \RuntimeException('Tenant não identificado. Acesso negado.');
    }

    $data['tenant_id'] = $tid;
    $fields = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));

    $stmt = db()->prepare("INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})");
    $stmt->execute(array_values($data));
    return (int)db()->lastInsertId();
}

/**
 * UPDATE seguro: SEMPRE filtra por tenant_id + id.
 */
function tenantUpdate(string $table, array $data, int $id): bool {
    $tid = tenantId();
    if ($tid <= 0) {
        throw new \RuntimeException('Tenant não identificado. Acesso negado.');
    }

    $set = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($data)));
    $params = array_values($data);
    $params[] = $id;
    $params[] = $tid;

    $stmt = db()->prepare("UPDATE {$table} SET {$set} WHERE id = ? AND tenant_id = ?");
    $stmt->execute($params);
    return $stmt->rowCount() > 0;
}

/**
 * DELETE seguro: SEMPRE filtra por tenant_id + id.
 */
function tenantDelete(string $table, int $id): bool {
    $tid = tenantId();
    if ($tid <= 0) {
        throw new \RuntimeException('Tenant não identificado. Acesso negado.');
    }

    $stmt = db()->prepare("DELETE FROM {$table} WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$id, $tid]);
    return $stmt->rowCount() > 0;
}

/**
 * Buscar um registro por ID (com verificação de tenant).
 */
function tenantFind(string $table, int $id): ?array {
    $tid = tenantId();
    if ($tid <= 0) return null;

    $stmt = db()->prepare("SELECT * FROM {$table} WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$id, $tid]);
    return $stmt->fetch() ?: null;
}

// ============================================
//   Auto-run migrations
// ============================================
function autoMigrate(): void {
    require_once __DIR__ . '/migrate.php';
    runMigrations(db());
}
