<?php
/**
 * Endpoint de Finalização Oficial de Competição
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/backend/helpers.php';
require_once __DIR__ . '/backend/torneios.php';

exigirLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: torneios.php');
    exit;
}

$torneioId = isset($_POST['torneio_id']) ? (int)$_POST['torneio_id'] : 0;
$resultado = finalizarTorneio($torneioId);

if ($resultado['sucesso']) {
    adicionarToast('success', $resultado['mensagem']);
} else {
    adicionarToast('error', $resultado['mensagem']);
}

header('Location: torneio.php?id=' . $torneioId);
exit;
