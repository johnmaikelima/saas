<?php
require_once __DIR__ . '/../../app/bootstrap.php';
auditLog('logout', 'Logout realizado');
session_destroy();
redirect('auth/login.php');
