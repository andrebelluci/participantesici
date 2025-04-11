<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit;
}
require_once 'includes/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$participante_id = $data['participante_id'] ?? null;
$ritual_id = $data['ritual_id'] ?? null;

if (!$participante_id || !$ritual_id || !is_numeric($participante_id) || !is_numeric($ritual_id)) {
    echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos']);
    exit;
}

try {
    // Verifica se o participante já está inscrito no ritual
    $stmt = $pdo->prepare("
        SELECT id FROM inscricoes 
        WHERE participante_id = ? AND ritual_id = ?
    ");
    $stmt->execute([$participante_id, $ritual_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Participante já inscrito neste ritual']);
        exit;
    }

    // Insere o participante no ritual
    $stmt = $pdo->prepare("
        INSERT INTO inscricoes (ritual_id, participante_id) 
        VALUES (?, ?)
    ");
    $stmt->execute([$ritual_id, $participante_id]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erro ao adicionar participante: ' . $e->getMessage()]);
}
