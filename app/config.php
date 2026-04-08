<?php
/**
 * Configurações globais do SaaS PDV Pro
 */

// Caminhos
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', __DIR__);
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');
define('STORAGE_PATH', ROOT_PATH . '/storage');

// Banco de dados MySQL
define('DB_HOST', $_ENV['DB_HOST'] ?? '127.0.0.1');
define('DB_PORT', $_ENV['DB_PORT'] ?? '3306');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'pdvpro_saas');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

// Aplicação
define('APP_NAME', 'PDV Pro');
define('APP_VERSION', '1.0.0');

// URL base
$_scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('APP_URL', $_ENV['APP_URL'] ?? ($_scheme . '://' . $_host));

// API do Painel (licenciamento)
define('PAINEL_API_URL', $_ENV['PAINEL_API_URL'] ?? 'https://painel.sub2.altusci.com.br/api/index.php');

// Sessão
define('SESSION_LIFETIME', 28800); // 8 horas

// Setup key
define('SETUP_KEY', $_ENV['SETUP_KEY'] ?? 'pdvpro-saas-2026');
