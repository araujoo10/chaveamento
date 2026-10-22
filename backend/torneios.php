<?php
/**
 * Módulo de Gerenciamento de Torneios
 * Validações, CRUD, Finalização e Auditoria
 */

require_once __DIR__ . '/../config/bd.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/logs.php';
require_once __DIR__ . '/chaveamento.php';

/**
 * Cria um novo torneio e seus participantes
 */
function criarTorneio(array $dados): array {
    if (!usuarioLogado()) {
        return ['sucesso' => false, 'mensagem' => 'Acesso negado. Faça login para criar competições.'];
    }

    $nome = limparInput($dados['nome'] ?? '');
    $modalidade = limparInput($dados['modalidade'] ?? '');
    $formato = limparInput($dados['formato'] ?? 'mata_mata');
    $descricao = limparInput($dados['descricao'] ?? '');
    $tipoSorteio = ($dados['tipo_sorteio'] ?? 'aleatorio') === 'semente' ? 'semente' : 'aleatorio';
    $disputa3Lugar = !empty($dados['disputa_3lugar']);

    // Validações básicas
    if (empty($nome) || strlen($nome) < 3) {
        return ['sucesso' => false, 'mensagem' => 'O nome do torneio deve ter pelo menos 3 caracteres.'];
    }

    if (empty($modalidade)) {
        return ['sucesso' => false, 'mensagem' => 'Informe a modalidade esportiva (Ex: Futebol, Futsal, Vôlei, E-Sports).'];
    }

    // Processar lista de participantes
    $rawTimes = $dados['participantes'] ?? '';
    $listaTimes = [];

    if (is_array($rawTimes)) {
        $listaTimes = $rawTimes;
    } else {
        // Separa por quebra de linha
        $linhas = explode("\n", str_replace("\r", "", (string)$rawTimes));
        foreach ($linhas as $linha) {
            $limpo = trim($linha);
            if (!empty($limpo)) {
                $listaTimes[] = $limpo;
            }
        }
    }

    // Validação de quantidade
    $totalTimes = count($listaTimes);
    if ($totalTimes < 2) {
        return ['sucesso' => false, 'mensagem' => 'Cadastre no mínimo 2 participantes/equipes para organizar o chaveamento.'];
    }

    if ($totalTimes > 64) {
        return ['sucesso' => false, 'mensagem' => 'O limite máximo para este formato é de 64 participantes.'];
    }

    // Validação de nomes duplicados
    $timesUnicos = [];
    foreach ($listaTimes as $t) {
        $tLimpo = mb_strtolower(trim($t), 'UTF-8');
        if (in_array($tLimpo, $timesUnicos)) {
            return ['sucesso' => false, 'mensagem' => "Existem equipes repetidas na lista: \"$t\". Cada participante deve ter nome único."];
        }
        $timesUnicos[] = $tLimpo;
    }

    $db = getDB();
    $usuario = getUsuarioAtual();

    $db->beginTransaction();

    try {
        // Insere o torneio
        $stmt = $db->prepare("
            INSERT INTO torneios (nome, modalidade, formato, descricao, status, criado_por, criado_em)
            VALUES (:nome, :modalidade, :formato, :descricao, 'planejado', :criado_por, :criado_em)
        ");

        $stmt->execute([
            ':nome' => $nome,
            ':modalidade' => $modalidade,
            ':formato' => $disputa3Lugar ? 'mata_mata_3lugar' : 'mata_mata',
            ':descricao' => $descricao,
            ':criado_por' => $usuario['id'],
            ':criado_em' => date('Y-m-d H:i:s')
        ]);

        $torneioId = (int)$db->lastInsertId();

        // Insere os participantes
        $stmtPart = $db->prepare("
            INSERT INTO participantes (torneio_id, nome, numero_semente, criado_em)
            VALUES (:tid, :nome, :semente, :criado_em)
        ");

        $participantesObjetos = [];
        foreach ($listaTimes as $idx => $nomeTime) {
            $semente = $idx + 1;
            $stmtPart->execute([
                ':tid' => $torneioId,
                ':nome' => trim($nomeTime),
                ':semente' => $semente,
                ':criado_em' => date('Y-m-d H:i:s')
            ]);
            $participantesObjetos[] = [
                'id' => (int)$db->lastInsertId(),
                'nome' => trim($nomeTime),
                'numero_semente' => $semente
            ];
        }

        $db->commit();

        // Gera o chaveamento automático
        ChaveamentoEngine::gerarChaves(
            $torneioId,
            $participantesObjetos,
            $tipoSorteio,
            $disputa3Lugar
        );

        registrarLog(
            'CRIACAO_TORNEIO',
            "Torneio \"$nome\" ($modalidade) criado por {$usuario['nome']} com $totalTimes participantes. Chaveamento gerado automaticamente.",
            $torneioId,
            null,
            ['nome' => $nome, 'modalidade' => $modalidade, 'total_times' => $totalTimes]
        );

        return [
            'sucesso' => true,
            'mensagem' => "Torneio criado com sucesso e chaveamento definido!",
            'torneio_id' => $torneioId
        ];

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Erro ao criar torneio: " . $e->getMessage());
        return ['sucesso' => false, 'mensagem' => 'Erro interno ao criar torneio: ' . $e->getMessage()];
    }
}

/**
 * Consulta informações completas de um torneio
 */
function obterTorneio(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT t.*, 
               u.nome as criador_nome,
               camp.nome as campeao_nome,
               vice.nome as vice_nome,
               terc.nome as terceiro_nome,
               (SELECT COUNT(*) FROM participantes WHERE torneio_id = t.id) as total_participantes,
               (SELECT COUNT(*) FROM partidas WHERE torneio_id = t.id) as total_partidas,
               (SELECT COUNT(*) FROM partidas WHERE torneio_id = t.id AND status = 'finalizada') as partidas_concluidas
        FROM torneios t
        LEFT JOIN usuarios u ON t.criado_por = u.id
        LEFT JOIN participantes camp ON t.campeao_id = camp.id
        LEFT JOIN participantes vice ON t.vice_id = vice.id
        LEFT JOIN participantes terc ON t.terceiro_id = terc.id
        WHERE t.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $torneio = $stmt->fetch();

    return $torneio ?: null;
}

/**
 * Lista todos os torneios cadastrados com filtros
 */
function listarTorneios(?string $statusFiltro = null, ?string $busca = null): array {
    $db = getDB();

    $where = ["1=1"];
    $params = [];

    if (!empty($statusFiltro)) {
        $where[] = "t.status = :status";
        $params[':status'] = $statusFiltro;
    }

    if (!empty($busca)) {
        $where[] = "(t.nome LIKE :busca OR t.modalidade LIKE :busca)";
        $params[':busca'] = '%' . $busca . '%';
    }

    $whereSql = implode(' AND ', $where);

    $sql = "
        SELECT t.*, 
               u.nome as criador_nome,
               camp.nome as campeao_nome,
               (SELECT COUNT(*) FROM participantes WHERE torneio_id = t.id) as total_participantes,
               (SELECT COUNT(*) FROM partidas WHERE torneio_id = t.id) as total_partidas,
               (SELECT COUNT(*) FROM partidas WHERE torneio_id = t.id AND status = 'finalizada') as partidas_concluidas
        FROM torneios t
        LEFT JOIN usuarios u ON t.criado_por = u.id
        LEFT JOIN participantes camp ON t.campeao_id = camp.id
        WHERE $whereSql
        ORDER BY t.id DESC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Obtém todos os participantes de um torneio
 */
function obterParticipantesTorneio(int $torneioId): array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT * FROM participantes 
        WHERE torneio_id = :tid 
        ORDER BY numero_semente ASC, id ASC
    ");
    $stmt->execute([':tid' => $torneioId]);
    return $stmt->fetchAll();
}

/**
 * Finaliza oficialmente uma competição
 */
function finalizarTorneio(int $id): array {
    if (!usuarioLogado()) {
        return ['sucesso' => false, 'mensagem' => 'Acesso negado.'];
    }

    $db = getDB();
    $torneio = obterTorneio($id);

    if (!$torneio) {
        return ['sucesso' => false, 'mensagem' => 'Torneio não encontrado.'];
    }

    if ($torneio['status'] === 'finalizado') {
        return ['sucesso' => false, 'mensagem' => 'Este campeonato já se encontra finalizado.'];
    }

    // Verifica se a final já foi realizada
    $stmtFinal = $db->prepare("
        SELECT * FROM partidas 
        WHERE torneio_id = :tid AND nome_rodada = 'Grande Final' 
        LIMIT 1
    ");
    $stmtFinal->execute([':tid' => $id]);
    $partidaFinal = $stmtFinal->fetch();

    if ($partidaFinal && empty($partidaFinal['vencedor_id'])) {
        return [
            'sucesso' => false, 
            'mensagem' => 'A Grande Final ainda não possui vencedor definido. Finalize a partida final antes de encerrar o campeonato.'
        ];
    }

    // Se houver campeão na final, garante que está registrado no torneio
    $campeaoId = $partidaFinal['vencedor_id'] ?? $torneio['campeao_id'];
    $viceId = ($campeaoId == $partidaFinal['participante1_id']) ? $partidaFinal['participante2_id'] : $partidaFinal['participante1_id'];

    $upd = $db->prepare("
        UPDATE torneios 
        SET status = 'finalizado',
            campeao_id = :camp,
            vice_id = :vice,
            finalizado_em = :agora
        WHERE id = :id
    ");

    $upd->execute([
        ':camp' => $campeaoId,
        ':vice' => $viceId,
        ':agora' => date('Y-m-d H:i:s'),
        ':id' => $id
    ]);

    $usuario = getUsuarioAtual();
    registrarLog(
        'FINALIZACAO_TORNEIO',
        "Torneio #$id \"{$torneio['nome']}\" finalizado com sucesso por {$usuario['nome']}. Campeão registrado.",
        $id
    );

    return ['sucesso' => true, 'mensagem' => 'Torneio finalizado com sucesso! Pódio consolidado.'];
}

/**
 * Reabre um campeonato para ajustes (ação restrita a administradores)
 */
function reabrirTorneio(int $id): array {
    if (!isAdmin()) {
        return ['sucesso' => false, 'mensagem' => 'Apenas administradores podem reabrir torneios finalizados.'];
    }

    $db = getDB();
    $torneio = obterTorneio($id);

    if (!$torneio) {
        return ['sucesso' => false, 'mensagem' => 'Torneio não encontrado.'];
    }

    $upd = $db->prepare("UPDATE torneios SET status = 'em_andamento', finalizado_em = NULL WHERE id = :id");
    $upd->execute([':id' => $id]);

    $usuario = getUsuarioAtual();
    registrarLog(
        'REABERTURA_TORNEIO',
        "Torneio #$id \"{$torneio['nome']}\" reaberto para edição pelo Administrador {$usuario['nome']}.",
        $id
    );

    return ['sucesso' => true, 'mensagem' => 'Torneio reaberto para ajustes com sucesso.'];
}

/**
 * Exclui um torneio e todos os seus dados vinculados
 */
function excluirTorneio(int $id): array {
    if (!isAdmin()) {
        return ['sucesso' => false, 'mensagem' => 'Apenas administradores podem excluir torneios.'];
    }

    $db = getDB();
    $torneio = obterTorneio($id);

    if (!$torneio) {
        return ['sucesso' => false, 'mensagem' => 'Torneio não encontrado.'];
    }

    $usuario = getUsuarioAtual();

    $stmt = $db->prepare("DELETE FROM torneios WHERE id = :id");
    $stmt->execute([':id' => $id]);

    registrarLog(
        'EXCLUSAO_TORNEIO',
        "Torneio #$id \"{$torneio['nome']}\" excluído permanentemente por {$usuario['nome']}.",
        null,
        ['torneio_id' => $id, 'nome' => $torneio['nome']]
    );

    return ['sucesso' => true, 'mensagem' => 'Torneio excluído com sucesso.'];
}
