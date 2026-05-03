<?php
// Helpers compartilhados pelas páginas de artigos (autocontido)

// Garantir que config esteja carregado (define APP_URL e outras constantes)
if (!defined('APP_URL')) {
    require_once __DIR__ . '/../../app/config.php';
}

if (!function_exists('eArt')) {
    function eArt($s): string {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('eAttr')) {
    function eAttr($s): string {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}
