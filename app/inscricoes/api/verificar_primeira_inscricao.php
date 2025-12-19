<?php
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
  // que tenha sido SALVA (salvo_em preenchido) e tenha pelo menos um dos campos preenchidos
  $stmt = $pdo->prepare("
    SELECT primeira_vez_instituto, primeira_vez_ayahuasca
    FROM inscricoes
    WHERE participante_id = ?
    AND id != ?
    AND salvo_em IS NOT NULL
    AND (primeira_vez_instituto IS NOT NULL OR primeira_vez_ayahuasca IS NOT NULL)
    ORDER BY id ASC
    LIMIT 1
  ");
  $stmt->execute([$participante_id, $inscricao_atual_id]);
  $primeira_inscricao = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($primeira_inscricao) {
    // Verifica se algum dos campos é "Sim"
    $tem_sim = ($primeira_inscricao['primeira_vez_instituto'] === 'Sim' ||
      $primeira_inscricao['primeira_vez_ayahuasca'] === 'Sim');

    // Verifica se ambos são "Não"
    $ambos_nao = ($primeira_inscricao['primeira_vez_instituto'] === 'Não' &&
      $primeira_inscricao['primeira_vez_ayahuasca'] === 'Não');

    echo json_encode([
      'dados_anteriores' => true,
      'tem_sim' => $tem_sim,
      'ambos_nao' => $ambos_nao,
      'primeira_vez_instituto' => $primeira_inscricao['primeira_vez_instituto'],
      'primeira_vez_ayahuasca' => $primeira_inscricao['primeira_vez_ayahuasca']
    ]);
  } else {
    echo json_encode(['dados_anteriores' => false]);
  }

} catch (Exception $e) {
  echo json_encode(['success' => false, 'error' => 'Erro ao verificar dados: ' . $e->getMessage()]);
}
