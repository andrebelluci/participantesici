<?php
require_once __DIR__ . '/../../functions/check_auth_api.php';
require_once __DIR__ . '/../../config/database.php';

$id = $_GET['id'] ?? null;

if (!$id) {
  echo json_encode(['error' => 'ID do participante não especificado']);
  exit;
}

try {
  $stmt = $pdo->prepare("SELECT pode_vincular_rituais, motivo_bloqueio_vinculacao FROM participantes WHERE id = ?");
  $stmt->execute([$id]);
  $dados = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$dados) {
    echo json_encode(['error' => 'Participante não encontrado']);
    exit;
  }

  echo json_encode([
    'pode_vincular_rituais' => $dados['pode_vincular_rituais'] ?? 'Sim',
    'motivo_bloqueio_vinculacao' => $dados['motivo_bloqueio_vinculacao'] ?? null
  ]);
} catch (Exception $e) {
  echo json_encode(['error' => 'Erro ao buscar dados: ' . $e->getMessage()]);
}
?>

