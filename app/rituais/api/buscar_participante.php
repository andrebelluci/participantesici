<?php
require_once __DIR__ . '/../../functions/check_auth_api.php';
require_once __DIR__ . '/../../config/database.php';

$pesquisa = $_GET['nome'] ?? null;

if (!$pesquisa) {
  echo json_encode(['error' => 'Pesquisa inválida']);
  exit;
}

try {
  $pesquisaLimpa = preg_replace('/[^0-9]/', '', $pesquisa);
  if (strlen($pesquisaLimpa) === 11) {
    $stmt = $pdo->prepare("SELECT id, nome_completo, foto FROM participantes WHERE cpf = ? LIMIT 20");
    $stmt->execute([$pesquisaLimpa]);
  } else {
    $stmt = $pdo->prepare("SELECT id, nome_completo, foto FROM participantes WHERE nome_completo LIKE ? LIMIT 20");
    $stmt->execute(["%$pesquisa%"]);
  }

  $participantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(empty($participantes) ? [] : $participantes);
} catch (Exception $e) {
  echo json_encode(['error' => 'Erro ao buscar participantes: ' . $e->getMessage()]);
}