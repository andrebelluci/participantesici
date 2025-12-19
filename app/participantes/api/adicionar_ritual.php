<?php
require_once __DIR__ . '/../../functions/check_auth_api.php';
require_once __DIR__ . '/../../config/database.php';

// Recebe os dados enviados via POST
$data = json_decode(file_get_contents('php://input'), true);
$ritual_id = $data['ritual_id'] ?? null;
$participante_id = $data['participante_id'] ?? null;

// Valida os parâmetros recebidos
if (!$ritual_id || !$participante_id || !is_numeric($ritual_id) || !is_numeric($participante_id)) {
  echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos']);
  exit;
}

try {
  // Verifica se o participante já tem inscrições anteriores
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

  // Se já tem inscrição anterior, usa lógica de negócio
  if ($inscricao_anterior) {
    // Se na primeira vez foi "Sim", agora sempre será "Não"
    // Se na primeira vez foi "Não", continua "Não"
    $primeira_vez_instituto = ($inscricao_anterior['primeira_vez_instituto'] === 'Sim') ? 'Não' : 'Não';
    $primeira_vez_ayahuasca = ($inscricao_anterior['primeira_vez_ayahuasca'] === 'Sim') ? 'Não' : 'Não';
  } else {
    // Se é a primeira inscrição, deixa NULL para ser preenchido depois
    $primeira_vez_instituto = null;
    $primeira_vez_ayahuasca = null;
  }

  // Insere o ritual para o participante
  $stmt = $pdo->prepare("
    INSERT INTO inscricoes (ritual_id, participante_id, primeira_vez_instituto, primeira_vez_ayahuasca)
    VALUES (?, ?, ?, ?)
  ");
  $stmt->execute([$ritual_id, $participante_id, $primeira_vez_instituto, $primeira_vez_ayahuasca]);

  $inscricao_id = $pdo->lastInsertId();

  // Copia dados da última inscrição salva (se existir)
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
    // Copia os campos da última inscrição salva
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

  // Retorna informação se os dados vieram de inscrição anterior
  if ($inscricao_anterior) {
    $response['dados_anteriores'] = true;
    $response['primeira_vez_instituto'] = $primeira_vez_instituto;
    $response['primeira_vez_ayahuasca'] = $primeira_vez_ayahuasca;
  } else {
    $response['dados_anteriores'] = false;
  }

  // Retorna informação sobre cópia de dados
  if ($dadosCopiados) {
    $response['dados_copiados'] = true;
    $response['ritual_nome_origem'] = $ritualNomeOrigem;
  } else {
    $response['dados_copiados'] = false;
  }

  echo json_encode($response);

} catch (Exception $e) {
  echo json_encode(['success' => false, 'error' => 'Erro ao adicionar ritual: ' . $e->getMessage()]);
}
