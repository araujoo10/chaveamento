<?php
/**
 * Módulo de Autenticação e Gestão de Usuários
 * Criptografia Bcrypt e Auditoria de Acesso
 */

require_once __DIR__ . '/../config/bd.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/logs.php';

/**
 * Realiza autenticação do usuário
 */
function autenticarUsuario(string $email, string $senha): array {
    $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));

    if (empty($email) || empty($senha)) {
        return ['sucesso' => false, 'mensagem' => 'Informe o e-mail e a senha.'];
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($senha, $usuario['senha'])) {
        registrarLog('LOGIN_FALHA', "Tentativa de login malsucedida para o e-mail: $email");
        return ['sucesso' => false, 'mensagem' => 'E-mail ou senha incorretos.'];
    }

    // Login bem-sucedido: configura sessão
    $_SESSION['usuario_id'] = (int)$usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['usuario_email'] = $usuario['email'];
    $_SESSION['usuario_tipo'] = $usuario['tipo'];

    registrarLog('LOGIN', "Usuário {$usuario['nome']} ({$usuario['email']}) entrou no sistema.");

    return [
        'sucesso' => true,
        'mensagem' => 'Login realizado com sucesso! Bem-vindo(a), ' . $usuario['nome'],
        'usuario' => $usuario
    ];
}

/**
 * Cadastra um novo usuário no sistema com hash Bcrypt
 */
function cadastrarUsuario(string $nome, string $email, string $senha, string $tipo = 'organizador'): array {
    $nome = limparInput($nome);
    $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));

    if (empty($nome) || strlen($nome) < 3) {
        return ['sucesso' => false, 'mensagem' => 'O nome deve ter pelo menos 3 caracteres.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['sucesso' => false, 'mensagem' => 'Informe um endereço de e-mail válido.'];
    }

    if (strlen($senha) < 6) {
        return ['sucesso' => false, 'mensagem' => 'A senha deve conter no mínimo 6 caracteres.'];
    }

    if (!in_array($tipo, ['admin', 'organizador'])) {
        $tipo = 'organizador';
    }

    $db = getDB();

    // Verifica unicidade de e-mail
    $stmtCheck = $db->prepare("SELECT id FROM usuarios WHERE email = :email LIMIT 1");
    $stmtCheck->execute([':email' => $email]);
    if ($stmtCheck->fetch()) {
        return ['sucesso' => false, 'mensagem' => 'Este e-mail já está cadastrado no sistema.'];
    }

    // Criptografia com algoritmo Bcrypt
    $senhaHash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 10]);

    $stmtInsert = $db->prepare("
        INSERT INTO usuarios (nome, email, senha, tipo, criado_em)
        VALUES (:nome, :email, :senha, :tipo, :criado_em)
    ");

    $sucesso = $stmtInsert->execute([
        ':nome' => $nome,
        ':email' => $email,
        ':senha' => $senhaHash,
        ':tipo' => $tipo,
        ':criado_em' => date('Y-m-d H:i:s')
    ]);

    if ($sucesso) {
        $novoId = (int)$db->lastInsertId();
        registrarLog('CADASTRO_USUARIO', "Novo usuário cadastrado: $nome ($email) [$tipo]");

        // Se não houver usuário logado, loga o recém-cadastrado
        if (!usuarioLogado()) {
            $_SESSION['usuario_id'] = $novoId;
            $_SESSION['usuario_nome'] = $nome;
            $_SESSION['usuario_email'] = $email;
            $_SESSION['usuario_tipo'] = $tipo;
        }

        return ['sucesso' => true, 'mensagem' => 'Cadastro realizado com sucesso!'];
    }

    return ['sucesso' => false, 'mensagem' => 'Falha ao registrar usuário. Tente novamente.'];
}

/**
 * Encerra a sessão do usuário
 */
function encerrarSessao(): void {
    if (usuarioLogado()) {
        registrarLog('LOGOUT', "Usuário {$_SESSION['usuario_nome']} desconectou-se.");
    }
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
