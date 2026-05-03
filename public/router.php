<?php
/**
 * Router para o servidor embutido do PHP (php -S)
 * - Resolve URLs amigáveis dos artigos: /artigos/[slug] -> /artigos/[slug].php
 * - Mantém o comportamento padrão para tudo o mais
 *
 * Uso: php -S 0.0.0.0:8081 -t /app/public /app/public/router.php
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$publicDir = __DIR__;
$filePath = $publicDir . $uri;

// Se for arquivo estático existente (CSS, JS, imagem etc), deixar o servidor servir
if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    return false;
}

// Roteamento de artigos: /artigos/[slug] (sem extensão) -> /artigos/[slug].php
if (preg_match('#^/artigos/([a-z0-9\-]+)/?$#', $uri, $m)) {
    $slug = $m[1];
    $artigoFile = $publicDir . '/artigos/' . $slug . '.php';

    // Bloquear acesso direto a arquivos de sistema (_layout, _helpers, etc.)
    if (str_starts_with($slug, '_')) {
        http_response_code(404);
        echo 'Não encontrado.';
        return true;
    }

    if (file_exists($artigoFile)) {
        require $artigoFile;
        return true;
    }

    http_response_code(404);
    echo 'Artigo não encontrado.';
    return true;
}

// Se a URL é um diretório, tentar servir o index.php dele
if (is_dir($filePath)) {
    $indexFile = rtrim($filePath, '/') . '/index.php';
    if (file_exists($indexFile)) {
        require $indexFile;
        return true;
    }
}

// Fallback: deixar o servidor lidar normalmente
return false;
