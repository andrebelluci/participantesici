<?php
require_once __DIR__ . '/../../functions/check_auth_api.php';
require_once __DIR__ . '/../../config/database.php';

$participante_id = $_GET['participante_id'] ?? null;
$inscricao_atual_id = $_GET['inscricao_atual_id'] ?? null;

if (!$participante_id || !is_numeric($participante_id)) {
  echo json_encode(['error' => 'ID do participante inválido']);
  exit;
}

try {
  // Busca a última inscrição salva (com salvo_em preenchido) para este participante
  // Exclui a inscrição atual se fornecida
  $sql = "
        SELECT i.*, r.nome as ritual_nome, r.id as ritual_id
        FROM inscricoes i
        JOIN rituais r ON i.ritual_id = r.id
        WHERE i.participante_id = ?
        AND i.salvo_em IS NOT NULL
    ";

  $params = [$participante_id];

  if ($inscricao_atual_id && is_numeric($inscricao_atual_id)) {
    $sql .= " AND i.id != ?";
    $params[] = $inscricao_atual_id;
  }

  $sql .= " ORDER BY i.salvo_em DESC LIMIT 1";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $ultimaInscricao = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$ultimaInscricao) {
    echo json_encode(['encontrada' => false]);
    exit;
  }

  // Retorna apenas os campos que devem ser copiados
  // Mantém NULL para campos vazios (não converte para string vazia)
  echo json_encode([
    'encontrada' => true,
    'ritual_id' => $ultimaInscricao['ritual_id'],
    'ritual_nome' => $ultimaInscricao['ritual_nome'],
    'dados' => [
      'doenca_psiquiatrica' => $ultimaInscricao['doenca_psiquiatrica'],
      'nome_doenca' => $ultimaInscricao['nome_doenca'],
      'uso_medicao' => $ultimaInscricao['uso_medicao'],
      'nome_medicao' => $ultimaInscricao['nome_medicao'],
      'mensagem' => $ultimaInscricao['mensagem']
    ]
  ]);
} catch (Exception $e) {
  echo json_encode(['error' => 'Erro ao buscar última inscrição: ' . $e->getMessage()]);
}

