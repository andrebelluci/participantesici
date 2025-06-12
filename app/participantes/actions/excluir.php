<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../config/database.php';

// Verifica se o ID do participante foi passado via GET
$id = $_GET['id'] ?? null;
if (!$id) {
  die("ID do participante não especificado.");
}

try {
  // Inicia uma transação para garantir que todas as operações sejam concluídas com sucesso
  $pdo->beginTransaction();

  // Exclui todas as inscrições do participante na tabela `inscricoes`
  $stmt_delete_inscricoes = $pdo->prepare("DELETE FROM inscricoes WHERE participante_id = ?");
  $stmt_delete_inscricoes->execute([$id]);

  // Exclui o participante da tabela `participantes`
  $stmt_delete_participante = $pdo->prepare("DELETE FROM participantes WHERE id = ?");
  $stmt_delete_participante->execute([$id]);

  // Confirma a transação
  $pdo->commit();

  // Redireciona de volta para a página de participantes com uma mensagem de sucesso
  echo "<script>alert('Participante excluído com sucesso!');</script>";
  echo "<script>window.location.href = '/participantesici/public_html/participantes';</script>";
} catch (Exception $e) {
  // Reverte a transação em caso de erro
  $pdo->rollBack();
  die("Erro ao excluir o participante: " . $e->getMessage());
}
