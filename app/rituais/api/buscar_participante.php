<?php
require_once __DIR__ . '/../../functions/check_auth_api.php';
require_once __DIR__ . '/../../functions/participante_status.php';
require_once __DIR__ . '/../../config/database.php';

$pesquisa = $_GET['nome'] ?? null;

if (!$pesquisa) {
  echo json_encode(['error' => 'Pesquisa inválida']);
  exit;
}

try {
  $pesquisaLimpa = preg_replace('/[^0-9]/', '', $pesquisa);
  $statusAtivo = PARTICIPANTE_STATUS_ATIVO;

  if (strlen($pesquisaLimpa) === 11) {
    $stmt = $pdo->prepare("
      SELECT id, nome_completo, foto, cpf, status, motivo_status
      FROM participantes
      WHERE cpf = ? AND status = ?
      LIMIT 20
    ");
    $stmt->execute([$pesquisaLimpa, $statusAtivo]);
  } else {
    $stmt = $pdo->prepare("
      SELECT id, nome_completo, foto, cpf, status, motivo_status
      FROM participantes
      WHERE nome_completo LIKE ? AND status = ?
      LIMIT 20
    ");
    $stmt->execute(["%$pesquisa%", $statusAtivo]);
  }

  $participantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(empty($participantes) ? [] : $participantes);
} catch (Exception $e) {
  echo json_encode(['error' => 'Erro ao buscar participantes: ' . $e->getMessage()]);
}
