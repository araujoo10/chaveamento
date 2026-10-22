<?php
/**
 * Módulo de Partidas e Gerenciamento de Placares
 * Regras de avanço automático, desempates e auditoria rigorosa
 */

require_once __DIR__ . '/../config/bd.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/logs.php';
require_once __DIR__ . '/chaveamento.php';

/**
 * Atualiza o placar de uma partida e avança os classificados
 */
function atualizarPlacarPartida(
    int $partidaId,
    int $placar1,
    int $placar2,
    ?int $penaltis1 = null,
    ?int $penaltis2 = null
): array {
    if (!usuarioLogado()) {
        return ['sucesso' => false, 'mensagem' => 'Acesso negado. Faça login como administrador.'];
    }

    $db = getDB();

    // Busca detalhes da partida atual e dos participantes
    $stmt = $db->prepare("
        SELECT p.*, 
               t1.nome as time1_nome, 
               t2.nome as time2_nome,
               t.nome as torneio_nome,
               t.status as torneio_status,
               t.formato as torneio_formato
        FROM partidas p
        JOIN torneios t ON p.torneio_id = t.id
        LEFT JOIN participantes t1 ON p.participante1_id = t1.id
        LEFT JOIN participantes t2 ON p.participante2_id = t2.id
        WHERE p.id = :id
    ");
    $stmt->execute([':id' => $partidaId]);
    $partida = $stmt->fetch();

    if (!$partida) {
        return ['sucesso' => false, 'mensagem' => 'Partida não encontrada.'];
    }

    if ($partida['torneio_status'] === 'finalizado') {
        return ['sucesso' => false, 'mensagem' => 'Este torneio já foi finalizado. Não é permitido alterar placares sem reabri-lo.'];
    }

    if (empty($partida['participante1_id']) || empty($partida['participante2_id'])) {
        return ['sucesso' => false, 'mensagem' => 'Ambos os participantes devem estar definidos para lançar o placar.'];
    }

    if ($placar1 < 0 || $placar2 < 0) {
        return ['sucesso' => false, 'mensagem' => 'Os placares não podem ser negativos.'];
    }

    // Validação de empate em mata-mata
    $vencedorId = null;
    $perdedorId = null;

    if ($placar1 > $placar2) {
        $vencedorId = (int)$partida['participante1_id'];
        $perdedorId = (int)$partida['participante2_id'];
    } elseif ($placar2 > $placar1) {
        $vencedorId = (int)$partida['participante2_id'];
        $perdedorId = (int)$partida['participante1_id'];
    } else {
        // Empate no placar normal: requer desempate / pênaltis
        if ($penaltis1 === null || $penaltis2 === null || $penaltis1 === $penaltis2) {
            return [
                'sucesso' => false, 
                'mensagem' => 'Em partidas eliminatórias, o empate exige definição de desempate/pênaltis com um vencedor distinto.'
            ];
        }

        if ($penaltis1 > $penaltis2) {
            $vencedorId = (int)$partida['participante1_id'];
            $perdedorId = (int)$partida['participante2_id'];
        } else {
            $vencedorId = (int)$partida['participante2_id'];
            $perdedorId = (int)$partida['participante1_id'];
        }
    }

    $db->beginTransaction();

    try {
        $dadosAnteriores = [
            'placar1' => $partida['placar1'],
            'placar2' => $partida['placar2'],
            'penaltis1' => $partida['penaltis1'],
            'penaltis2' => $partida['penaltis2'],
            'vencedor_id' => $partida['vencedor_id'],
            'status' => $partida['status']
        ];

        $dadosNovos = [
            'placar1' => $placar1,
            'placar2' => $placar2,
            'penaltis1' => $penaltis1,
            'penaltis2' => $penaltis2,
            'vencedor_id' => $vencedorId,
            'status' => 'finalizada'
        ];

        // Atualiza a partida
        $upd = $db->prepare("
            UPDATE partidas 
            SET placar1 = :placar1,
                placar2 = :placar2,
                penaltis1 = :pen1,
                penaltis2 = :pen2,
                vencedor_id = :vencedor,
                status = 'finalizada',
                atualizado_por = :usuario_id,
                atualizado_em = :agora
            WHERE id = :id
        ");

        $usuario = getUsuarioAtual();
        $upd->execute([
            ':placar1' => $placar1,
            ':placar2' => $placar2,
            ':pen1' => $penaltis1,
            ':pen2' => $penaltis2,
            ':vencedor' => $vencedorId,
            ':usuario_id' => $usuario['id'] ?? null,
            ':agora' => date('Y-m-d H:i:s'),
            ':id' => $partidaId
        ]);

        // Propaga vencedor para a próxima partida da chave
        if (!empty($partida['proxima_partida_id']) && !empty($partida['proximo_slot'])) {
            $slot = ($partida['proximo_slot'] === 'participante2') ? 'participante2_id' : 'participante1_id';
            $updProx = $db->prepare("UPDATE partidas SET $slot = :vencedor WHERE id = :prox_id");
            $updProx->execute([
                ':vencedor' => $vencedorId,
                ':prox_id' => $partida['proxima_partida_id']
            ]);
        }

        // Se for Semifinal e houver disputa de 3º lugar, envia o perdedor para a disputa de 3º lugar
        if ($partida['nome_rodada'] === 'Semifinal') {
            $stmt3 = $db->prepare("SELECT id, participante1_id, participante2_id FROM partidas WHERE torneio_id = :tid AND nome_rodada = 'Disputa de 3º Lugar' LIMIT 1");
            $stmt3->execute([':tid' => $partida['torneio_id']]);
            $partida3 = $stmt3->fetch();

            if ($partida3) {
                // Se a partida da semi for ordem 1 vai no slot 1, se ordem 2 vai no slot 2
                $slot3 = ($partida['ordem_partida'] === 1) ? 'participante1_id' : 'participante2_id';
                $upd3 = $db->prepare("UPDATE partidas SET $slot3 = :perdedor WHERE id = :p3id");
                $upd3->execute([':perdedor' => $perdedorId, ':p3id' => $partida3['id']]);
            }
        }

        // Se for a Grande Final, já pré-registra campeão e vice no torneio
        if ($partida['nome_rodada'] === 'Grande Final') {
            $updTorneio = $db->prepare("UPDATE torneios SET campeao_id = :camp, vice_id = :vice WHERE id = :tid");
            $updTorneio->execute([
                ':camp' => $vencedorId,
                ':vice' => $perdedorId,
                ':tid' => $partida['torneio_id']
            ]);
        }

        // Se for disputa de 3º lugar, salva terceiro colocado
        if ($partida['nome_rodada'] === 'Disputa de 3º Lugar') {
            $updTerceiro = $db->prepare("UPDATE torneios SET terceiro_id = :terc WHERE id = :tid");
            $updTerceiro->execute([
                ':terc' => $vencedorId,
                ':tid' => $partida['torneio_id']
            ]);
        }

        $db->commit();

        // Nome do vencedor
        $vencedorNome = ($vencedorId === (int)$partida['participante1_id']) ? $partida['time1_nome'] : $partida['time2_nome'];
        $infoPlacar = "{$partida['time1_nome']} $placar1 x $placar2 {$partida['time2_nome']}";
        if ($penaltis1 !== null && $penaltis2 !== null) {
            $infoPlacar .= " (Pênaltis: $penaltis1 x $penaltis2)";
        }

        registrarLog(
            'ALTERACAO_PLACAR',
            "Placar definido na partida #$partidaId ({$partida['nome_rodada']}): $infoPlacar. Classificado/Vencedor: $vencedorNome.",
            (int)$partida['torneio_id'],
            $dadosAnteriores,
            $dadosNovos
        );

        return [
            'sucesso' => true,
            'mensagem' => "Placar salvo com sucesso! $vencedorNome avançou.",
            'vencedor_id' => $vencedorId,
            'vencedor_nome' => $vencedorNome
        ];

    } catch (Exception $e) {
        $db->rollBack();
        error_log("Erro ao salvar placar: " . $e->getMessage());
        return ['sucesso' => false, 'mensagem' => 'Erro interno ao salvar placar: ' . $e->getMessage()];
    }
}

/**
 * Obtém todas as partidas de um torneio organizadas por rodada
 */
function obterPartidasDoTorneio(int $torneioId): array {
    $db = getDB();

    $stmt = $db->prepare("
        SELECT p.*,
               t1.nome as time1_nome,
               t1.numero_semente as time1_semente,
               t2.nome as time2_nome,
               t2.numero_semente as time2_semente,
               v.nome as vencedor_nome,
               u.nome as usuario_atualizador
        FROM partidas p
        LEFT JOIN participantes t1 ON p.participante1_id = t1.id
        LEFT JOIN participantes t2 ON p.participante2_id = t2.id
        LEFT JOIN participantes v ON p.vencedor_id = v.id
        LEFT JOIN usuarios u ON p.atualizado_por = u.id
        WHERE p.torneio_id = :tid
        ORDER BY p.rodada ASC, p.ordem_partida ASC
    ");
    $stmt->execute([':tid' => $torneioId]);
    $partidas = $stmt->fetchAll();

    // Agrupa por rodada
    $rodadas = [];
    foreach ($partidas as $p) {
        $rodadaNum = (int)$p['rodada'];
        if (!isset($rodadas[$rodadaNum])) {
            $rodadas[$rodadaNum] = [
                'rodada' => $rodadaNum,
                'nome' => $p['nome_rodada'],
                'partidas' => []
            ];
        }
        $rodadas[$rodadaNum]['partidas'][] = $p;
    }

    return $rodadas;
}
