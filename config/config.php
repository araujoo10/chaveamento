<?php
/**
 * Configuração Geral da Aplicação
 * Chaveamento Automático de Campeonatos Esportivos
 */

// Iniciar sessão nativa se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    // Configurações de segurança da sessão
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Fuso horário padrão do Brasil
date_default_timezone_set('America/Sao_Paulo');

// Constantes do Sistema
define('APP_NAME', 'Chaveamento Esportivo Pro');
define('APP_VERSION', '1.0.0');

// URL Base (detectada dinamicamente)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$baseUrl = rtrim($protocol . $host . $scriptDir, '/');
if (substr($baseUrl, -8) === '/backend' || substr($baseUrl, -7) === '/config') {
    $baseUrl = dirname($baseUrl);
}
define('BASE_URL', $baseUrl);

// Configurações de Banco de Dados MySQL
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'chaveamento_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', 'utf8mb4');
