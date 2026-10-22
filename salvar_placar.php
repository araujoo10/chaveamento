<?php
/**
 * Endpoint AJAX para Salvar Placar da Partida
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/backend/helpers.php';
require_once __DIR__ . '/backend/partidas.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(false, 'Método de requisição inválido.');
}

if (!usuarioLogado()) {
    responderJson(false, 'Faça login para poder registrar placares.');
}

$partidaId = isset($_POST['partida_id']) ? (int)$_POST['partida_id'] : 0;
$placar1 = isset($_POST['placar1']) && $_POST['placar1'] !== '' ? (int)$_POST['placar1'] : null;
$placar2 = isset($_POST['placar2']) && $_POST['placar2'] !== '' ? (int)$_POST['placar2'] : null;

$penaltis1 = isset($_POST['penaltis1']) && $_POST['penaltis1'] !== '' ? (int)$_POST['penaltis1'] : null;
$penaltis2 = isset($_POST['penaltis2']) && $_POST['penaltis2'] !== '' ? (int)$_POST['penaltis2'] : null;

if ($partidaId <= 0 || $placar1 === null || $placar2 === null) {
    responderJson(false, 'Parâmetros incompletos para atualização de placar.');
}

$resultado = atualizarPlacarPartida($partidaId, $placar1, $placar2, $penaltis1, $penaltis2);

echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
exit;
