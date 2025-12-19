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
  // Buscar dados do participante
  $stmt = $pdo->prepare("SELECT id, nome_completo, cpf, foto FROM participantes WHERE id = ?");
  $stmt->execute([$id]);
  $participante = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$participante) {
    http_response_code(404);
    echo json_encode(['error' => 'Participante não encontrado.']);
    exit;
  }

  // Buscar documentos do participante
  $stmt_docs = $pdo->prepare("SELECT caminho, nome_arquivo FROM documentos WHERE participante_id = ?");
  $stmt_docs->execute([$id]);
  $documentos = $stmt_docs->fetchAll(PDO::FETCH_ASSOC);

  // Verificar se há arquivos para baixar
  $temArquivos = false;
  $arquivosParaZip = [];

  // Adicionar foto se existir
  if ($participante['foto']) {
    $caminho_foto = __DIR__ . '/../../../public_html' . $participante['foto'];
    if (file_exists($caminho_foto)) {
      $extensao_foto = pathinfo($caminho_foto, PATHINFO_EXTENSION);
      $arquivosParaZip[] = [
        'caminho_completo' => $caminho_foto,
        'nome_no_zip' => 'foto_participante.' . $extensao_foto
      ];
      $temArquivos = true;
    }
  }

  // Adicionar documentos
  foreach ($documentos as $doc) {
    $caminho_completo = __DIR__ . '/../../../public_html' . $doc['caminho'];
    if (file_exists($caminho_completo)) {
      $extensao = pathinfo($doc['nome_arquivo'], PATHINFO_EXTENSION);
      $nome_no_zip = 'documentos/' . $doc['nome_arquivo'];
      // Se não tiver extensão no nome, usar a do caminho
      if (!$extensao) {
        $extensao = pathinfo($caminho_completo, PATHINFO_EXTENSION);
        $nome_no_zip = 'documentos/' . basename($doc['nome_arquivo']) . '.' . $extensao;
      }
      $arquivosParaZip[] = [
        'caminho_completo' => $caminho_completo,
        'nome_no_zip' => $nome_no_zip
      ];
      $temArquivos = true;
    }
  }

  // Se não houver arquivos, criar ZIP vazio com mensagem
  if (!$temArquivos) {
    $zip_filename = tempnam(sys_get_temp_dir(), 'participante_');
    $zip = new ZipArchive();

    if ($zip->open($zip_filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
      $zip->addFromString('sem_arquivos.txt', 'Este participante não possui documentos ou foto cadastrados.');
      $zip->close();
    } else {
      http_response_code(500);
      echo json_encode(['error' => 'Erro ao criar arquivo ZIP.']);
      exit;
    }
  } else {

    // Criar ZIP com arquivos
    $zip_filename = tempnam(sys_get_temp_dir(), 'participante_');
    $zip = new ZipArchive();

    if ($zip->open($zip_filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
      http_response_code(500);
      echo json_encode(['error' => 'Erro ao criar arquivo ZIP.']);
      exit;
    }

    // Adicionar arquivos ao ZIP
    foreach ($arquivosParaZip as $arquivo) {
      if (file_exists($arquivo['caminho_completo'])) {
        $zip->addFile($arquivo['caminho_completo'], $arquivo['nome_no_zip']);
      }
    }

    $zip->close();
  }

  // Preparar nome do arquivo para download
  $cpf_limpo = preg_replace('/\D/', '', $participante['cpf']);
  $nome_participante_limpo = preg_replace('/[^a-zA-Z0-9]/', '_', $participante['nome_completo']);
  $nome_zip_download = 'participante_' . $nome_participante_limpo . '_' . $cpf_limpo . '_' . date('Y-m-d') . '.zip';

  // Enviar ZIP para download
  header('Content-Type: application/zip');
  header('Content-Disposition: attachment; filename="' . $nome_zip_download . '"');
  header('Content-Length: ' . filesize($zip_filename));
  header('Cache-Control: no-cache, must-revalidate');
  header('Pragma: no-cache');
  header('Expires: 0');

  readfile($zip_filename);
  unlink($zip_filename); // Remove arquivo temporário
  exit;

} catch (Exception $e) {
  error_log("Erro ao baixar documentos do participante ID $id: " . $e->getMessage());
  http_response_code(500);
  echo json_encode(['error' => 'Erro ao processar download: ' . $e->getMessage()]);
  exit;
}

