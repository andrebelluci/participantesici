<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../config/database.php';

// Verifica se o ID foi passado e é válido
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
  $_SESSION['error'] = 'ID do ritual inválido.';
  header('Location: /participantesici/public_html/rituais');
  exit;
}

try {
  $pdo->beginTransaction();

  // 1. Remove as inscrições associadas
  $stmt = $pdo->prepare("DELETE FROM inscricoes WHERE ritual_id = ?");
  $stmt->execute([$id]);

  // 2. Remove o ritual
  $stmt = $pdo->prepare("DELETE FROM rituais WHERE id = ?");
  $stmt->execute([$id]);

  $pdo->commit();

  $_SESSION['success'] = 'Ritual e participantes associados foram excluídos permanentemente.';
} catch (PDOException $e) {
  $pdo->rollBack();
  $_SESSION['error'] = 'Erro ao excluir ritual: ' . $e->getMessage();
}

header('Location: /participantesici/public_html/rituais');
exit;
