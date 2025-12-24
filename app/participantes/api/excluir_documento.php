<?php
require_once __DIR__ . '/../../functions/check_auth_api.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$documento_id = $_POST['documento_id'] ?? null;
$participante_id = $_POST['participante_id'] ?? null;

if (!$documento_id || !$participante_id) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'ID do documento ou participante não especificado.']);
  exit;
}

try {
  // Buscar caminho do arquivo antes de excluir
  $stmt = $pdo->prepare("SELECT caminho FROM documentos WHERE id = ? AND participante_id = ?");
  $stmt->execute([$documento_id, $participante_id]);
  $documento = $stmt->fetch();

  if (!$documento) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Documento não encontrado.']);
    exit;
  }

  // Excluir do banco de dados
  $stmt_delete = $pdo->prepare("DELETE FROM documentos WHERE id = ? AND participante_id = ?");
  $stmt_delete->execute([$documento_id, $participante_id]);

  // Excluir arquivo físico
  $caminho_arquivo = __DIR__ . '/../../../public_html' . $documento['caminho'];
  if (file_exists($caminho_arquivo)) {
    unlink($caminho_arquivo);
  }

  echo json_encode([
    'success' => true,
    'message' => 'Documento excluído com sucesso!'
  ]);
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Erro ao excluir documento: ' . $e->getMessage()]);
}

