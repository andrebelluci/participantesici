<?php
require_once __DIR__ . '/../../functions/check_auth_api.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$inscricao_id = $data['inscricao_id'] ?? null;
$participante_id = $data['participante_id'] ?? null;
$ritual_id = $data['ritual_id'] ?? null;
$assinatura = $data['assinatura'] ?? null;

// Validações
if (!$inscricao_id || !$participante_id || !$ritual_id) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
  exit;
}

if (!$assinatura || !preg_match('/^data:image\/png;base64,/', $assinatura)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Assinatura inválida.']);
  exit;
}

try {
  // Verificar se a inscrição existe e pertence aos IDs fornecidos
  $stmt = $pdo->prepare("
    SELECT id FROM inscricoes
    WHERE id = ? AND participante_id = ? AND ritual_id = ?
  ");
  $stmt->execute([$inscricao_id, $participante_id, $ritual_id]);

  if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Inscrição não encontrada.']);
    exit;
  }

  // Atualizar assinatura
  $stmt = $pdo->prepare("
    UPDATE inscricoes
    SET assinatura = ?, assinatura_data = NOW()
    WHERE id = ?
  ");
  $stmt->execute([$assinatura, $inscricao_id]);

  echo json_encode([
    'success' => true,
    'message' => 'Assinatura salva com sucesso!'
  ]);

} catch (PDOException $e) {
  error_log("Erro ao salvar assinatura: " . $e->getMessage());
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Erro ao salvar assinatura.']);
}

