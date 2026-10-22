<?php
/**
 * Funções Auxiliares e Utilitários Gerais
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Adiciona mensagem Toast para ser exibida na interface
 * @param string $tipo 'success', 'error', 'warning', 'info'
 * @param string $mensagem Texto da mensagem
 * @param string $titulo Título opcional
 */
function adicionarToast(string $tipo, string $mensagem, string $titulo = ''): void {
    if (!isset($_SESSION['toasts'])) {
        $_SESSION['toasts'] = [];
    }
    $_SESSION['toasts'][] = [
        'tipo' => $tipo,
        'mensagem' => $mensagem,
        'titulo' => $titulo ?: ($tipo === 'success' ? 'Sucesso' : ($tipo === 'error' ? 'Erro' : 'Aviso')),
        'id' => uniqid('toast_')
    ];
}

/**
 * Retorna e limpa a lista de toasts acumulados
 */
function obterToasts(): array {
    $toasts = $_SESSION['toasts'] ?? [];
    $_SESSION['toasts'] = [];
    return $toasts;
}

/**
 * Limpa e sanitiza entradas de texto
 */
function limparInput($data): string {
    if (is_array($data)) {
        return '';
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Retorna dados do usuário atualmente autenticado
 */
function getUsuarioAtual(): ?array {
    if (!empty($_SESSION['usuario_id'])) {
        return [
            'id' => $_SESSION['usuario_id'],
            'nome' => $_SESSION['usuario_nome'] ?? 'Usuário',
            'email' => $_SESSION['usuario_email'] ?? '',
            'tipo' => $_SESSION['usuario_tipo'] ?? 'organizador'
        ];
    }
    return null;
}

/**
 * Verifica se há usuário logado
 */
function usuarioLogado(): bool {
    return !empty($_SESSION['usuario_id']);
}

/**
 * Verifica se o usuário atual é administrador
 */
function isAdmin(): bool {
    return usuarioLogado() && (($_SESSION['usuario_tipo'] ?? '') === 'admin');
}

/**
 * Exige login obrigatório para acessar a página
 */
function exigirLogin(): void {
    if (!usuarioLogado()) {
        adicionarToast('warning', 'Faça login para acessar esta funcionalidade.');
        header('Location: login.php');
        exit;
    }
}

/**
 * Exige perfil de administrador
 */
function exigirAdmin(): void {
    exigirLogin();
    if (!isAdmin()) {
        adicionarToast('error', 'Acesso restrito para administradores.');
        header('Location: dashboard.php');
        exit;
    }
}

/**
 * Resposta formatada em JSON (usada por endpoints AJAX)
 */
function responderJson(bool $sucesso, string $mensagem, array $dados = []): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'sucesso' => $sucesso,
        'mensagem' => $mensagem,
        'dados' => $dados
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Formata data e hora para o padrão brasileiro
 */
function formatarDataHora(?string $data): string {
    if (!$data) return '-';
    $timestamp = strtotime($data);
    if (!$timestamp) return $data;
    return date('d/m/Y H:i:s', $timestamp);
}

/**
 * Formata data simples
 */
function formatarDataSimples(?string $data): string {
    if (!$data) return '-';
    $timestamp = strtotime($data);
    if (!$timestamp) return $data;
    return date('d/m/Y', $timestamp);
}
