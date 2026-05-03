<?php
// Helpers compartilhados pelas páginas de artigos (autocontido)

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
