<?php
/**
 * Script de Demonstração / Seed
 * Cria um torneio exemplo com partidas em andamento e histórico de auditoria
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/backend/helpers.php';
require_once __DIR__ . '/backend/auth.php';
require_once __DIR__ . '/backend/torneios.php';
require_once __DIR__ . '/backend/partidas.php';

// Garante que haja uma sessão de admin
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_nome'] = 'Administrador Geral';
$_SESSION['usuario_email'] = 'admin@etec.sp.gov.br';
$_SESSION['usuario_tipo'] = 'admin';

echo "Inicializando banco e gerando torneio de demonstração...\n";

$torneioDados = [
    'nome' => 'Copa ETEC Esportes 2026 - Futsal',
    'modalidade' => 'Futsal',
    'formato' => 'mata_mata_3lugar',
    'descricao' => 'Campeonato oficial de integração dos cursos técnicos da ETEC. Partidas eliminatórias diretas com disputa de 3º lugar.',
    'tipo_sorteio' => 'aleatorio',
    'disputa_3lugar' => 1,
    'participantes' => [
        '1º ADS - Cyber Knights',
        '2º ADS - Binary Warriors',
        '3º ADS - Code Breakers',
        '1º Logística - Falcões da Rota',
        '2º Logística - Titãs do Galpão',
        '1º RH - Gestão & Vitória',
        '2º RH - Gladiadores Corporativos',
        '3º RH - Águias Douradas'
    ]
];

$res = criarTorneio($torneioDados);

if ($res['sucesso']) {
    $tid = $res['torneio_id'];
    echo "Torneio criado com sucesso! ID: $tid\n";

    // Vamos simular os resultados das Quartas de Final (Rodada 1)
    $db = getDB();
    $stmt = $db->prepare("SELECT id, participante1_id, participante2_id, ordem_partida FROM partidas WHERE torneio_id = :tid AND rodada = 1 ORDER BY ordem_partida ASC");
    $stmt->execute([':tid' => $tid]);
    $partidasR1 = $stmt->fetchAll();

    // Simula placares
    $placares = [
        ['p1' => 4, 'p2' => 2],
        ['p1' => 1, 'p2' => 1, 'pen1' => 5, 'pen2' => 4],
        ['p1' => 3, 'p2' => 0],
        ['p1' => 2, 'p2' => 5]
    ];

    foreach ($partidasR1 as $idx => $partida) {
        $p = $placares[$idx] ?? ['p1' => 2, 'p2' => 1];
        $pen1 = $p['pen1'] ?? null;
        $pen2 = $p['pen2'] ?? null;

        $resPlacar = atualizarPlacarPartida($partida['id'], $p['p1'], $p['p2'], $pen1, $pen2);
        echo "Partida R1 Jogo #" . $partida['ordem_partida'] . " atualizada: " . $resPlacar['mensagem'] . "\n";
    }

    echo "Seed finalizado com sucesso!\n";
} else {
    echo "Erro ao criar torneio: " . $res['mensagem'] . "\n";
}
