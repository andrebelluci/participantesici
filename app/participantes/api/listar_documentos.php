<?php
require_once __DIR__ . '/../../functions/check_auth_api.php';
require_once __DIR__ . '/../../config/database.php';

$id = $_GET['id'] ?? null;
if (!$id) {
  http_response_code(400);
  echo json_encode(['error' => 'ID do participante não especificado.']);
  exit;
}

try {
  $stmt = $pdo->prepare("
    SELECT id, nome_arquivo, caminho, tipo, tamanho, criado_em
    FROM documentos
    WHERE participante_id = ?
    ORDER BY criado_em DESC
  ");
  $stmt->execute([$id]);
  $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Formatar dados para resposta
  $documentosFormatados = [];
  foreach ($documentos as $doc) {
    $isImagem = $doc['tipo'] && strpos($doc['tipo'], 'image/') === 0;
    $tamanhoFormatado = $doc['tamanho'] ? number_format($doc['tamanho'] / 1024, 2) . ' KB' : '0 KB';
    $dataFormatada = date('d/m/Y H:i', strtotime($doc['criado_em']));
    $icone = $isImagem ? 'fa-image' : 'fa-file-pdf';

    $documentosFormatados[] = [
      'id' => $doc['id'],
      'nome_arquivo' => $doc['nome_arquivo'],
      'caminho' => $doc['caminho'],
      'tipo' => $doc['tipo'],
      'tamanho' => $doc['tamanho'],
      'tamanho_formatado' => $tamanhoFormatado,
      'criado_em' => $doc['criado_em'],
      'data_formatada' => $dataFormatada,
      'is_imagem' => $isImagem,
      'icone' => $icone
    ];
  }

  header('Content-Type: application/json');
  echo json_encode([
    'success' => true,
    'documentos' => $documentosFormatados,
    'total' => count($documentosFormatados)
  ]);
} catch (Exception $e) {
  http_response_code(500);
  header('Content-Type: application/json');
  echo json_encode(['success' => false, 'error' => 'Erro ao buscar documentos: ' . $e->getMessage()]);
}

