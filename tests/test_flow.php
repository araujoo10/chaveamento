<?php
/**
 * Teste Automatizado de Fluxo Completo:
 * 1. Simulação de atualização de placares das Semifinais
 * 2. Simulação de disputa de 3º lugar
 * 3. Simulação da Grande Final
 * 4. Finalização Oficial do Torneio
 * 5. Verificação do Pódio e Logs de Auditoria
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/backend/helpers.php';
require_once __DIR__ . '/backend/partidas.php';
require_once __DIR__ . '/backend/torneios.php';
require_once __DIR__ . '/backend/logs.php';

$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_nome'] = 'Administrador Geral';
$_SESSION['usuario_tipo'] = 'admin';

$db = getDB();

echo "--- 1. Atualizando Semifinais (Rodada 2) ---\n";
$semis = $db->query("SELECT id, ordem_partida FROM partidas WHERE torneio_id = 1 AND rodada = 2 ORDER BY ordem_partida ASC")->fetchAll();

$r1 = atualizarPlacarPartida($semis[0]['id'], 3, 1);
echo "Semi 1 (Partida #{$semis[0]['id']}): " . $r1['mensagem'] . "\n";

$r2 = atualizarPlacarPartida($semis[1]['id'], 2, 4);
echo "Semi 2 (Partida #{$semis[1]['id']}): " . $r2['mensagem'] . "\n";

echo "\n--- 2. Atualizando Disputa de 3º Lugar ---\n";
$p3 = $db->query("SELECT id FROM partidas WHERE torneio_id = 1 AND nome_rodada = 'Disputa de 3º Lugar'")->fetch();
if ($p3) {
    $r3 = atualizarPlacarPartida($p3['id'], 3, 2);
    echo "Disputa 3º Lugar (Partida #{$p3['id']}): " . $r3['mensagem'] . "\n";
}

echo "\n--- 3. Atualizando Grande Final ---\n";
$final = $db->query("SELECT id FROM partidas WHERE torneio_id = 1 AND nome_rodada = 'Grande Final'")->fetch();
$rFinal = atualizarPlacarPartida($final['id'], 4, 2);
echo "Grande Final (Partida #{$final['id']}): " . $rFinal['mensagem'] . "\n";

echo "\n--- 4. Finalizando Oficialmente o Torneio ---\n";
$rFin = finalizarTorneio(1);
echo "Finalizacao: " . $rFin['mensagem'] . "\n";

$t = obterTorneio(1);
echo "\n--- 5. Resultado Consolidado do Torneio ---\n";
echo "Nome do Torneio: " . $t['nome'] . "\n";
echo "Status: " . $t['status'] . "\n";
echo "1º Lugar (Campeão): " . $t['campeao_nome'] . "\n";
echo "2º Lugar (Vice): " . $t['vice_nome'] . "\n";
echo "3º Lugar (Bronze): " . $t['terceiro_nome'] . "\n";

echo "\n--- 6. Verificando Logs de Auditoria Gravados ---\n";
$logs = $db->query("SELECT id, acao, descricao, criado_em FROM logs_auditoria ORDER BY id DESC LIMIT 5")->fetchAll();
foreach ($logs as $l) {
    echo "#{$l['id']} [{$l['acao']}] {$l['descricao']}\n";
}
