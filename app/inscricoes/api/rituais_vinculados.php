<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

$participante_id = $_GET['participante_id'] ?? null;

if (!$participante_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID do participante é obrigatório']);
    exit;
}

try {
    // Busca todos os rituais já vinculados ao participante
    $stmt = $pdo->prepare("
        SELECT ritual_id
        FROM inscricoes
        WHERE participante_id = ?
    ");

    $stmt->execute([$participante_id]);
    $rituais = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'rituais_ids' => array_map('intval', $rituais) // Converte para array de inteiros
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor']);
    error_log('Erro ao buscar rituais vinculados: ' . $e->getMessage());
}
