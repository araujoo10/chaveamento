<?php
/**
 * Barra de Navegação Responsiva Mobile-First
 */
require_once __DIR__ . '/../backend/helpers.php';
$usuario = getUsuarioAtual();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="bg-slate-900/90 backdrop-blur-md border-b border-slate-800 sticky top-0 z-40 transition-all">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            
            <!-- Logo & Marca -->
            <div class="flex items-center space-x-3">
                <a href="index.php" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 to-indigo-500 flex items-center justify-center text-white shadow-lg shadow-brand-500/25 group-hover:scale-105 transition-transform">
                        <i class="fa-solid fa-sitemap text-lg"></i>
                    </div>
                    <div>
                        <span class="text-lg font-bold font-['Outfit'] tracking-tight bg-gradient-to-r from-white via-slate-200 to-brand-400 bg-clip-text text-transparent">
                            Chaveamento<span class="text-brand-500">Auto</span>
                        </span>
                        <span class="hidden sm:inline-block text-[10px] uppercase tracking-wider font-semibold px-1.5 py-0.5 rounded bg-brand-950/80 text-brand-400 border border-brand-800/50 ml-1.5">
                            Esportivo
                        </span>
                    </div>
                </a>
            </div>

            <!-- Links Desktop -->
            <div class="hidden md:flex items-center space-x-1 lg:space-x-2">
                <a href="index.php" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= $currentPage === 'index.php' ? 'text-white bg-slate-800 border border-slate-700' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' ?>">
                    <i class="fa-solid fa-house mr-1.5 text-xs text-brand-400"></i> Início
                </a>
                <a href="torneios.php" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= $currentPage === 'torneios.php' ? 'text-white bg-slate-800 border border-slate-700' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' ?>">
                    <i class="fa-solid fa-trophy mr-1.5 text-xs text-amber-400"></i> Torneios
                </a>
                <a href="novo_torneio.php" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= $currentPage === 'novo_torneio.php' ? 'text-white bg-brand-600 shadow-md shadow-brand-500/20' : 'text-brand-400 hover:text-brand-300 hover:bg-brand-950/40' ?>">
                    <i class="fa-solid fa-circle-plus mr-1.5 text-xs"></i> Novo Torneio
                </a>
                <a href="historico.php" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= $currentPage === 'historico.php' ? 'text-white bg-slate-800 border border-slate-700' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' ?>">
                    <i class="fa-solid fa-shield-halved mr-1.5 text-xs text-emerald-400"></i> Histórico & Auditoria
                </a>
            </div>

            <!-- Ações do Usuário (Desktop) -->
            <div class="hidden md:flex items-center space-x-3">
                <?php if ($usuario): ?>
                    <div class="flex items-center space-x-3 bg-slate-800/60 border border-slate-700/60 rounded-xl px-3 py-1.5">
                        <div class="w-8 h-8 rounded-full bg-brand-600/30 border border-brand-500/30 flex items-center justify-center text-brand-300 text-sm font-semibold">
                            <?= mb_substr($usuario['nome'], 0, 1, 'UTF-8') ?>
                        </div>
                        <div class="text-left text-xs">
                            <div class="font-semibold text-slate-200"><?= htmlspecialchars($usuario['nome']) ?></div>
                            <div class="text-[10px] text-slate-400 uppercase tracking-wider flex items-center">
                                <span class="w-1.5 h-1.5 rounded-full <?= $usuario['tipo'] === 'admin' ? 'bg-amber-400' : 'bg-brand-400' ?> mr-1"></span>
                                <?= $usuario['tipo'] === 'admin' ? 'Administrador' : 'Organizador' ?>
                            </div>
                        </div>
                        <a href="logout.php" title="Sair da Conta" class="text-slate-400 hover:text-red-400 p-1.5 rounded-lg hover:bg-red-500/10 transition-colors ml-1">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="text-sm font-medium text-slate-300 hover:text-white px-3 py-2 rounded-lg hover:bg-slate-800 transition-colors">
                        <i class="fa-solid fa-arrow-right-to-bracket mr-1"></i> Entrar
                    </a>
                    <a href="registro.php" class="text-sm font-medium text-white bg-brand-600 hover:bg-brand-500 px-4 py-2 rounded-lg shadow-md shadow-brand-600/25 transition-all">
                        <i class="fa-solid fa-user-plus mr-1"></i> Cadastrar
                    </a>
                <?php endif; ?>
            </div>

            <!-- Botão Menu Mobile (Hamburger) -->
            <div class="flex md:hidden items-center space-x-2">
                <button type="button" id="mobile-menu-btn" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none" aria-label="Abrir Menu">
                    <i class="fa-solid fa-bars text-xl" id="menu-icon-bars"></i>
                    <i class="fa-solid fa-xmark text-xl hidden" id="menu-icon-close"></i>
                </button>
            </div>

        </div>
    </div>

    <!-- Menu Dropdown Mobile -->
    <div class="hidden md:hidden border-t border-slate-800 bg-slate-900 px-4 pt-3 pb-5 space-y-2" id="mobile-menu">
        <a href="index.php" class="flex items-center px-3 py-2.5 rounded-lg text-base font-medium <?= $currentPage === 'index.php' ? 'text-white bg-slate-800' : 'text-slate-300 hover:bg-slate-800' ?>">
            <i class="fa-solid fa-house w-6 text-brand-400"></i> Início
        </a>
        <a href="torneios.php" class="flex items-center px-3 py-2.5 rounded-lg text-base font-medium <?= $currentPage === 'torneios.php' ? 'text-white bg-slate-800' : 'text-slate-300 hover:bg-slate-800' ?>">
            <i class="fa-solid fa-trophy w-6 text-amber-400"></i> Torneios
        </a>
        <a href="novo_torneio.php" class="flex items-center px-3 py-2.5 rounded-lg text-base font-medium text-brand-400 bg-brand-950/40 border border-brand-800/40">
            <i class="fa-solid fa-circle-plus w-6"></i> Novo Torneio
        </a>
        <a href="historico.php" class="flex items-center px-3 py-2.5 rounded-lg text-base font-medium <?= $currentPage === 'historico.php' ? 'text-white bg-slate-800' : 'text-slate-300 hover:bg-slate-800' ?>">
            <i class="fa-solid fa-shield-halved w-6 text-emerald-400"></i> Histórico & Auditoria
        </a>

        <div class="border-t border-slate-800 pt-3 mt-3">
            <?php if ($usuario): ?>
                <div class="flex items-center justify-between px-3 py-2 bg-slate-800/50 rounded-lg">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 rounded-full bg-brand-600/30 flex items-center justify-center text-brand-300 font-semibold text-sm">
                            <?= mb_substr($usuario['nome'], 0, 1, 'UTF-8') ?>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-white"><?= htmlspecialchars($usuario['nome']) ?></div>
                            <div class="text-xs text-slate-400"><?= $usuario['tipo'] === 'admin' ? 'Administrador' : 'Organizador' ?></div>
                        </div>
                    </div>
                    <a href="logout.php" class="text-red-400 hover:text-red-300 text-sm font-medium px-2 py-1 rounded">
                        Sair
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-2 gap-2 pt-1">
                    <a href="login.php" class="text-center py-2 px-3 rounded-lg text-sm font-medium bg-slate-800 text-slate-200">
                        Entrar
                    </a>
                    <a href="registro.php" class="text-center py-2 px-3 rounded-lg text-sm font-medium bg-brand-600 text-white">
                        Cadastrar
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>
