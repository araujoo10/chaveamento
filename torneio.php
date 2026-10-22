<?php
/**
 * Visualização Interativa do Torneio e Chaveamento
 * Inspiração: BracketMaker.app
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/backend/helpers.php';
require_once __DIR__ . '/backend/torneios.php';
require_once __DIR__ . '/backend/partidas.php';

$torneioId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$torneio = obterTorneio($torneioId);

if (!$torneio) {
    adicionarToast('error', 'Torneio não encontrado.');
    header('Location: torneios.php');
    exit;
}

$pageTitle = $torneio['nome'];

// Obtém as rodadas e partidas
$rodadas = obterPartidasDoTorneio($torneioId);
$participantes = obterParticipantesTorneio($torneioId);

$usuario = getUsuarioAtual();
$podeEditarPlacar = isAdmin() || ($usuario && $usuario['id'] == $torneio['criado_por']);

// Identifica se a partida final já possui vencedor para habilitar o botão de finalizar
$finalConcluida = false;
$partidaFinalId = null;
foreach ($rodadas as $r) {
    foreach ($r['partidas'] as $p) {
        if ($p['nome_rodada'] === 'Grande Final' && !empty($p['vencedor_id'])) {
            $finalConcluida = true;
            $partidaFinalId = $p['id'];
        }
    }
}

$progresso = $torneio['total_partidas'] > 0 ? round(($torneio['partidas_concluidas'] / $torneio['total_partidas']) * 100) : 0;

require_once __DIR__ . '/includes/header.php';
?>

<!-- Cabeçalho do Torneio -->
<div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 mb-8 shadow-xl">
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
        <div>
            <!-- Badges Superiores -->
            <div class="flex flex-wrap items-center gap-2 mb-3">
                <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-bold bg-brand-500/10 text-brand-300 border border-brand-500/20">
                    <i class="fa-solid fa-medal mr-1.5 text-xs"></i> <?= htmlspecialchars($torneio['modalidade']) ?>
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider
                    <?= $torneio['status'] === 'finalizado' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : ($torneio['status'] === 'em_andamento' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-slate-800 text-slate-400 border border-slate-700') ?>">
                    <span class="w-2 h-2 rounded-full mr-2 <?= $torneio['status'] === 'finalizado' ? 'bg-emerald-400' : ($torneio['status'] === 'em_andamento' ? 'bg-amber-400 animate-pulse' : 'bg-slate-400') ?>"></span>
                    <?= $torneio['status'] === 'finalizado' ? 'Torneio Finalizado' : ($torneio['status'] === 'em_andamento' ? 'Em Disputa' : 'Planejado') ?>
                </span>
                <?php if ($torneio['formato'] === 'mata_mata_3lugar'): ?>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-[11px] font-semibold bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
                        <i class="fa-solid fa-award mr-1"></i> Com Disputa de Bronze
                    </span>
                <?php endif; ?>
            </div>

            <!-- Título & Descrição -->
            <h1 class="text-2xl sm:text-4xl font-extrabold text-white tracking-tight mb-2">
                <?= htmlspecialchars($torneio['nome']) ?>
            </h1>
            <?php if (!empty($torneio['descricao'])): ?>
                <p class="text-xs sm:text-sm text-slate-300 max-w-2xl leading-relaxed mb-3">
                    <?= nl2br(htmlspecialchars($torneio['descricao'])) ?>
                </p>
            <?php endif; ?>

            <!-- Metadados -->
            <div class="flex flex-wrap items-center gap-y-2 gap-x-4 text-xs text-slate-400">
                <span><i class="fa-solid fa-user-circle text-slate-500 mr-1"></i> Criado por <strong><?= htmlspecialchars($torneio['criador_nome'] ?? 'Admin') ?></strong></span>
                <span>&bull;</span>
                <span><i class="fa-solid fa-users text-slate-500 mr-1"></i> <strong><?= $torneio['total_participantes'] ?></strong> Equipes</span>
                <span>&bull;</span>
                <span><i class="fa-solid fa-calendar-alt text-slate-500 mr-1"></i> <?= formatarDataSimples($torneio['criado_em']) ?></span>
                <span>&bull;</span>
                <span><i class="fa-solid fa-chart-pie text-slate-500 mr-1"></i> <?= $torneio['partidas_concluidas'] ?>/<?= $torneio['total_partidas'] ?> Jogos (<?= $progresso ?>%)</span>
            </div>
        </div>

        <!-- Ações do Torneio -->
        <div class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto">
            <a href="historico.php?torneio_id=<?= $torneio['id'] ?>" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 border border-slate-700 transition-colors flex items-center">
                <i class="fa-solid fa-shield-halved mr-2 text-emerald-400"></i> Logs & Auditoria
            </a>

            <?php if ($podeEditarPlacar): ?>
                <?php if ($torneio['status'] !== 'finalizado'): ?>
                    <button type="button" onclick="confirmarFinalizacao(<?= $torneio['id'] ?>, <?= $finalConcluida ? 'true' : 'false' ?>)" class="px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 shadow-lg shadow-emerald-500/20 transition-all flex items-center">
                        <i class="fa-solid fa-flag-checkered mr-2"></i> Finalizar Torneio
                    </button>
                <?php else: ?>
                    <?php if (isAdmin()): ?>
                        <button type="button" onclick="confirmarReabertura(<?= $torneio['id'] ?>)" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-amber-300 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/30 transition-colors flex items-center">
                            <i class="fa-solid fa-rotate-left mr-2"></i> Reabrir Torneio
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Banner do Pódio Final (se torneio estiver finalizado) -->
<?php if ($torneio['status'] === 'finalizado' && !empty($torneio['campeao_nome'])): ?>
    <div class="mb-8 p-6 sm:p-8 rounded-3xl bg-gradient-to-r from-amber-950/60 via-slate-900 to-amber-950/60 border border-amber-500/40 shadow-2xl relative overflow-hidden text-center">
        <div class="absolute -top-12 left-1/2 transform -translate-x-1/2 w-80 h-32 bg-amber-500/20 rounded-full blur-2xl pointer-events-none"></div>
        <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-amber-500/20 text-amber-300 text-xs font-bold uppercase tracking-wider mb-4 border border-amber-500/30">
            <i class="fa-solid fa-crown text-amber-400"></i> Pódio Oficial Consolidado
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 max-w-3xl mx-auto items-end pt-2">
            <!-- 2º Lugar (Prata) -->
            <div class="bg-slate-900/80 border border-slate-700/80 rounded-2xl p-4 order-2 sm:order-1">
                <div class="w-12 h-12 mx-auto rounded-full bg-slate-700 text-slate-200 flex items-center justify-center text-xl font-black mb-2 shadow-lg">
                    <i class="fa-solid fa-medal"></i>
                </div>
                <div class="text-[11px] uppercase tracking-wider text-slate-400 font-bold mb-1">2º Lugar (Vice-Campeão)</div>
                <div class="text-base font-bold text-slate-100"><?= htmlspecialchars($torneio['vice_nome'] ?? 'Não definido') ?></div>
            </div>

            <!-- 1º Lugar (Ouro / Campeão) -->
            <div class="bg-gradient-to-b from-amber-500/20 to-slate-900 border-2 border-amber-500/60 rounded-3xl p-6 order-1 sm:order-2 transform sm:-translate-y-3 shadow-2xl shadow-amber-500/20">
                <div class="w-16 h-16 mx-auto rounded-full bg-gradient-to-tr from-amber-500 to-yellow-300 text-slate-950 flex items-center justify-center text-2xl font-black mb-2 shadow-xl shadow-amber-500/40 animate-bounce">
                    <i class="fa-solid fa-trophy"></i>
                </div>
                <div class="text-xs uppercase tracking-wider text-amber-300 font-extrabold mb-1">Grande Campeão</div>
                <div class="text-xl sm:text-2xl font-black text-white"><?= htmlspecialchars($torneio['campeao_nome']) ?></div>
            </div>

            <!-- 3º Lugar (Bronze) -->
            <div class="bg-slate-900/80 border border-slate-700/80 rounded-2xl p-4 order-3">
                <div class="w-12 h-12 mx-auto rounded-full bg-amber-900/60 text-amber-400 border border-amber-600/40 flex items-center justify-center text-xl font-black mb-2 shadow-lg">
                    <i class="fa-solid fa-award"></i>
                </div>
                <div class="text-[11px] uppercase tracking-wider text-slate-400 font-bold mb-1">3º Lugar (Bronze)</div>
                <div class="text-base font-bold text-slate-100"><?= htmlspecialchars($torneio['terceiro_nome'] ?? 'Não disputado') ?></div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Barra de Controles da Chave (Zoom, Pan, Alternar Visão) -->
<div class="bg-slate-900 border border-slate-800 rounded-2xl p-3 mb-6 flex flex-wrap items-center justify-between gap-3">
    <!-- Alternar Visão -->
    <div class="flex items-center space-x-1 bg-slate-950/80 p-1 rounded-xl border border-slate-800">
        <button type="button" id="btn-tab-arvore" onclick="alternarVisao('arvore')" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-brand-600 text-white shadow-md shadow-brand-500/20">
            <i class="fa-solid fa-sitemap mr-1.5"></i> Chave Gráfica
        </button>
        <button type="button" id="btn-tab-lista" onclick="alternarVisao('lista')" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-400 hover:text-white transition-colors">
            <i class="fa-solid fa-list-ol mr-1.5"></i> Lista de Partidas
        </button>
    </div>

    <!-- Controles de Zoom da Árvore -->
    <div class="flex items-center space-x-2">
        <span class="text-xs text-slate-400 hidden sm:inline mr-1">
            <i class="fa-solid fa-hand-pointer mr-1"></i> Arraste para navegar
        </span>
        <div class="flex items-center space-x-1 bg-slate-950/80 p-1 rounded-xl border border-slate-800">
            <button type="button" id="btn-zoom-out" title="Diminuir Zoom" class="w-7 h-7 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 flex items-center justify-center text-xs">
                <i class="fa-solid fa-minus"></i>
            </button>
            <span id="label-zoom" class="text-xs font-semibold text-slate-300 px-2 min-w-[45px] text-center">100%</span>
            <button type="button" id="btn-zoom-in" title="Aumentar Zoom" class="w-7 h-7 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 flex items-center justify-center text-xs">
                <i class="fa-solid fa-plus"></i>
            </button>
            <button type="button" id="btn-zoom-reset" title="Restaurar Visão Padrão" class="w-7 h-7 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 flex items-center justify-center text-xs ml-1">
                <i class="fa-solid fa-rotate"></i>
            </button>
        </div>
    </div>
</div>

<!-- =======================================================
     VISÃO 1: ÁRVORE GRÁFICA / BRACKET (Estilo BracketMaker)
     ======================================================= -->
<div id="visao-arvore" class="bracket-wrapper shadow-2xl">
    <div id="bracket-viewport" class="bracket-viewport">
        <div id="bracket-tree" class="bracket-tree">
            <?php
            // Filtra rodadas normais (excluindo a disputa de 3º lugar, tratada separadamente)
            $rodadasChave = array_filter($rodadas, function ($r) {
                return $r['nome'] !== 'Disputa de 3º Lugar';
            });
            ksort($rodadasChave);
            ?>

            <?php foreach ($rodadasChave as $numRodada => $dadosRodada): ?>
                <div class="bracket-round">
                    <!-- Cabeçalho da Rodada -->
                    <div class="bracket-round-header">
                        <div class="text-xs font-bold text-white uppercase tracking-wider">
                            <?= htmlspecialchars($dadosRodada['nome']) ?>
                        </div>
                        <div class="text-[11px] text-slate-400 mt-0.5">
                            <?= count($dadosRodada['partidas']) ?> <?= count($dadosRodada['partidas']) === 1 ? 'confronto' : 'confrontos' ?>
                        </div>
                    </div>

                    <!-- Coluna de Partidas da Rodada -->
                    <div class="bracket-matches-column">
                        <?php foreach ($dadosRodada['partidas'] as $p): ?>
                            <?php
                            $isBye = ($p['status'] === 'bye');
                            $isFinalizada = ($p['status'] === 'finalizada');
                            $time1Venceu = (!empty($p['vencedor_id']) && $p['vencedor_id'] == $p['participante1_id']);
                            $time2Venceu = (!empty($p['vencedor_id']) && $p['vencedor_id'] == $p['participante2_id']);
                            $podeLancar = $podeEditarPlacar && !$isBye && !empty($p['participante1_id']) && !empty($p['participante2_id']) && ($torneio['status'] !== 'finalizado');
                            ?>

                            <div class="bracket-match-card <?= $isBye ? 'bye-card' : '' ?> <?= $isFinalizada ? 'finalizada' : '' ?>">
                                <!-- Header do Card de Partida -->
                                <div class="px-3 py-1.5 bg-slate-950/80 border-b border-slate-800 flex items-center justify-between text-[11px]">
                                    <span class="font-bold text-slate-400">
                                        Jogo #<?= $p['ordem_partida'] ?>
                                    </span>
                                    <span class="match-status-badge 
                                        <?= $isBye ? 'bg-indigo-500/10 text-indigo-400' : ($isFinalizada ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-800 text-slate-400') ?>">
                                        <?= $isBye ? 'BYE (Isenção)' : ($isFinalizada ? 'Concluída' : 'Pendente') ?>
                                    </span>
                                </div>

                                <!-- Time 1 -->
                                <div class="team-row <?= $time1Venceu ? 'vencedor' : ($time2Venceu ? 'perdedor' : '') ?>">
                                    <div class="flex items-center min-w-0 flex-1">
                                        <?php if (!empty($p['time1_semente'])): ?>
                                            <span class="team-seed">#<?= $p['time1_semente'] ?></span>
                                        <?php endif; ?>
                                        <span class="team-name" title="<?= htmlspecialchars($p['time1_nome'] ?? 'Aguardando vencedor...') ?>">
                                            <?php if ($time1Venceu): ?>
                                                <i class="fa-solid fa-trophy text-amber-400 text-xs mr-1"></i>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($p['time1_nome'] ?? 'Aguardando...') ?>
                                        </span>
                                    </div>
                                    <span class="team-score">
                                        <?= ($p['placar1'] !== null) ? $p['placar1'] : '-' ?>
                                    </span>
                                </div>

                                <!-- Time 2 -->
                                <div class="team-row <?= $time2Venceu ? 'vencedor' : ($time1Venceu ? 'perdedor' : '') ?>">
                                    <div class="flex items-center min-w-0 flex-1">
                                        <?php if (!empty($p['time2_semente'])): ?>
                                            <span class="team-seed">#<?= $p['time2_semente'] ?></span>
                                        <?php endif; ?>
                                        <span class="team-name" title="<?= htmlspecialchars($p['time2_nome'] ?? 'Aguardando vencedor...') ?>">
                                            <?php if ($time2Venceu): ?>
                                                <i class="fa-solid fa-trophy text-amber-400 text-xs mr-1"></i>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($p['time2_nome'] ?? 'Aguardando...') ?>
                                        </span>
                                    </div>
                                    <span class="team-score">
                                        <?= ($p['placar2'] !== null) ? $p['placar2'] : '-' ?>
                                    </span>
                                </div>

                                <!-- Seção de Rodapé do Card (Pênaltis & Botão de Placar) -->
                                <?php if ($p['penaltis1'] !== null && $p['penaltis2'] !== null): ?>
                                    <div class="px-3 py-1 bg-amber-500/10 text-amber-300 text-[10px] text-center font-semibold border-t border-amber-500/20">
                                        Pênaltis: <?= $p['penaltis1'] ?> x <?= $p['penaltis2'] ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($podeLancar): ?>
                                    <div class="p-1.5 bg-slate-950/60 border-t border-slate-800">
                                        <button type="button" onclick='abrirModalPlacar(<?= json_encode($p, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' class="w-full py-1 px-2 rounded-lg text-[11px] font-bold text-slate-300 hover:text-white bg-slate-800 hover:bg-brand-600 border border-slate-700 hover:border-brand-500 transition-colors flex items-center justify-center">
                                            <i class="fa-solid fa-pen-to-square mr-1 text-[10px]"></i> <?= $isFinalizada ? 'Ajustar Placar' : 'Lançar Placar' ?>
                                        </button>
                                    </div>
                                <?php endif; ?>

                                <!-- Conector com a próxima rodada -->
                                <?php if (!empty($p['proxima_partida_id'])): ?>
                                    <div class="bracket-connector-right"></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Disputa de 3º Lugar na árvore (se houver) -->
            <?php
            $partida3Lugar = null;
            foreach ($rodadas as $r) {
                if ($r['nome'] === 'Disputa de 3º Lugar') {
                    $partida3Lugar = $r['partidas'][0] ?? null;
                    break;
                }
            }
            ?>
            <?php if ($partida3Lugar): ?>
                <div class="bracket-round">
                    <div class="bracket-round-header border-amber-500/40 bg-amber-950/20">
                        <div class="text-xs font-bold text-amber-400 uppercase tracking-wider flex items-center justify-center">
                            <i class="fa-solid fa-award mr-1.5"></i> 3º Lugar (Bronze)
                        </div>
                        <div class="text-[11px] text-slate-400 mt-0.5">Disputa de Medalha</div>
                    </div>

                    <div class="bracket-matches-column justify-center">
                        <?php
                        $p = $partida3Lugar;
                        $isFinalizada = ($p['status'] === 'finalizada');
                        $t1V = (!empty($p['vencedor_id']) && $p['vencedor_id'] == $p['participante1_id']);
                        $t2V = (!empty($p['vencedor_id']) && $p['vencedor_id'] == $p['participante2_id']);
                        $podeLancar3 = $podeEditarPlacar && !empty($p['participante1_id']) && !empty($p['participante2_id']) && ($torneio['status'] !== 'finalizado');
                        ?>
                        <div class="bracket-match-card <?= $isFinalizada ? 'finalizada' : '' ?> border-amber-500/40">
                            <div class="px-3 py-1.5 bg-slate-950/80 border-b border-slate-800 flex items-center justify-between text-[11px]">
                                <span class="font-bold text-amber-400">Decisão Bronze</span>
                                <span class="match-status-badge <?= $isFinalizada ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-800 text-slate-400' ?>">
                                    <?= $isFinalizada ? 'Concluída' : 'Pendente' ?>
                                </span>
                            </div>

                            <div class="team-row <?= $t1V ? 'vencedor' : ($t2V ? 'perdedor' : '') ?>">
                                <div class="flex items-center min-w-0 flex-1">
                                    <span class="team-name" title="<?= htmlspecialchars($p['time1_nome'] ?? 'Perdedor Semi 1') ?>">
                                        <?php if ($t1V): ?><i class="fa-solid fa-award text-amber-400 mr-1 text-xs"></i><?php endif; ?>
                                        <?= htmlspecialchars($p['time1_nome'] ?? 'Perdedor Semi 1') ?>
                                    </span>
                                </div>
                                <span class="team-score"><?= ($p['placar1'] !== null) ? $p['placar1'] : '-' ?></span>
                            </div>

                            <div class="team-row <?= $t2V ? 'vencedor' : ($t1V ? 'perdedor' : '') ?>">
                                <div class="flex items-center min-w-0 flex-1">
                                    <span class="team-name" title="<?= htmlspecialchars($p['time2_nome'] ?? 'Perdedor Semi 2') ?>">
                                        <?php if ($t2V): ?><i class="fa-solid fa-award text-amber-400 mr-1 text-xs"></i><?php endif; ?>
                                        <?= htmlspecialchars($p['time2_nome'] ?? 'Perdedor Semi 2') ?>
                                    </span>
                                </div>
                                <span class="team-score"><?= ($p['placar2'] !== null) ? $p['placar2'] : '-' ?></span>
                            </div>

                            <?php if ($p['penaltis1'] !== null && $p['penaltis2'] !== null): ?>
                                <div class="px-3 py-1 bg-amber-500/10 text-amber-300 text-[10px] text-center font-semibold border-t border-amber-500/20">
                                    Pênaltis: <?= $p['penaltis1'] ?> x <?= $p['penaltis2'] ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($podeLancar3): ?>
                                <div class="p-1.5 bg-slate-950/60 border-t border-slate-800">
                                    <button type="button" onclick='abrirModalPlacar(<?= json_encode($p, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' class="w-full py-1 px-2 rounded-lg text-[11px] font-bold text-amber-300 hover:text-white bg-amber-500/10 hover:bg-amber-600 border border-amber-500/30 transition-colors flex items-center justify-center">
                                        <i class="fa-solid fa-pen-to-square mr-1 text-[10px]"></i> <?= $isFinalizada ? 'Ajustar Placar' : 'Lançar Placar' ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- =======================================================
     VISÃO 2: TABELA / LISTA DE PARTIDAS
     ======================================================= -->
<div id="visao-lista" class="hidden space-y-6">
    <?php foreach ($rodadas as $numRodada => $dadosRodada): ?>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-lg">
            <div class="px-6 py-3.5 bg-slate-950/80 border-b border-slate-800 flex items-center justify-between">
                <h3 class="text-sm font-bold text-white flex items-center">
                    <span class="w-2 h-2 rounded-full bg-brand-500 mr-2"></span>
                    <?= htmlspecialchars($dadosRodada['nome']) ?>
                </h3>
                <span class="text-xs text-slate-400">
                    <?= count($dadosRodada['partidas']) ?> jogos
                </span>
            </div>

            <div class="divide-y divide-slate-800">
                <?php foreach ($dadosRodada['partidas'] as $p): ?>
                    <?php
                    $isBye = ($p['status'] === 'bye');
                    $isFin = ($p['status'] === 'finalizada');
                    $t1V = (!empty($p['vencedor_id']) && $p['vencedor_id'] == $p['participante1_id']);
                    $t2V = (!empty($p['vencedor_id']) && $p['vencedor_id'] == $p['participante2_id']);
                    $podeLancar = $podeEditarPlacar && !$isBye && !empty($p['participante1_id']) && !empty($p['participante2_id']) && ($torneio['status'] !== 'finalizado');
                    ?>
                    <div class="p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 hover:bg-slate-800/40 transition-colors">
                        <div class="flex items-center space-x-3">
                            <span class="text-xs font-bold text-slate-500 w-14">Jogo #<?= $p['ordem_partida'] ?></span>
                            <span class="match-status-badge <?= $isBye ? 'bg-indigo-500/10 text-indigo-400' : ($isFin ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-800 text-slate-400') ?>">
                                <?= $isBye ? 'BYE' : ($isFin ? 'Concluída' : 'Pendente') ?>
                            </span>
                        </div>

                        <!-- Confronto -->
                        <div class="flex-1 flex items-center justify-center space-x-4 w-full sm:w-auto">
                            <!-- Time 1 -->
                            <div class="flex-1 text-right <?= $t1V ? 'font-bold text-emerald-400' : 'text-slate-300' ?>">
                                <?= htmlspecialchars($p['time1_nome'] ?? 'Aguardando...') ?>
                                <?php if ($t1V): ?><i class="fa-solid fa-check ml-1 text-xs"></i><?php endif; ?>
                            </div>

                            <!-- Placar Central -->
                            <div class="px-3 py-1 bg-slate-950 rounded-lg border border-slate-700 text-sm font-black text-white whitespace-nowrap">
                                <?= ($p['placar1'] !== null) ? $p['placar1'] : '-' ?>
                                <span class="text-slate-500 font-normal mx-1">x</span>
                                <?= ($p['placar2'] !== null) ? $p['placar2'] : '-' ?>
                            </div>

                            <!-- Time 2 -->
                            <div class="flex-1 text-left <?= $t2V ? 'font-bold text-emerald-400' : 'text-slate-300' ?>">
                                <?php if ($t2V): ?><i class="fa-solid fa-check mr-1 text-xs"></i><?php endif; ?>
                                <?= htmlspecialchars($p['time2_nome'] ?? 'Aguardando...') ?>
                            </div>
                        </div>

                        <!-- Ação -->
                        <div class="w-full sm:w-auto flex justify-end">
                            <?php if ($podeLancar): ?>
                                <button type="button" onclick='abrirModalPlacar(<?= json_encode($p, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' class="px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-slate-800 hover:bg-brand-600 border border-slate-700 transition-colors">
                                    <i class="fa-solid fa-pen-to-square mr-1"></i> Placar
                                </button>
                            <?php else: ?>
                                <span class="text-xs text-slate-500">—</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script src="assets/js/bracket.js"></script>
<script>
function confirmarFinalizacao(torneioId, finalConcluida) {
    if (!finalConcluida) {
        mostrarToast('warning', 'A Grande Final precisa ser realizada e possuir um vencedor antes de encerrar o torneio.', 'Final Pendente');
        return;
    }

    confirmarAcao(
        'Tem certeza que deseja declarar este torneio como oficialmente <strong>FINALIZADO</strong>? O campeão será coroado e os placares serão bloqueados.',
        () => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'finalizar_torneio.php';
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'torneio_id';
            input.value = torneioId;
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        },
        'Finalizar Competição',
        'success'
    );
}

function confirmarReabertura(torneioId) {
    confirmarAcao(
        'Deseja reabrir este campeonato para edição de partidas? Esta operação de administrador será registrada no log de auditoria.',
        () => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'reabrir_torneio.php';
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'torneio_id';
            input.value = torneioId;
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        },
        'Reabrir Campeonato',
        'warning'
    );
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
