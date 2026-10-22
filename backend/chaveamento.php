<?php
/**
 * Motor de Geração e Gerenciamento do Chaveamento (Brackets)
 * Suporte a potências de 2 e tratamento inteligente de BYEs (isenção de rodada)
 */

require_once __DIR__ . '/../config/bd.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/logs.php';

class ChaveamentoEngine {

    /**
     * Gera a chave completa para um torneio
     *
     * @param int $torneioId ID do torneio
     * @param array $participantes Lista de participantes [{id, nome, numero_semente}]
     * @param string $tipoSorteio 'aleatorio' ou 'semente'
     * @param bool $disputa3Lugar Se deve gerar partida de 3º lugar
     * @return bool
     */
    public static function gerarChaves(
        int $torneioId,
        array $participantes,
        string $tipoSorteio = 'aleatorio',
        bool $disputa3Lugar = false
    ): bool {
        $db = getDB();
        $numTimes = count($participantes);

        if ($numTimes < 2) {
            throw new InvalidArgumentException("É necessário no mínimo 2 participantes para gerar a chave.");
        }

        // Calcula tamanho do chaveamento (próxima potência de 2)
        $tamanhoChave = 1;
        while ($tamanhoChave < $numTimes) {
            $tamanhoChave *= 2;
        }

        $totalRodadas = (int)log($tamanhoChave, 2);

        // Ordenação dos participantes
        if ($tipoSorteio === 'aleatorio') {
            shuffle($participantes);
            // Reatribui número de semente aleatório
            foreach ($participantes as $i => &$p) {
                $p['numero_semente'] = $i + 1;
            }
            unset($p);
        } else {
            // Ordena pelo número da semente previamente configurado
            usort($participantes, function ($a, $b) {
                return ($a['numero_semente'] ?? 999) <=> ($b['numero_semente'] ?? 999);
            });
        }

        // Gera ordem tradicional de emparelhamento por sementes
        $ordemSementes = self::gerarOrdemSementes($tamanhoChave);

        // Mapeia participantes nos slots da Rodada 1
        $slotsRodada1 = [];
        for ($i = 0; $i < $tamanhoChave; $i++) {
            $sementeDesejada = $ordemSementes[$i];
            // Se a semente existir na lista de participantes, associa; senão é BYE (vazio)
            $slotsRodada1[$i] = $participantes[$sementeDesejada - 1] ?? null;
        }

        $db->beginTransaction();

        try {
            // Limpa partidas anteriores se houver
            $delStmt = $db->prepare("DELETE FROM partidas WHERE torneio_id = :tid");
            $delStmt->execute([':tid' => $torneioId]);

            // Cria a estrutura das rodadas futuras de trás para frente (da Final até a Rodada 1)
            // para conseguirmos amarrar proxima_partida_id facilmente
            $partidasPorRodada = [];

            // Disputa de 3º Lugar (se ativada)
            $partida3LugarId = null;
            if ($disputa3Lugar && $totalRodadas >= 2) {
                $stmt3 = $db->prepare("
                    INSERT INTO partidas (torneio_id, rodada, nome_rodada, ordem_partida, status)
                    VALUES (:tid, :rodada, :nome, 99, 'pendente')
                ");
                $stmt3->execute([
                    ':tid' => $torneioId,
                    ':rodada' => $totalRodadas,
                    ':nome' => 'Disputa de 3º Lugar'
                ]);
                $partida3LugarId = (int)$db->lastInsertId();
            }

            // Cria rodadas da final (totalRodadas) até 1
            for ($rodada = $totalRodadas; $rodada >= 1; $rodada--) {
                $numPartidas = (int)pow(2, $totalRodadas - $rodada);
                $nomeRodada = self::getNomeRodada($rodada, $totalRodadas);
                $partidasPorRodada[$rodada] = [];

                for ($ordem = 1; $ordem <= $numPartidas; $ordem++) {
                    $proximaPartidaId = null;
                    $proximoSlot = null;

                    // Se não for a final, liga com a partida da próxima rodada
                    if ($rodada < $totalRodadas) {
                        $ordemPai = (int)ceil($ordem / 2);
                        $proximaPartidaId = $partidasPorRodada[$rodada + 1][$ordemPai]['id'];
                        $proximoSlot = ($ordem % 2 === 1) ? 'participante1' : 'participante2';
                    }

                    $stmt = $db->prepare("
                        INSERT INTO partidas (torneio_id, rodada, nome_rodada, ordem_partida, proxima_partida_id, proximo_slot, status)
                        VALUES (:tid, :rodada, :nome_rodada, :ordem, :prox_id, :prox_slot, 'pendente')
                    ");

                    $stmt->execute([
                        ':tid' => $torneioId,
                        ':rodada' => $rodada,
                        ':nome_rodada' => $nomeRodada,
                        ':ordem' => $ordem,
                        ':prox_id' => $proximaPartidaId,
                        ':prox_slot' => $proximoSlot
                    ]);

                    $partidaId = (int)$db->lastInsertId();
                    $partidasPorRodada[$rodada][$ordem] = [
                        'id' => $partidaId,
                        'ordem' => $ordem
                    ];
                }
            }

            // Agora preenche a Rodada 1 com os participantes e resolve os BYEs
            $numPartidasR1 = $tamanhoChave / 2;
            for ($ordem = 1; $ordem <= $numPartidasR1; $ordem++) {
                $idxTime1 = ($ordem - 1) * 2;
                $idxTime2 = $idxTime1 + 1;

                $time1 = $slotsRodada1[$idxTime1];
                $time2 = $slotsRodada1[$idxTime2];

                $partidaId = $partidasPorRodada[1][$ordem]['id'];

                $id1 = $time1['id'] ?? null;
                $id2 = $time2['id'] ?? null;

                // Casos de BYE
                if ($id1 !== null && $id2 === null) {
                    // Time 1 avança direto de rodada (BYE)
                    $upd = $db->prepare("
                        UPDATE partidas 
                        SET participante1_id = :p1, participante2_id = NULL, vencedor_id = :vencedor, status = 'bye'
                        WHERE id = :id
                    ");
                    $upd->execute([':p1' => $id1, ':vencedor' => $id1, ':id' => $partidaId]);

                    // Avança automaticamente para a próxima rodada
                    self::propagarVencedor($db, $partidaId, $id1);
                } elseif ($id1 === null && $id2 !== null) {
                    // Time 2 avança direto de rodada (BYE)
                    $upd = $db->prepare("
                        UPDATE partidas 
                        SET participante1_id = NULL, participante2_id = :p2, vencedor_id = :vencedor, status = 'bye'
                        WHERE id = :id
                    ");
                    $upd->execute([':p2' => $id2, ':vencedor' => $id2, ':id' => $partidaId]);

                    self::propagarVencedor($db, $partidaId, $id2);
                } else {
                    // Partida normal
                    $upd = $db->prepare("
                        UPDATE partidas 
                        SET participante1_id = :p1, participante2_id = :p2, status = 'pendente'
                        WHERE id = :id
                    ");
                    $upd->execute([':p1' => $id1, ':p2' => $id2, ':id' => $partidaId]);
                }
            }

            // Atualiza status do torneio para em_andamento
            $stmtStatus = $db->prepare("UPDATE torneios SET status = 'em_andamento' WHERE id = :id");
            $stmtStatus->execute([':id' => $torneioId]);

            $db->commit();

            registrarLog(
                'GERACAO_CHAVEAMENTO',
                "Chaveamento gerado automaticamente para o torneio ID $torneioId ($numTimes times, $tamanhoChave slots, $totalRodadas rodadas). Sorteio: $tipoSorteio.",
                $torneioId
            );

            return true;
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Erro ao gerar chaveamento: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Propaga o vencedor para a próxima partida da árvore
     */
    public static function propagarVencedor(PDO $db, int $partidaOrigemId, int $vencedorId): void {
        $stmt = $db->prepare("SELECT proxima_partida_id, proximo_slot FROM partidas WHERE id = :id");
        $stmt->execute([':id' => $partidaOrigemId]);
        $partida = $stmt->fetch();

        if ($partida && !empty($partida['proxima_partida_id']) && !empty($partida['proximo_slot'])) {
            $slot = $partida['proximo_slot'] === 'participante2' ? 'participante2_id' : 'participante1_id';
            $sql = "UPDATE partidas SET $slot = :vencedor WHERE id = :prox_id";
            $upd = $db->prepare($sql);
            $upd->execute([
                ':vencedor' => $vencedorId,
                ':prox_id' => $partida['proxima_partida_id']
            ]);
        }
    }

    /**
     * Retorna a ordem das sementes clássica de um torneio de eliminação
     * Garante que 1 vs N na final, 1 vs 4 e 2 vs 3 nas semis, etc.
     */
    private static function gerarOrdemSementes(int $n): array {
        $seeds = [1, 2];
        while (count($seeds) < $n) {
            $temp = [];
            $soma = count($seeds) * 2 + 1;
            foreach ($seeds as $s) {
                $temp[] = $s;
                $temp[] = $soma - $s;
            }
            $seeds = $temp;
        }
        return $seeds;
    }

    /**
     * Retorna o nome da rodada de acordo com sua posição na árvore
     */
    public static function getNomeRodada(int $rodada, int $totalRodadas): string {
        $distanciaFinal = $totalRodadas - $rodada;
        switch ($distanciaFinal) {
            case 0:
                return 'Grande Final';
            case 1:
                return 'Semifinal';
            case 2:
                return 'Quartas de Final';
            case 3:
                return 'Oitavas de Final';
            case 4:
                return '16 avos de Final';
            default:
                return "Rodada $rodada";
        }
    }
}
