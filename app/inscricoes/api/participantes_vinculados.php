<?php
require_once __DIR__ . '/../../functions/check_auth_api.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$ritual_id = filter_input(INPUT_GET, 'ritual_id', FILTER_VALIDATE_INT);

if (!$ritual_id) {
  echo json_encode(['error' => 'ID do ritual é obrigatório']);
  exit;
}

try {
  // Buscar IDs dos participantes já vinculados a este ritual
  $stmt = $pdo->prepare("
        SELECT participante_id
        FROM inscricoes
        WHERE ritual_id = ?
    ");
  $stmt->execute([$ritual_id]);
  $participantes = $stmt->fetchAll(PDO::FETCH_COLUMN);

  echo json_encode([
    'participantes_ids' => array_map('intval', $participantes)
  ]);

} catch (Exception $e) {
  error_log("Erro ao buscar participantes vinculados: " . $e->getMessage());
  echo json_encode(['error' => 'Erro interno do servidor']);
}
