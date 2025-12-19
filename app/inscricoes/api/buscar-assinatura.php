<?php
require_once __DIR__ . '/../../functions/check_auth_api.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$id = $_GET['id'] ?? null;

if (!$id) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'ID não especificado.']);
  exit;
}

try {
  $stmt = $pdo->prepare("
    SELECT assinatura, assinatura_data
    FROM inscricoes
    WHERE id = ?
  ");
  $stmt->execute([$id]);
  $result = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($result && $result['assinatura']) {
    echo json_encode([
      'success' => true,
      'assinatura' => $result['assinatura'],
      'assinatura_data' => $result['assinatura_data']
    ]);
  } else {
    echo json_encode([
      'success' => false,
      'assinatura' => null
    ]);
  }

} catch (PDOException $e) {
  error_log("Erro ao buscar assinatura: " . $e->getMessage());
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Erro ao buscar assinatura.']);
}

