<?php
/**
 * Verificação de autenticação e tenant
 * Incluir em TODAS as páginas protegidas
 */

require_once __DIR__ . '/../bootstrap.php';

// Rodar migrations automaticamente
autoMigrate();

// Validar sessão (anti-hijacking)
if (!validateSession()) {
    redirect('auth/login.php');
}

// URI atual para comparações
$_currentUri = $_SERVER['REQUEST_URI'] ?? '';
$_currentScript = $_SERVER['SCRIPT_NAME'] ?? '';

// Páginas públicas (não requerem login)
$isPublicPage = str_contains($_currentUri, 'auth/login')
    || str_contains($_currentScript, 'auth/login')
    || str_contains($_currentUri, 'auth/register')
    || str_contains($_currentScript, 'auth/register')
    || str_contains($_currentUri, 'auth/forgot')
    || str_contains($_currentScript, 'auth/forgot');

// Verificar autenticação
if (!isset($_SESSION['usuario']) || !isset($_SESSION['tenant_id'])) {
    if (!$isPublicPage) {
        redirect('auth/login.php');
    }
} else {
    // Verificar se tenant ainda está ativo
    $stmt = db()->prepare("SELECT status FROM tenants WHERE id = ?");
    $stmt->execute([tenantId()]);
    $tenantStatus = $stmt->fetchColumn();

    if ($tenantStatus !== 'ativo') {
        session_destroy();
        session_start();
        flashError('Sua conta foi suspensa. Entre em contato com o suporte.');
        redirect('auth/login.php');
    }
}

// Verificar se precisa trocar senha
if (isset($_SESSION['usuario']) && ($_SESSION['usuario']['trocar_senha'] ?? false)) {
    $isTrocarPage = str_contains($_currentUri, 'trocar_senha') || str_contains($_currentScript, 'trocar_senha');
    $isLogoutPage = str_contains($_currentUri, 'logout') || str_contains($_currentScript, 'logout');
    if (!$isTrocarPage && !$isLogoutPage) {
        redirect('auth/trocar_senha.php');
    }
}

// Restringir acesso de operador caixa apenas a PDV, caixa e auth
if (isset($_SESSION['usuario']) && ($_SESSION['usuario']['perfil'] ?? '') === 'caixa') {
    $allowedPaths = ['/pdv/', '/caixa/', '/auth/'];
    $isAllowed = false;
    foreach ($allowedPaths as $path) {
        if (str_contains($_currentUri, $path) || str_contains($_currentScript, $path)) {
            $isAllowed = true;
            break;
        }
    }
    if (!$isAllowed) {
        flashError('Sem permissão para acessar essa página.');
        redirect('pdv/');
    }
}
