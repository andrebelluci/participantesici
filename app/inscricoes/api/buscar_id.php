<?php
require_once __DIR__ . '/../../functions/check_auth_api.php';
require_once __DIR__ . '/../../config/database.php';

$participante_id = $_GET['participante_id'] ?? null;
$ritual_id = $_GET['ritual_id'] ?? null;

if (!$participante_id || !$ritual_id || !is_numeric($participante_id) || !is_numeric($ritual_id)) {
    echo json_encode(['error' => 'Parâmetros inválidos']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id
        FROM inscricoes
        WHERE participante_id = ? AND ritual_id = ?
    ");
    $stmt->execute([$participante_id, $ritual_id]);
    $inscricao = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$inscricao) {
        echo json_encode(['error' => 'Inscrição não encontrada']);
        exit;
    }

    echo json_encode(['inscricao_id' => $inscricao['id']]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erro ao buscar ID da inscrição: ' . $e->getMessage()]);
}
?>