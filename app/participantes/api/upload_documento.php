<?php
require_once __DIR__ . '/../../functions/check_auth_api.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$id = $_POST['participante_id'] ?? $_GET['id'] ?? null;
if (!$id) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'ID do participante não especificado.']);
  exit;
}

// Buscar dados do participante
$stmt = $pdo->prepare("SELECT * FROM participantes WHERE id = ?");
$stmt->execute([$id]);
$pessoa = $stmt->fetch();

if (!$pessoa || !isset($pessoa['cpf'])) {
  http_response_code(404);
  echo json_encode(['success' => false, 'message' => 'Participante não encontrado.']);
  exit;
}

// ✅ FUNÇÃO PARA GERAR NOME DE ARQUIVO DE DOCUMENTO
function gerarNomeArquivoDocumento($cpf, $extensao)
{
  $cpfLimpo = preg_replace('/\D/', '', $cpf);
  $numeroAleatorio = uniqid();
  return $numeroAleatorio . '_' . $cpfLimpo . '_doc.' . $extensao;
}

// ✅ FUNÇÃO PARA GERAR PRÓXIMO NÚMERO PARA FICHA DE INSCRIÇÃO
function gerarProximoNumeroFichaInscricao($pdo, $participante_id, $cpf, $extensao)
{
  $cpfLimpo = preg_replace('/\D/', '', $cpf);
  $nomeBase = 'Ficha de inscrição';

  // Buscar todas as fichas de inscrição existentes para este participante
  $stmt = $pdo->prepare("
    SELECT nome_arquivo
    FROM documentos
    WHERE participante_id = ?
    AND nome_arquivo LIKE ?
    ORDER BY nome_arquivo ASC
  ");
  $pattern = $nomeBase . '_%_' . $cpfLimpo . '.' . $extensao;
  $stmt->execute([$participante_id, $pattern]);
  $fichasExistentes = $stmt->fetchAll(PDO::FETCH_COLUMN);

  if (empty($fichasExistentes)) {
    // Primeira ficha
    return $nomeBase . '_1_' . $cpfLimpo . '.' . $extensao;
  }

  // Extrair números das fichas existentes
  $numeros = [];
  foreach ($fichasExistentes as $ficha) {
    // Padrão: Ficha de inscrição_N_CPF.extensao
    if (preg_match('/' . preg_quote($nomeBase, '/') . '_(\d+)_' . preg_quote($cpfLimpo, '/') . '\.' . preg_quote($extensao, '/') . '$/', $ficha, $matches)) {
      $numeros[] = (int) $matches[1];
    }
  }

  if (empty($numeros)) {
    // Se não encontrou números válidos, começa do 1
    return $nomeBase . '_1_' . $cpfLimpo . '.' . $extensao;
  }

  // Encontrar o próximo número disponível
  sort($numeros);
  $proximoNumero = 1;
  foreach ($numeros as $num) {
    if ($num == $proximoNumero) {
      $proximoNumero++;
    } else {
      break;
    }
  }

  return $nomeBase . '_' . $proximoNumero . '_' . $cpfLimpo . '.' . $extensao;
}

// ✅ FUNÇÃO PARA CONVERTER TAMANHO (ex: "8M" para bytes)
function parseSize($size)
{
  $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
  $size = preg_replace('/[^0-9\.]/', '', $size);
  if ($unit) {
    return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
  }
  return round($size);
}

// Processar upload de documento
$cpf = $pessoa['cpf'];
$cpfLimpo = preg_replace('/\D/', '', $cpf);

$documento = null;
$nome_arquivo = null;
$tipo = null;
$tamanho = null;

// Processar documento comprimido (imagem)
if (!empty($_POST['documento_comprimido'])) {
  $imageData = $_POST['documento_comprimido'];
  $tamanhoPost = strlen($imageData);

  // Verificar tamanho máximo do POST
  $maxPostSize = ini_get('post_max_size');
  $maxPostSizeBytes = parseSize($maxPostSize);
  if ($tamanhoPost > $maxPostSizeBytes * 0.9) {
    http_response_code(413);
    echo json_encode(['success' => false, 'message' => 'Arquivo muito grande. Tente uma imagem menor ou use PDF.']);
    exit;
  }

  if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $matches)) {
    $imageType = $matches[1];
    $imageData = substr($imageData, strpos($imageData, ',') + 1);
    $imageData = base64_decode($imageData, true);

    if ($imageData === false) {
      http_response_code(400);
      echo json_encode(['success' => false, 'message' => 'Erro ao processar imagem. Dados inválidos.']);
      exit;
    }

    // Obter nome personalizado (obrigatório)
    $nome_personalizado = !empty($_POST['nome_arquivo_personalizado']) ? trim($_POST['nome_arquivo_personalizado']) : 'Ficha de inscrição';

    // Gerar nome do arquivo físico
    $nome_arquivo_salvo = gerarNomeArquivoDocumento($cpf, $imageType);
    $documento_destino = __DIR__ . '/../../../public_html/storage/uploads/documentos/' . $nome_arquivo_salvo;
    $diretorio_destino = dirname($documento_destino);

    if (!is_dir($diretorio_destino)) {
      if (!mkdir($diretorio_destino, 0755, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao criar diretório de uploads.']);
        exit;
      }
    }

    if (!is_writable($diretorio_destino)) {
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => 'Diretório de uploads não tem permissão de escrita.']);
      exit;
    }

    $resultado = file_put_contents($documento_destino, $imageData);
    if ($resultado === false) {
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => 'Erro ao salvar arquivo no servidor.']);
      exit;
    }

    $documento = '/storage/uploads/documentos/' . $nome_arquivo_salvo;
    $tipo = 'image/' . $imageType;
    $tamanho = filesize($documento_destino);

    // Remover extensão se o usuário digitou
    $nome_personalizado = preg_replace('/\.[^.]+$/', '', $nome_personalizado);

    // Se for "Ficha de inscrição", gerar número automático
    if ($nome_personalizado === 'Ficha de inscrição') {
      $nome_arquivo = gerarProximoNumeroFichaInscricao($pdo, $id, $cpf, $imageType);
    } else {
      // Para outros nomes, validar se já existe
      $nome_arquivo = $nome_personalizado . '_' . $cpfLimpo . '.' . $imageType;

      // Validar se já existe documento com mesmo nome para este participante
      $stmt_check = $pdo->prepare("SELECT id FROM documentos WHERE participante_id = ? AND nome_arquivo = ?");
      $stmt_check->execute([$id, $nome_arquivo]);
      if ($stmt_check->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Já existe um arquivo com este nome. Por favor, escolha outro nome.']);
        exit;
      }
    }
  } else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Formato de imagem inválido.']);
    exit;
  }
}
// Processar upload normal
elseif (!empty($_FILES['documento']['name'])) {
  $extensao = strtolower(pathinfo($_FILES['documento']['name'], PATHINFO_EXTENSION));

  // Obter nome personalizado (obrigatório)
  $nome_personalizado = !empty($_POST['nome_arquivo_personalizado']) ? trim($_POST['nome_arquivo_personalizado']) : 'Ficha de inscrição';

  $nome_arquivo_salvo = gerarNomeArquivoDocumento($cpf, $extensao);
  $documento_destino = __DIR__ . '/../../../public_html/storage/uploads/documentos/' . $nome_arquivo_salvo;
  $diretorio_destino = dirname($documento_destino);

  if (!is_dir($diretorio_destino)) {
    if (!mkdir($diretorio_destino, 0755, true)) {
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => 'Erro ao criar diretório de uploads.']);
      exit;
    }
  }

  if (!is_writable($diretorio_destino)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Diretório de uploads não tem permissão de escrita.']);
    exit;
  }

  // Validar tipo de arquivo
  $tiposPermitidos = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
  if (!in_array($extensao, $tiposPermitidos)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tipo de arquivo não permitido. Use imagens ou PDF.']);
    exit;
  }

  // Validar se o arquivo foi enviado corretamente
  if (!isset($_FILES['documento']['tmp_name']) || !is_uploaded_file($_FILES['documento']['tmp_name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Erro: Arquivo não foi enviado corretamente.']);
    exit;
  }

  if (move_uploaded_file($_FILES['documento']['tmp_name'], $documento_destino)) {
    $documento = '/storage/uploads/documentos/' . $nome_arquivo_salvo;
    $tipo = $_FILES['documento']['type'];
    $tamanho = $_FILES['documento']['size'];

    // Remover extensão se o usuário digitou
    $nome_personalizado = preg_replace('/\.[^.]+$/', '', $nome_personalizado);

    // Se for "Ficha de inscrição", gerar número automático
    if ($nome_personalizado === 'Ficha de inscrição') {
      $nome_arquivo = gerarProximoNumeroFichaInscricao($pdo, $id, $cpf, $extensao);
    } else {
      // Para outros nomes, validar se já existe
      $nome_arquivo = $nome_personalizado . '_' . $cpfLimpo . '.' . $extensao;

      // Validar se já existe documento com mesmo nome para este participante
      $stmt_check = $pdo->prepare("SELECT id FROM documentos WHERE participante_id = ? AND nome_arquivo = ?");
      $stmt_check->execute([$id, $nome_arquivo]);
      if ($stmt_check->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Já existe um arquivo com este nome. Por favor, escolha outro nome.']);
        exit;
      }
    }
  } else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar arquivo no servidor.']);
    exit;
  }
} else {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Nenhum arquivo foi enviado.']);
  exit;
}

// Salvar no banco de dados
if ($documento) {
  try {
    $stmt = $pdo->prepare("
      INSERT INTO documentos (participante_id, nome_arquivo, caminho, tipo, tamanho)
      VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$id, $nome_arquivo, $documento, $tipo, $tamanho]);
    $documento_id = $pdo->lastInsertId();

    echo json_encode([
      'success' => true,
      'message' => 'Documento adicionado com sucesso!',
      'documento_id' => $documento_id
    ]);
  } catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar documento no banco de dados: ' . $e->getMessage()]);
  }
} else {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Erro ao processar documento.']);
}

