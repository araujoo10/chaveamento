<?php
/**
 * Módulo de Logs e Auditoria do Sistema
 * Garante a integridade e rastreabilidade contra manipulações
 */

require_once __DIR__ . '/../config/bd.php';
require_once __DIR__ . '/helpers.php';

/**
 * Registra um evento de auditoria no banco de dados
 *
 * @param string $acao Identificador curto da ação (ex: 'CRIACAO_TORNEIO', 'ALTERACAO_PLACAR')
 * @param string $descricao Descrição legível em português do que ocorreu
 * @param int|null $torneio_id ID do torneio associado (se houver)
 * @param mixed $dados_anteriores Dados antes da alteração (array ou texto)
 * @param mixed $dados_novos Dados após a alteração (array ou texto)
 * @return bool
 */
function registrarLog(
    string $acao,
    string $descricao,
    ?int $torneio_id = null,
    $dados_anteriores = null,
    $dados_novos = null
): bool {
    try {
        $db = getDB();
        
        $usuario = getUsuarioAtual();
        $usuario_id = $usuario['id'] ?? null;
        $usuario_nome = $usuario['nome'] ?? 'Sistema / Visitante';

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }

        $antigoJson = $dados_anteriores !== null ? (is_string($dados_anteriores) ? $dados_anteriores : json_encode($dados_anteriores, JSON_UNESCAPED_UNICODE)) : null;
        $novoJson = $dados_novos !== null ? (is_string($dados_novos) ? $dados_novos : json_encode($dados_novos, JSON_UNESCAPED_UNICODE)) : null;

        $stmt = $db->prepare("
            INSERT INTO logs_auditoria (usuario_id, usuario_nome, torneio_id, acao, descricao, dados_anteriores, dados_novos, ip, criado_em)
            VALUES (:usuario_id, :usuario_nome, :torneio_id, :acao, :descricao, :dados_anteriores, :dados_novos, :ip, :criado_em)
        ");

        return $stmt->execute([
            ':usuario_id' => $usuario_id,
            ':usuario_nome' => $usuario_nome,
            ':torneio_id' => $torneio_id,
            ':acao' => mb_strtoupper($acao, 'UTF-8'),
            ':descricao' => $descricao,
            ':dados_anteriores' => $antigoJson,
            ':dados_novos' => $novoJson,
            ':ip' => substr($ip, 0, 45),
            ':criado_em' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        error_log("Erro ao registrar log de auditoria: " . $e->getMessage());
        return false;
    }
}

/**
 * Consulta os logs de auditoria com filtros e paginação
 */
function listarLogs(array $filtros = [], int $limite = 50, int $offset = 0): array {
    $db = getDB();

    $where = ["1=1"];
    $params = [];

    if (!empty($filtros['torneio_id'])) {
        $where[] = "l.torneio_id = :torneio_id";
        $params[':torneio_id'] = (int)$filtros['torneio_id'];
    }

    if (!empty($filtros['acao'])) {
        $where[] = "l.acao = :acao";
        $params[':acao'] = $filtros['acao'];
    }

    if (!empty($filtros['busca'])) {
        $where[] = "(l.descricao LIKE :busca OR l.usuario_nome LIKE :busca OR t.nome LIKE :busca)";
        $params[':busca'] = '%' . $filtros['busca'] . '%';
    }

    $whereSql = implode(' AND ', $where);

    // Contagem total
    $countSql = "SELECT COUNT(*) FROM logs_auditoria l 
                 LEFT JOIN torneios t ON l.torneio_id = t.id 
                 WHERE $whereSql";
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Registros
    $sql = "SELECT l.*, t.nome as torneio_nome 
            FROM logs_auditoria l 
            LEFT JOIN torneios t ON l.torneio_id = t.id 
            WHERE $whereSql 
            ORDER BY l.id DESC 
            LIMIT :limite OFFSET :offset";

    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return [
        'total' => $total,
        'logs' => $stmt->fetchAll()
    ];
}

/**
 * Retorna lista de ações distintas registradas nos logs
 */
function obterAcoesRegistradas(): array {
    $db = getDB();
    $stmt = $db->query("SELECT DISTINCT acao FROM logs_auditoria ORDER BY acao ASC");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}
