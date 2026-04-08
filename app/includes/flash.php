<?php
/**
 * Sistema de mensagens flash via sessão
 */

function flash(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flashSuccess(string $msg): void { flash('success', $msg); }
function flashError(string $msg): void { flash('danger', $msg); }
function flashWarning(string $msg): void { flash('warning', $msg); }
function flashInfo(string $msg): void { flash('info', $msg); }

function renderFlash(): string {
    if (empty($_SESSION['flash'])) return '';
    $html = '';
    foreach ($_SESSION['flash'] as $f) {
        $icon = match($f['type']) {
            'success' => 'fa-check-circle',
            'danger' => 'fa-exclamation-circle',
            'warning' => 'fa-exclamation-triangle',
            'info' => 'fa-info-circle',
            default => 'fa-info-circle'
        };
        $html .= '<div class="alert alert-' . e($f['type']) . ' alert-dismissible fade show" role="alert">';
        $html .= '<i class="fas ' . $icon . ' me-2"></i>' . e($f['message']);
        $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
    $_SESSION['flash'] = [];
    return $html;
}
