<?php
require_once __DIR__ . '/../../functions/check_auth_api.php';
require_once __DIR__ . '/../../config/database.php';

$inscricao_id = $_GET['id'] ?? null;

if (!$inscricao_id || !is_numeric($inscricao_id)) {
    echo json_encode(['error' => 'ID de inscrição inválido']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT i.*, r.nome as ritual_nome, r.id as ritual_id
        FROM inscricoes i
        JOIN rituais r ON i.ritual_id = r.id
        WHERE i.id = ?
    ");
    $stmt->execute([$inscricao_id]);
    $inscricao = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$inscricao) {
        echo json_encode(['error' => 'Inscrição não encontrada']);
        exit;
    }

    echo json_encode($inscricao);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erro ao carregar detalhes: ' . $e->getMessage()]);
}
