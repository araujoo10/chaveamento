<?php
/**
 * Script de Logout
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/backend/helpers.php';
require_once __DIR__ . '/backend/auth.php';

encerrarSessao();

// Como a sessão foi destruída, reiniciamos temporariamente para enviar a toast de saída
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
adicionarToast('info', 'Sua sessão foi encerrada com segurança.');
header('Location: login.php');
exit;
