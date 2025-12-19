<?php
require_once __DIR__ . '/../../functions/check_auth_api.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$inscricao_id = $data['inscricao_id'] ?? null;
$participante_id = $data['participante_id'] ?? null;
$ritual_id = $data['ritual_id'] ?? null;

// Validações
if (!$inscricao_id || !$participante_id || !$ritual_id) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
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

  // Iniciar transação
  $pdo->beginTransaction();

  // Excluir assinatura (definir como NULL)
  $stmt = $pdo->prepare("
    UPDATE inscricoes
    SET assinatura = NULL, assinatura_data = NULL
    WHERE id = ?
  ");
  $stmt->execute([$inscricao_id]);

  // Atualizar presença para 'Não'
  $stmt = $pdo->prepare("
    UPDATE inscricoes
    SET presente = 'Não'
    WHERE id = ?
  ");
  $stmt->execute([$inscricao_id]);

  // Commit transação
  $pdo->commit();

  echo json_encode([
    'success' => true,
    'message' => 'Assinatura excluída e presença atualizada para "Não".'
  ]);

} catch (PDOException $e) {
  // Rollback em caso de erro
  if ($pdo->inTransaction()) {
    $pdo->rollBack();
  }
  error_log("Erro ao excluir assinatura: " . $e->getMessage());
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Erro ao excluir assinatura.']);
}

