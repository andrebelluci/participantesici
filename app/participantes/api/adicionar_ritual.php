<?php
require_once __DIR__ . '/../../functions/check_auth_api.php';
require_once __DIR__ . '/../../config/database.php';

// Recebe os dados enviados via POST
$data = json_decode(file_get_contents('php://input'), true);
$ritual_id = $data['ritual_id'] ?? null;
$participante_id = $data['participante_id'] ?? null;

// Valida os parâmetros recebidos
if (!$ritual_id || !$participante_id || !is_numeric($ritual_id) || !is_numeric($participante_id)) {
  echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos']);
  exit;
}

try {
  // Verifica se o ritual já está vinculado ao participante
  $stmt = $pdo->prepare("
        SELECT id FROM inscricoes
        WHERE ritual_id = ? AND participante_id = ?
    ");
  $stmt->execute([$ritual_id, $participante_id]);
  if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Ritual já vinculado a este participante']);
    exit;
  }

  // Insere o ritual para o participante
  $stmt = $pdo->prepare("
        INSERT INTO inscricoes (ritual_id, participante_id)
        VALUES (?, ?)
    ");
  $stmt->execute([$ritual_id, $participante_id]);

  echo json_encode(['success' => true]);
} catch (Exception $e) {
  echo json_encode(['success' => false, 'error' => 'Erro ao adicionar ritual: ' . $e->getMessage()]);
}
