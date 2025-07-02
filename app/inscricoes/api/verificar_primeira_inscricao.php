<?php
// Novo arquivo: public_html/api/inscricoes/verificar-primeira-inscricao.php
require_once __DIR__ . '/../../functions/check_auth_api.php';
require_once __DIR__ . '/../../config/database.php';

$participante_id = $_GET['participante_id'] ?? null;
$inscricao_atual_id = $_GET['inscricao_atual_id'] ?? null;

if (!$participante_id || !$inscricao_atual_id || !is_numeric($participante_id) || !is_numeric($inscricao_atual_id)) {
  echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos']);
  exit;
}

try {
  // Busca a primeira inscrição do participante (excluindo a atual)
  $stmt = $pdo->prepare("
    SELECT primeira_vez_instituto, primeira_vez_ayahuasca
    FROM inscricoes
    WHERE participante_id = ?
    AND id != ?
    AND primeira_vez_instituto IS NOT NULL
    AND primeira_vez_ayahuasca IS NOT NULL
    ORDER BY id ASC
    LIMIT 1
  ");
  $stmt->execute([$participante_id, $inscricao_atual_id]);
  $primeira_inscricao = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($primeira_inscricao) {
    echo json_encode([
      'dados_anteriores' => true,
      'primeira_vez_instituto' => $primeira_inscricao['primeira_vez_instituto'],
      'primeira_vez_ayahuasca' => $primeira_inscricao['primeira_vez_ayahuasca']
    ]);
  } else {
    echo json_encode(['dados_anteriores' => false]);
  }

} catch (Exception $e) {
  echo json_encode(['success' => false, 'error' => 'Erro ao verificar dados: ' . $e->getMessage()]);
}
