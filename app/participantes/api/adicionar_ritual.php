<?php
require_once __DIR__ . '/../../functions/check_auth_api.php';
require_once __DIR__ . '/../../functions/participante_status.php';
require_once __DIR__ . '/../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$ritual_id = $data['ritual_id'] ?? null;
$participante_id = $data['participante_id'] ?? null;

if (!$ritual_id || !$participante_id || !is_numeric($ritual_id) || !is_numeric($participante_id)) {
  echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos']);
  exit;
}

try {
  $stmt = $pdo->prepare("SELECT status, motivo_status FROM participantes WHERE id = ?");
  $stmt->execute([$participante_id]);
  $participante = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$participante) {
    echo json_encode(['success' => false, 'error' => 'Participante não encontrado']);
    exit;
  }

  $status = participanteNormalizarStatus($participante['status'] ?? null);

  if (!participantePodeVincularRituais($status)) {
    $motivo = $participante['motivo_status'] ?? null;
    if ($motivo === null || trim($motivo) === '') {
      $motivo = participanteStatusLabel($status);
    }
    echo json_encode([
      'success' => false,
      'error' => 'Este participante não pode ser vinculado a novos rituais (' . participanteStatusLabel($status) . ').',
      'motivo_status' => $motivo,
      'status' => $status,
    ]);
    exit;
  }

  $stmt = $pdo->prepare("
    SELECT primeira_vez_instituto, primeira_vez_ayahuasca
    FROM inscricoes
    WHERE participante_id = ?
    AND primeira_vez_instituto IS NOT NULL
    AND primeira_vez_ayahuasca IS NOT NULL
    ORDER BY id ASC
    LIMIT 1
  ");
  $stmt->execute([$participante_id]);
  $inscricao_anterior = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($inscricao_anterior) {
    $primeira_vez_instituto = 'Não';
    $primeira_vez_ayahuasca = 'Não';
  } else {
    $primeira_vez_instituto = null;
    $primeira_vez_ayahuasca = null;
  }

  $stmt = $pdo->prepare("
    INSERT INTO inscricoes (ritual_id, participante_id, primeira_vez_instituto, primeira_vez_ayahuasca)
    VALUES (?, ?, ?, ?)
  ");
  $stmt->execute([$ritual_id, $participante_id, $primeira_vez_instituto, $primeira_vez_ayahuasca]);

  $inscricao_id = $pdo->lastInsertId();

  $dadosCopiados = false;
  $ritualNomeOrigem = null;
  $stmt = $pdo->prepare("
    SELECT i.*, r.nome as ritual_nome
    FROM inscricoes i
    JOIN rituais r ON i.ritual_id = r.id
    WHERE i.participante_id = ?
    AND i.salvo_em IS NOT NULL
    AND i.id != ?
    ORDER BY i.salvo_em DESC
    LIMIT 1
  ");
  $stmt->execute([$participante_id, $inscricao_id]);
  $ultimaInscricao = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($ultimaInscricao) {
    $stmt = $pdo->prepare("
      UPDATE inscricoes
      SET doenca_psiquiatrica = ?,
          nome_doenca = ?,
          uso_medicao = ?,
          nome_medicao = ?,
          mensagem = ?
      WHERE id = ?
    ");
    $stmt->execute([
      $ultimaInscricao['doenca_psiquiatrica'],
      $ultimaInscricao['nome_doenca'],
      $ultimaInscricao['uso_medicao'],
      $ultimaInscricao['nome_medicao'],
      $ultimaInscricao['mensagem'],
      $inscricao_id
    ]);
    $dadosCopiados = true;
    $ritualNomeOrigem = $ultimaInscricao['ritual_nome'];
  }

  $response = ['success' => true, 'inscricao_id' => $inscricao_id];
  $response['dados_anteriores'] = (bool) $inscricao_anterior;
  if ($inscricao_anterior) {
    $response['primeira_vez_instituto'] = $primeira_vez_instituto;
    $response['primeira_vez_ayahuasca'] = $primeira_vez_ayahuasca;
  }
  $response['dados_copiados'] = $dadosCopiados;
  if ($dadosCopiados) {
    $response['ritual_nome_origem'] = $ritualNomeOrigem;
  }

  echo json_encode($response);

} catch (Exception $e) {
  echo json_encode(['success' => false, 'error' => 'Erro ao adicionar ritual: ' . $e->getMessage()]);
}
