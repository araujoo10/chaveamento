<?php
/**
 * Página Inicial / Dashboard Público
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/backend/helpers.php';
require_once __DIR__ . '/backend/torneios.php';

$pageTitle = 'Chaveamento Automático de Campeonatos';

// Estatísticas gerais
$db = getDB();
$totalTorneios = (int)$db->query("SELECT COUNT(*) FROM torneios")->fetchColumn();
$totalPartidas = (int)$db->query("SELECT COUNT(*) FROM partidas WHERE status = 'finalizada'")->fetchColumn();
$totalEquipes = (int)$db->query("SELECT COUNT(*) FROM participantes")->fetchColumn();
$totalLogs = (int)$db->query("SELECT COUNT(*) FROM logs_auditoria")->fetchColumn();

// Torneios recentes
$torneiosRecentes = listarTorneios();
$torneiosDestaque = array_slice($torneiosRecentes, 0, 6);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-brand-950/40 to-slate-900 border border-slate-800 p-8 sm:p-12 mb-10 shadow-2xl">
    <div class="absolute -top-24 -right-24 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="relative z-10 max-w-3xl">
        <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-brand-500/10 border border-brand-500/30 text-brand-300 text-xs font-semibold uppercase tracking-wider mb-5">
            <i class="fa-solid fa-sparkles text-brand-400"></i>
            <span>Sistema Oficial de Chaveamento Esportivo</span>
        </div>

        <h1 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight leading-tight mb-4">
            Organize seus campeonatos com <span class="bg-gradient-to-r from-brand-400 via-indigo-300 to-brand-500 bg-clip-text text-transparent">chaveamento automático</span> e auditoria justa.
        </h1>

        <p class="text-base sm:text-lg text-slate-300 leading-relaxed mb-8">
            Geração instantânea de chaves mata-mata inspirada no BracketMaker, indicação automática de classificados, lançamento seguro de placares e histórico imutável contra manipulações.
        </p>

        <div class="flex flex-wrap items-center gap-4">
            <a href="novo_torneio.php" class="px-6 py-3.5 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 shadow-xl shadow-brand-500/25 transition-all transform hover:-translate-y-0.5 flex items-center">
                <i class="fa-solid fa-circle-plus mr-2 text-base"></i> Criar Novo Torneio
            </a>
            <a href="torneios.php" class="px-6 py-3.5 rounded-xl font-bold text-sm text-slate-200 bg-slate-800/80 hover:bg-slate-700/80 border border-slate-700 transition-all flex items-center">
                <i class="fa-solid fa-trophy mr-2 text-amber-400 text-base"></i> Explorar Torneios
            </a>
            <a href="historico.php" class="px-5 py-3.5 rounded-xl font-semibold text-sm text-slate-400 hover:text-slate-200 hover:bg-slate-800/40 transition-colors flex items-center">
                <i class="fa-solid fa-shield-halved mr-2 text-emerald-400"></i> Auditoria de Logs
            </a>
        </div>
    </div>
</div>

<!-- Barra de Métricas / Estatísticas -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
    <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 flex items-center space-x-4">
        <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center text-xl flex-shrink-0">
            <i class="fa-solid fa-trophy"></i>
        </div>
        <div>
            <div class="text-2xl font-black text-white"><?= $totalTorneios ?></div>
            <div class="text-xs font-medium text-slate-400 uppercase tracking-wider">Torneios Criados</div>
        </div>
    </div>

    <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 flex items-center space-x-4">
        <div class="w-12 h-12 rounded-xl bg-brand-500/10 border border-brand-500/20 text-brand-400 flex items-center justify-center text-xl flex-shrink-0">
            <i class="fa-solid fa-users"></i>
        </div>
        <div>
            <div class="text-2xl font-black text-white"><?= $totalEquipes ?></div>
            <div class="text-xs font-medium text-slate-400 uppercase tracking-wider">Equipes & Atletas</div>
        </div>
    </div>

    <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 flex items-center space-x-4">
        <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-xl flex-shrink-0">
            <i class="fa-solid fa-futbol"></i>
        </div>
        <div>
            <div class="text-2xl font-black text-white"><?= $totalPartidas ?></div>
            <div class="text-xs font-medium text-slate-400 uppercase tracking-wider">Jogos Concluídos</div>
        </div>
    </div>

    <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 flex items-center space-x-4">
        <div class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center text-xl flex-shrink-0">
            <i class="fa-solid fa-shield-check"></i>
        </div>
        <div>
            <div class="text-2xl font-black text-white"><?= $totalLogs ?></div>
            <div class="text-xs font-medium text-slate-400 uppercase tracking-wider">Ações Auditadas</div>
        </div>
    </div>
</div>

<!-- Seção: Torneios Recentes e em Andamento -->
<div class="mb-12">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-white flex items-center">
                <i class="fa-solid fa-fire text-amber-500 mr-2.5"></i> Torneios em Andamento e Recentes
            </h2>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">Acompanhe as chaves das competições cadastradas no sistema</p>
        </div>
        <a href="torneios.php" class="text-xs sm:text-sm font-semibold text-brand-400 hover:text-brand-300 flex items-center transition-colors">
            Ver todos <i class="fa-solid fa-arrow-right ml-1.5 text-xs"></i>
        </a>
    </div>

    <?php if (empty($torneiosDestaque)): ?>
        <div class="bg-slate-900/50 border border-slate-800 rounded-2xl p-10 text-center">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-slate-800 text-slate-500 flex items-center justify-center text-2xl mb-4">
                <i class="fa-solid fa-sitemap"></i>
            </div>
            <h3 class="text-lg font-bold text-white mb-2">Nenhum torneio cadastrado ainda</h3>
            <p class="text-sm text-slate-400 max-w-md mx-auto mb-6">
                Comece agora mesmo criando sua primeira competição esportiva com chaveamento automático de partidas.
            </p>
            <a href="novo_torneio.php" class="inline-flex items-center px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-brand-600 hover:bg-brand-500 transition-colors shadow-lg shadow-brand-500/25">
                <i class="fa-solid fa-plus mr-2"></i> Criar Primeiro Torneio
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($torneiosDestaque as $t): ?>
                <?php
                $progresso = $t['total_partidas'] > 0 ? round(($t['partidas_concluidas'] / $t['total_partidas']) * 100) : 0;
                ?>
                <div class="bg-slate-900 border border-slate-800 hover:border-slate-700 rounded-2xl p-5 flex flex-col justify-between transition-all hover:shadow-xl hover:-translate-y-1 group">
                    <div>
                        <!-- Header do Card -->
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-brand-500/10 text-brand-300 border border-brand-500/20">
                                <i class="fa-solid fa-medal mr-1.5 text-[10px]"></i> <?= htmlspecialchars($t['modalidade']) ?>
                            </span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider
                                <?= $t['status'] === 'finalizado' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : ($t['status'] === 'em_andamento' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-slate-800 text-slate-400 border border-slate-700') ?>">
                                <span class="w-1.5 h-1.5 rounded-full mr-1.5 <?= $t['status'] === 'finalizado' ? 'bg-emerald-400' : ($t['status'] === 'em_andamento' ? 'bg-amber-400 animate-pulse' : 'bg-slate-400') ?>"></span>
                                <?= $t['status'] === 'finalizado' ? 'Finalizado' : ($t['status'] === 'em_andamento' ? 'Em Disputa' : 'Planejado') ?>
                            </span>
                        </div>

                        <!-- Título -->
                        <h3 class="text-lg font-bold text-white group-hover:text-brand-300 transition-colors line-clamp-1 mb-1">
                            <?= htmlspecialchars($t['nome']) ?>
                        </h3>

                        <!-- Info Participantes & Criador -->
                        <div class="text-xs text-slate-400 mb-4 flex items-center space-x-3">
                            <span><i class="fa-solid fa-users text-slate-500 mr-1"></i> <?= $t['total_participantes'] ?> times</span>
                            <span>&bull;</span>
                            <span><i class="fa-solid fa-calendar-day text-slate-500 mr-1"></i> <?= formatarDataSimples($t['criado_em']) ?></span>
                        </div>

                        <!-- Campeão (se houver) -->
                        <?php if (!empty($t['campeao_nome'])): ?>
                            <div class="mb-4 p-2.5 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center space-x-2 text-xs text-amber-300 font-semibold">
                                <i class="fa-solid fa-trophy text-amber-400"></i>
                                <span>Campeão: <?= htmlspecialchars($t['campeao_nome']) ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Barra de Progresso de Partidas -->
                        <div class="mb-5">
                            <div class="flex items-center justify-between text-xs text-slate-400 mb-1.5 font-medium">
                                <span>Jogos realizados</span>
                                <span class="text-slate-300"><?= $t['partidas_concluidas'] ?> / <?= $t['total_partidas'] ?> (<?= $progresso ?>%)</span>
                            </div>
                            <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                                <div class="bg-gradient-to-r from-brand-500 to-emerald-500 h-2 rounded-full transition-all duration-500" style="width: <?= $progresso ?>%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Botão de Ação -->
                    <a href="torneio.php?id=<?= $t['id'] ?>" class="w-full py-2.5 px-4 rounded-xl text-center text-xs font-bold text-white bg-slate-800 hover:bg-brand-600 border border-slate-700 hover:border-brand-500 transition-all flex items-center justify-center group-hover:shadow-lg group-hover:shadow-brand-500/20">
                        <i class="fa-solid fa-sitemap mr-2"></i> Ver Chaveamento Interativo
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Recursos de Destaque / Regras de Negócio do Sistema -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 py-6 border-t border-slate-800/80">
    <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800">
        <div class="w-12 h-12 rounded-xl bg-brand-500/10 text-brand-400 flex items-center justify-center text-xl mb-4">
            <i class="fa-solid fa-sitemap"></i>
        </div>
        <h4 class="text-base font-bold text-white mb-2">Chaveamento Automático</h4>
        <p class="text-xs text-slate-400 leading-relaxed">
            Algoritmo inteligente de chave eliminatória (mata-mata) com tratamento automático de potências de 2, BYEs e avanço imediato para as próximas fases.
        </p>
    </div>

    <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800">
        <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-xl mb-4">
            <i class="fa-solid fa-lock"></i>
        </div>
        <h4 class="text-base font-bold text-white mb-2">Contra Manipulações</h4>
        <p class="text-xs text-slate-400 leading-relaxed">
            Todas as alterações de placar, datas e rodadas são estritamente registradas no log de auditoria com IP, usuário e valores anteriores e novos.
        </p>
    </div>

    <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800">
        <div class="w-12 h-12 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-xl mb-4">
            <i class="fa-solid fa-mobile-screen"></i>
        </div>
        <h4 class="text-base font-bold text-white mb-2">Mobile-First Responsivo</h4>
        <p class="text-xs text-slate-400 leading-relaxed">
            Interface moderna desenvolvida em Tailwind CSS com suporte a toque, arrasto (pan) da chave, zoom e modais nativos integrados sem travamentos.
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
