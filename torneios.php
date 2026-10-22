<?php
/**
 * Listagem de Torneios Esportivos
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/backend/helpers.php';
require_once __DIR__ . '/backend/torneios.php';

$pageTitle = 'Todos os Torneios';

$filtroStatus = !empty($_GET['status']) ? limparInput($_GET['status']) : null;
$filtroBusca = !empty($_GET['busca']) ? limparInput($_GET['busca']) : null;

$torneios = listarTorneios($filtroStatus, $filtroBusca);

require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">
    <!-- Cabeçalho & Botão Criar -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-6 border-b border-slate-800">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight flex items-center">
                <i class="fa-solid fa-trophy text-amber-400 mr-3 text-2xl"></i> Torneios Cadastrados
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">Gerencie chaves, acompanhe partidas e veja os campeões</p>
        </div>
        <a href="novo_torneio.php" class="px-5 py-3 rounded-xl font-bold text-xs sm:text-sm text-white bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 shadow-lg shadow-brand-500/25 transition-all flex items-center">
            <i class="fa-solid fa-circle-plus mr-2"></i> Criar Torneio
        </a>
    </div>

    <!-- Barra de Filtros e Busca -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
        <!-- Status Tabs -->
        <div class="flex items-center space-x-1 overflow-x-auto pb-1 md:pb-0">
            <a href="torneios.php<?= $filtroBusca ? '?busca=' . urlencode($filtroBusca) : '' ?>" class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors <?= empty($filtroStatus) ? 'bg-brand-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800' ?>">
                Todos
            </a>
            <a href="torneios.php?status=em_andamento<?= $filtroBusca ? '&busca=' . urlencode($filtroBusca) : '' ?>" class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors <?= $filtroStatus === 'em_andamento' ? 'bg-brand-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800' ?>">
                Em Andamento
            </a>
            <a href="torneios.php?status=finalizado<?= $filtroBusca ? '&busca=' . urlencode($filtroBusca) : '' ?>" class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors <?= $filtroStatus === 'finalizado' ? 'bg-brand-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800' ?>">
                Finalizados
            </a>
            <a href="torneios.php?status=planejado<?= $filtroBusca ? '&busca=' . urlencode($filtroBusca) : '' ?>" class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors <?= $filtroStatus === 'planejado' ? 'bg-brand-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800' ?>">
                Planejados
            </a>
        </div>

        <!-- Campo de Busca -->
        <form method="GET" action="torneios.php" class="relative min-w-[260px]">
            <?php if ($filtroStatus): ?>
                <input type="hidden" name="status" value="<?= htmlspecialchars($filtroStatus) ?>">
            <?php endif; ?>
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500 text-xs">
                <i class="fa-solid fa-magnifying-glass"></i>
            </span>
            <input type="text" name="busca" value="<?= htmlspecialchars($filtroBusca ?? '') ?>" placeholder="Buscar torneio ou modalidade..."
                class="w-full pl-9 pr-8 py-2 bg-slate-950 border border-slate-700/80 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-brand-500 transition-colors">
            <?php if ($filtroBusca): ?>
                <a href="torneios.php<?= $filtroStatus ? '?status=' . urlencode($filtroStatus) : '' ?>" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-500 hover:text-white text-xs">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Lista de Torneios -->
    <?php if (empty($torneios)): ?>
        <div class="bg-slate-900/50 border border-slate-800 rounded-2xl p-12 text-center">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-slate-800 text-slate-500 flex items-center justify-center text-2xl mb-4">
                <i class="fa-solid fa-trophy"></i>
            </div>
            <h3 class="text-lg font-bold text-white mb-2">Nenhum torneio encontrado</h3>
            <p class="text-xs text-slate-400 max-w-sm mx-auto mb-6">
                Não foram localizados campeonatos correspondentes aos filtros selecionados.
            </p>
            <a href="novo_torneio.php" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold text-white bg-brand-600 hover:bg-brand-500 transition-colors">
                <i class="fa-solid fa-plus mr-1.5"></i> Cadastrar Novo Torneio
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($torneios as $t): ?>
                <?php
                $progresso = $t['total_partidas'] > 0 ? round(($t['partidas_concluidas'] / $t['total_partidas']) * 100) : 0;
                ?>
                <div class="bg-slate-900 border border-slate-800 hover:border-slate-700 rounded-2xl p-5 flex flex-col justify-between transition-all hover:shadow-xl hover:-translate-y-0.5 group">
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

                        <!-- Info Detalhes -->
                        <div class="text-xs text-slate-400 mb-4 flex items-center space-x-3">
                            <span><i class="fa-solid fa-users text-slate-500 mr-1"></i> <?= $t['total_participantes'] ?> times</span>
                            <span>&bull;</span>
                            <span><i class="fa-solid fa-calendar-day text-slate-500 mr-1"></i> <?= formatarDataSimples($t['criado_em']) ?></span>
                        </div>

                        <!-- Campeão se houver -->
                        <?php if (!empty($t['campeao_nome'])): ?>
                            <div class="mb-4 p-2.5 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center space-x-2 text-xs text-amber-300 font-semibold">
                                <i class="fa-solid fa-trophy text-amber-400"></i>
                                <span>Campeão: <?= htmlspecialchars($t['campeao_nome']) ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Barra de Progresso -->
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

                    <!-- Ações -->
                    <div class="flex items-center space-x-2 pt-2 border-t border-slate-800/80">
                        <a href="torneio.php?id=<?= $t['id'] ?>" class="flex-1 py-2.5 px-3 rounded-xl text-center text-xs font-bold text-white bg-slate-800 hover:bg-brand-600 border border-slate-700 hover:border-brand-500 transition-all flex items-center justify-center">
                            <i class="fa-solid fa-sitemap mr-1.5"></i> Chaveamento
                        </a>
                        <a href="historico.php?torneio_id=<?= $t['id'] ?>" title="Auditoria deste Torneio" class="p-2.5 rounded-xl text-slate-400 hover:text-emerald-400 bg-slate-800 hover:bg-slate-700 border border-slate-700 transition-colors">
                            <i class="fa-solid fa-shield-halved text-xs"></i>
                        </a>
                        <?php if (isAdmin()): ?>
                            <button type="button" onclick="confirmarExclusao(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['nome'])) ?>')" title="Excluir Torneio" class="p-2.5 rounded-xl text-slate-400 hover:text-rose-400 bg-slate-800 hover:bg-rose-500/10 border border-slate-700 hover:border-rose-500/30 transition-colors">
                                <i class="fa-solid fa-trash-can text-xs"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function confirmarExclusao(torneioId, nome) {
    confirmarAcao(
        `Tem certeza que deseja excluir permanentemente o torneio <strong>"${escapeHtml(nome)}"</strong>? Todas as partidas, chaves e participantes vinculados serão apagados. Esta ação será auditada nos logs.`,
        () => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'excluir_torneio.php';
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'torneio_id';
            input.value = torneioId;
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        },
        'Excluir Competição',
        'danger'
    );
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
