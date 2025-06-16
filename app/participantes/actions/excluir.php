<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../config/database.php';

$id = $_GET['id'] ?? null;
if (!$id) {
  $_SESSION['error'] = "ID do participante não especificado.";
  header("Location: /participantesici/public_html/participantes");
  exit;
}

try {
  $pdo->beginTransaction();

  $stmt_delete_inscricoes = $pdo->prepare("DELETE FROM inscricoes WHERE participante_id = ?");
  $stmt_delete_inscricoes->execute([$id]);

  $stmt_delete_participante = $pdo->prepare("DELETE FROM participantes WHERE id = ?");
  $stmt_delete_participante->execute([$id]);

  $pdo->commit();

  $_SESSION['success'] = "Participante excluído com sucesso!";
} catch (Exception $e) {
  $pdo->rollBack();
  $_SESSION['error'] = "Erro ao excluir participante: " . $e->getMessage();
}

header("Location: /participantesici/public_html/participantes");
exit;
