<?php
/**
 * Tela de Login com Hash Bcrypt
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/backend/helpers.php';
require_once __DIR__ . '/backend/auth.php';

// Se já estiver logado, redireciona
if (usuarioLogado()) {
    header('Location: torneios.php');
    exit;
}

$pageTitle = 'Entrar no Sistema';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    $resultado = autenticarUsuario($email, $senha);

    if ($resultado['sucesso']) {
        adicionarToast('success', $resultado['mensagem']);
        header('Location: torneios.php');
        exit;
    } else {
        adicionarToast('error', $resultado['mensagem']);
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto my-8 sm:my-16">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-brand-500 via-indigo-500 to-emerald-500"></div>

        <div class="text-center mb-6">
            <div class="w-12 h-12 mx-auto rounded-2xl bg-brand-600/20 text-brand-400 border border-brand-500/30 flex items-center justify-center text-xl mb-3 shadow-lg shadow-brand-500/10">
                <i class="fa-solid fa-lock"></i>
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Acesso ao Sistema</h1>
            <p class="text-xs text-slate-400 mt-1">Entre com suas credenciais para gerenciar chaveamentos e placares</p>
        </div>

        <form method="POST" action="login.php" class="space-y-4">
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
                <label for="senha" class="block text-xs font-semibold text-slate-300 mb-1.5">
                    Senha de Acesso
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500 text-sm">
                        <i class="fa-solid fa-key"></i>
                    </span>
                    <input type="password" name="senha" id="senha" required autocomplete="current-password"
                        placeholder="••••••••"
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-colors">
                </div>
            </div>

            <button type="submit" class="w-full mt-2 py-3 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 shadow-lg shadow-brand-500/25 transition-all flex items-center justify-center">
                <i class="fa-solid fa-right-to-bracket mr-2"></i> Entrar no Painel
            </button>
        </form>

        <!-- Atalho Rápido de Demonstração -->
        <div class="mt-6 pt-5 border-t border-slate-800/80">
            <div class="bg-slate-950/70 border border-slate-800 rounded-xl p-3 text-xs text-slate-400">
                <div class="font-semibold text-slate-300 flex items-center justify-between mb-1.5">
                    <span><i class="fa-solid fa-user-shield text-amber-400 mr-1.5"></i> Administrador Geral (Padrão):</span>
                    <button type="button" onclick="preencherAdmin()" class="text-[11px] text-brand-400 hover:text-brand-300 font-bold underline">
                        Usar dados
                    </button>
                </div>
                <div class="space-y-0.5 text-[11px]">
                    <div>E-mail: <code class="text-brand-300">admin@etec.sp.gov.br</code></div>
                    <div>Senha: <code class="text-brand-300">admin123</code></div>
                </div>
            </div>
        </div>

        <div class="mt-6 text-center text-xs text-slate-400">
            Não possui uma conta? 
            <a href="registro.php" class="text-brand-400 hover:text-brand-300 font-semibold ml-1">
                Cadastre-se aqui
            </a>
        </div>
    </div>
</div>

<script>
function preencherAdmin() {
    document.getElementById('email').value = 'admin@etec.sp.gov.br';
    document.getElementById('senha').value = 'admin123';
    mostrarToast('info', 'Credenciais de Administrador preenchidas. Clique em Entrar.');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
