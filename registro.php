<?php
/**
 * Tela de Cadastro de Usuários com Hash Bcrypt
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/backend/helpers.php';
require_once __DIR__ . '/backend/auth.php';

if (usuarioLogado()) {
    header('Location: torneios.php');
    exit;
}

$pageTitle = 'Criar Nova Conta';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';
    $tipo = $_POST['tipo'] ?? 'organizador';

    if ($senha !== $confirmarSenha) {
        adicionarToast('error', 'A confirmação de senha não confere com a senha informada.');
    } else {
        $resultado = cadastrarUsuario($nome, $email, $senha, $tipo);
        if ($resultado['sucesso']) {
            adicionarToast('success', $resultado['mensagem']);
            header('Location: torneios.php');
            exit;
        } else {
            adicionarToast('error', $resultado['mensagem']);
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto my-6 sm:my-12">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-emerald-500 via-brand-500 to-indigo-500"></div>

        <div class="text-center mb-6">
            <div class="w-12 h-12 mx-auto rounded-2xl bg-emerald-600/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center text-xl mb-3 shadow-lg shadow-emerald-500/10">
                <i class="fa-solid fa-user-plus"></i>
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Criar Conta</h1>
            <p class="text-xs text-slate-400 mt-1">Cadastre-se para gerenciar seus campeonatos esportivos</p>
        </div>

        <form method="POST" action="registro.php" class="space-y-4">
            <div>
                <label for="nome" class="block text-xs font-semibold text-slate-300 mb-1.5">
                    Nome Completo
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500 text-sm">
                        <i class="fa-solid fa-user"></i>
                    </span>
                    <input type="text" name="nome" id="nome" required
                        value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>"
                        placeholder="Ex: Carlos Silva"
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">
                </div>
            </div>

            <div>
                <label for="email" class="block text-xs font-semibold text-slate-300 mb-1.5">
                    Endereço de E-mail
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500 text-sm">
                        <i class="fa-solid fa-envelope"></i>
                    </span>
                    <input type="email" name="email" id="email" required autocomplete="email"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        placeholder="seu.email@exemplo.com"
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">
                </div>
            </div>

            <div>
                <label for="tipo" class="block text-xs font-semibold text-slate-300 mb-1.5">
                    Perfil de Acesso
                </label>
                <select name="tipo" id="tipo" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-brand-500">
                    <option value="organizador">Organizador (Criar torneios e visualizar)</option>
                    <option value="admin">Administrador (Permissão total para placares e chaves)</option>
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="senha" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Senha
                    </label>
                    <div class="relative">
                        <input type="password" name="senha" id="senha" required minlength="6"
                            placeholder="Mínimo 6 chars"
                            class="w-full px-3 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">
                    </div>
                </div>

                <div>
                    <label for="confirmar_senha" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Confirmar Senha
                    </label>
                    <div class="relative">
                        <input type="password" name="confirmar_senha" id="confirmar_senha" required minlength="6"
                            placeholder="Repita a senha"
                            class="w-full px-3 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">
                    </div>
                </div>
            </div>

            <p class="text-[11px] text-slate-400">
                <i class="fa-solid fa-shield-halved text-emerald-400 mr-1"></i> 
                Sua senha será protegida com algoritmo criptográfico <strong>Bcrypt</strong>.
            </p>

            <button type="submit" class="w-full mt-2 py-3 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-emerald-600 to-brand-600 hover:from-emerald-500 hover:to-brand-500 shadow-lg shadow-emerald-500/25 transition-all flex items-center justify-center">
                <i class="fa-solid fa-user-check mr-2"></i> Concluir Cadastro
            </button>
        </form>

        <div class="mt-6 text-center text-xs text-slate-400">
            Já possui uma conta? 
            <a href="login.php" class="text-brand-400 hover:text-brand-300 font-semibold ml-1">
                Faça login
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
