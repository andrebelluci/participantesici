<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../config/database.php';

// Obter o ID do participante da URL
$id = $_GET['id'] ?? null;

if (!$id) {
  die("ID do participante não especificado.");
}

$stmt = $pdo->prepare("SELECT * FROM participantes WHERE id = ?");
$stmt->execute([$id]);
$pessoa = $stmt->fetch();

if (!$pessoa) {
  die("Participante não encontrado.");
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

// ✅ PROCESSAR UPLOAD DE DOCUMENTO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_documento'])) {
  // Verificar se é requisição AJAX (no início para usar em todo o código)
  $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

  error_log("=== INÍCIO PROCESSAMENTO UPLOAD DOCUMENTO ===");
  error_log("Participante ID: $id");
  error_log("É AJAX: " . ($isAjax ? 'SIM' : 'NÃO'));
  error_log("POST keys: " . implode(', ', array_keys($_POST)));
  error_log("FILES keys: " . (isset($_FILES) ? implode(', ', array_keys($_FILES)) : 'nenhum'));

  if (!$pessoa || !isset($pessoa['cpf'])) {
    error_log("ERRO: Dados do participante não encontrados");
    $_SESSION['error'] = 'Erro: Dados do participante não encontrados.';
    header("Location: /participante/$id");
    exit;
  }

  $cpf = $pessoa['cpf'];
  $cpfLimpo = preg_replace('/\D/', '', $cpf);
  error_log("CPF do participante: $cpf (limpo: $cpfLimpo)");

  $documento = null;
  $nome_arquivo = null;
  $tipo = null;
  $tamanho = null;

  // Processar documento comprimido (imagem)
  if (!empty($_POST['documento_comprimido'])) {
    $imageData = $_POST['documento_comprimido'];
    $tamanhoPost = strlen($imageData);
    error_log("Documento comprimido recebido. Tamanho POST: $tamanhoPost caracteres");

    // Verificar tamanho máximo do POST (padrão PHP é 8MB, mas base64 aumenta ~33%)
    $maxPostSize = ini_get('post_max_size');
    $maxPostSizeBytes = parseSize($maxPostSize);
    if ($tamanhoPost > $maxPostSizeBytes * 0.9) {
      error_log("Tamanho do POST muito grande: $tamanhoPost bytes (máximo: $maxPostSizeBytes)");
      $_SESSION['error'] = 'Arquivo muito grande. Tente uma imagem menor ou use PDF.';
      header("Location: /participante/$id");
      exit;
    }

    if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $matches)) {
      $imageType = $matches[1];
      error_log("Tipo de imagem detectado: $imageType");
      $imageData = substr($imageData, strpos($imageData, ',') + 1);
      $imageData = base64_decode($imageData, true);

      if ($imageData === false) {
        error_log("Erro ao decodificar base64");
        $_SESSION['error'] = 'Erro ao processar imagem. Dados inválidos.';
        header("Location: /participante/$id");
        exit;
      }

      error_log("Imagem decodificada. Tamanho: " . strlen($imageData) . " bytes");

      // Obter nome personalizado (obrigatório)
      $nome_personalizado = !empty($_POST['nome_arquivo_personalizado']) ? trim($_POST['nome_arquivo_personalizado']) : 'Ficha de inscrição';

      // Gerar nome do arquivo físico (sempre usa nome único para evitar conflitos)
      $nome_arquivo_salvo = gerarNomeArquivoDocumento($cpf, $imageType);
      $documento_destino = __DIR__ . '/../../../public_html/storage/uploads/documentos/' . $nome_arquivo_salvo;
      $diretorio_destino = dirname($documento_destino);

      if (!is_dir($diretorio_destino)) {
        if (!mkdir($diretorio_destino, 0755, true)) {
          error_log("Erro ao criar diretório: $diretorio_destino");
          $_SESSION['error'] = 'Erro ao criar diretório de uploads.';
          header("Location: /participante/$id");
          exit;
        }
      }

      if (!is_writable($diretorio_destino)) {
        error_log("Diretório não tem permissão de escrita: $diretorio_destino");
        $_SESSION['error'] = 'Diretório de uploads não tem permissão de escrita.';
        header("Location: /participante/$id");
        exit;
      }

      $resultado = file_put_contents($documento_destino, $imageData);
      if ($resultado === false) {
        error_log("Erro ao salvar arquivo: $documento_destino");
        $_SESSION['error'] = 'Erro ao salvar arquivo no servidor.';
        header("Location: /participante/$id");
        exit;
      }

      error_log("Arquivo salvo com sucesso: $documento_destino ($resultado bytes)");

      $documento = '/storage/uploads/documentos/' . $nome_arquivo_salvo;
      $tipo = 'image/' . $imageType;
      $tamanho = filesize($documento_destino);

      // Remover extensão se o usuário digitou (vamos adicionar a correta)
      $nome_personalizado = preg_replace('/\.[^.]+$/', '', $nome_personalizado);
      $cpfLimpo = preg_replace('/\D/', '', $cpf);

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
          $_SESSION['error'] = 'Já existe um arquivo com este nome. Por favor, escolha outro nome.';
          // Verificar se é requisição AJAX
          $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
          if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $_SESSION['error']]);
            unset($_SESSION['error']);
            exit;
          }
          header("Location: /participante/$id");
          exit;
        }
      }

      error_log("Documento processado: $documento, tipo: $tipo, tamanho: $tamanho bytes");
    } else {
      error_log("Formato de imagem inválido. POST data não corresponde ao padrão esperado.");
      $_SESSION['error'] = 'Formato de imagem inválido.';
      header("Location: /participante/$id");
      exit;
    }
  }
  // Processar upload normal
  elseif (!empty($_FILES['documento']['name'])) {
    error_log("=== PROCESSANDO UPLOAD NORMAL (PDF ou imagem não comprimida) ===");
    error_log("Nome do arquivo: " . $_FILES['documento']['name']);
    error_log("Tipo do arquivo: " . ($_FILES['documento']['type'] ?? 'não definido'));
    error_log("Tamanho do arquivo: " . ($_FILES['documento']['size'] ?? 'não definido') . " bytes");

    $extensao = strtolower(pathinfo($_FILES['documento']['name'], PATHINFO_EXTENSION));
    $nome_arquivo_original = $_FILES['documento']['name'];
    error_log("Extensão detectada: $extensao");

    // Obter nome personalizado (obrigatório)
    $nome_personalizado = !empty($_POST['nome_arquivo_personalizado']) ? trim($_POST['nome_arquivo_personalizado']) : 'Ficha de inscrição';
    error_log("Nome personalizado: $nome_personalizado");

    $nome_arquivo_salvo = gerarNomeArquivoDocumento($cpf, $extensao);
    $documento_destino = __DIR__ . '/../../../public_html/storage/uploads/documentos/' . $nome_arquivo_salvo;
    $diretorio_destino = dirname($documento_destino);

    if (!is_dir($diretorio_destino)) {
      if (!mkdir($diretorio_destino, 0755, true)) {
        error_log("Erro ao criar diretório: $diretorio_destino");
        $_SESSION['error'] = 'Erro ao criar diretório de uploads.';
        header("Location: /participante/$id");
        exit;
      }
    }

    if (!is_writable($diretorio_destino)) {
      error_log("Diretório não tem permissão de escrita: $diretorio_destino");
      $_SESSION['error'] = 'Diretório de uploads não tem permissão de escrita.';
      header("Location: /participante/$id");
      exit;
    }

    // Validar tipo de arquivo
    $tiposPermitidos = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    if (!in_array($extensao, $tiposPermitidos)) {
      $_SESSION['error'] = 'Tipo de arquivo não permitido. Use imagens ou PDF.';
      // Verificar se é requisição AJAX
      $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
      if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $_SESSION['error']]);
        unset($_SESSION['error']);
        exit;
      }
      header("Location: /participante/$id");
      exit;
    }

    // Validar se o arquivo foi enviado corretamente
    if (!isset($_FILES['documento']['tmp_name']) || !is_uploaded_file($_FILES['documento']['tmp_name'])) {
      error_log("Arquivo não foi enviado corretamente. FILES: " . print_r($_FILES, true));
      $_SESSION['error'] = 'Erro: Arquivo não foi enviado corretamente.';
      // Verificar se é requisição AJAX
      $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
      if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $_SESSION['error']]);
        unset($_SESSION['error']);
        exit;
      }
      header("Location: /participante/$id");
      exit;
    }

    error_log("Tentando mover arquivo de: " . $_FILES['documento']['tmp_name'] . " para: $documento_destino");

    if (move_uploaded_file($_FILES['documento']['tmp_name'], $documento_destino)) {
      error_log("✅ Arquivo movido com sucesso!");
      $documento = '/storage/uploads/documentos/' . $nome_arquivo_salvo;
      $tipo = $_FILES['documento']['type'];
      $tamanho = $_FILES['documento']['size'];
      error_log("Documento definido: $documento, tipo: $tipo, tamanho: $tamanho");

      // Remover extensão se o usuário digitou (vamos adicionar a correta)
      $nome_personalizado = preg_replace('/\.[^.]+$/', '', $nome_personalizado);
      $cpfLimpo = preg_replace('/\D/', '', $cpf);

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
          $_SESSION['error'] = 'Já existe um arquivo com este nome. Por favor, escolha outro nome.';
          // Verificar se é requisição AJAX
          $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
          if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $_SESSION['error']]);
            unset($_SESSION['error']);
            exit;
          }
          header("Location: /participante/$id");
          exit;
        }
      }
    } else {
      error_log("Erro ao mover arquivo. Origem: " . $_FILES['documento']['tmp_name'] . " Destino: $documento_destino");
      $_SESSION['error'] = 'Erro ao salvar arquivo no servidor.';
      // Verificar se é requisição AJAX
      $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
      if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $_SESSION['error']]);
        unset($_SESSION['error']);
        exit;
      }
      header("Location: /participante/$id");
      exit;
    }
  }

  if ($documento) {
    try {
      error_log("Salvando documento no banco: participante_id=$id, nome=$nome_arquivo, caminho=$documento, tipo=$tipo, tamanho=$tamanho");
      $stmt = $pdo->prepare("
        INSERT INTO documentos (participante_id, nome_arquivo, caminho, tipo, tamanho)
        VALUES (?, ?, ?, ?, ?)
      ");
      $stmt->execute([$id, $nome_arquivo, $documento, $tipo, $tamanho]);
      $documento_id = $pdo->lastInsertId();
      error_log("✅ Documento salvo com sucesso! ID: $documento_id");
      $_SESSION['success'] = 'Documento adicionado com sucesso!';

      // Se for AJAX, retornar sucesso imediatamente e sair
      if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => $_SESSION['success']]);
        unset($_SESSION['success']);
        exit;
      }
    } catch (PDOException $e) {
      error_log("❌ ERRO ao salvar documento no banco: " . $e->getMessage());
      error_log("Stack trace: " . $e->getTraceAsString());
      $_SESSION['error'] = 'Erro ao salvar documento no banco de dados: ' . $e->getMessage();

      // Se for AJAX, retornar erro imediatamente e sair
      if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $_SESSION['error']]);
        unset($_SESSION['error']);
        exit;
      }
    }
  } else {
    error_log("❌ ERRO: documento não foi processado");
    error_log("POST documento_comprimido vazio: " . (empty($_POST['documento_comprimido']) ? 'SIM' : 'NÃO'));
    error_log("FILES documento vazio: " . (empty($_FILES['documento']['name']) ? 'SIM' : 'NÃO'));
    error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
    error_log("upload_documento no POST: " . (isset($_POST['upload_documento']) ? 'SIM' : 'NÃO'));

    if (isset($_FILES['documento'])) {
      error_log("FILES['documento'] existe. Keys: " . implode(', ', array_keys($_FILES['documento'])));
      error_log("FILES['documento']['name']: " . ($_FILES['documento']['name'] ?? 'não definido'));
      error_log("FILES['documento']['tmp_name']: " . ($_FILES['documento']['tmp_name'] ?? 'não definido'));
      error_log("FILES['documento']['error']: " . ($_FILES['documento']['error'] ?? 'não definido'));
      error_log("FILES['documento']['size']: " . ($_FILES['documento']['size'] ?? 'não definido'));
    } else {
      error_log("FILES['documento'] NÃO existe!");
    }

    if (!empty($_FILES['documento']['error'])) {
      error_log("Erro no upload FILES: " . $_FILES['documento']['error']);
      $errosUpload = [
        UPLOAD_ERR_INI_SIZE => 'Arquivo excede upload_max_filesize',
        UPLOAD_ERR_FORM_SIZE => 'Arquivo excede MAX_FILE_SIZE',
        UPLOAD_ERR_PARTIAL => 'Upload parcial',
        UPLOAD_ERR_NO_FILE => 'Nenhum arquivo enviado',
        UPLOAD_ERR_NO_TMP_DIR => 'Diretório temporário não encontrado',
        UPLOAD_ERR_CANT_WRITE => 'Falha ao escrever arquivo',
        UPLOAD_ERR_EXTENSION => 'Upload bloqueado por extensão'
      ];
      error_log("Tipo de erro: " . ($errosUpload[$_FILES['documento']['error']] ?? 'Desconhecido'));
    }
    $_SESSION['error'] = 'Erro ao fazer upload do documento. Verifique se o arquivo é válido e tente novamente.';

    // Se for AJAX, retornar erro imediatamente
    if ($isAjax) {
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'message' => $_SESSION['error']]);
      unset($_SESSION['error']);
      exit;
    }
  }

  error_log("=== FIM PROCESSAMENTO UPLOAD DOCUMENTO ===");

  if ($isAjax) {
    // Retornar JSON para AJAX
    header('Content-Type: application/json');
    if (isset($_SESSION['success'])) {
      echo json_encode(['success' => true, 'message' => $_SESSION['success']]);
      unset($_SESSION['success']);
    } elseif (isset($_SESSION['error'])) {
      echo json_encode(['success' => false, 'message' => $_SESSION['error']]);
      unset($_SESSION['error']);
    } else {
      echo json_encode(['success' => false, 'message' => 'Erro desconhecido']);
    }
    exit;
  }

  header("Location: /participante/$id");
  exit;
}

// ✅ PROCESSAR EXCLUSÃO DE DOCUMENTO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['excluir_documento'])) {
  $documento_id = $_POST['documento_id'] ?? null;

  if ($documento_id) {
    try {
      // Buscar caminho do arquivo
      $stmt = $pdo->prepare("SELECT caminho FROM documentos WHERE id = ? AND participante_id = ?");
      $stmt->execute([$documento_id, $id]);
      $documento = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($documento) {
        // Excluir arquivo físico
        $caminho_completo = __DIR__ . '/../../../public_html' . $documento['caminho'];
        if (file_exists($caminho_completo)) {
          unlink($caminho_completo);
        }

        // Excluir do banco
        $stmt_delete = $pdo->prepare("DELETE FROM documentos WHERE id = ? AND participante_id = ?");
        $stmt_delete->execute([$documento_id, $id]);

        $_SESSION['success'] = 'Documento excluído com sucesso!';
      }
    } catch (PDOException $e) {
      $_SESSION['error'] = 'Erro ao excluir documento.';
    }
  }

  // Verificar se é requisição AJAX
  $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

  if ($isAjax) {
    // Retornar JSON para AJAX
    header('Content-Type: application/json');
    if (isset($_SESSION['success'])) {
      echo json_encode(['success' => true, 'message' => $_SESSION['success']]);
      unset($_SESSION['success']);
    } elseif (isset($_SESSION['error'])) {
      echo json_encode(['success' => false, 'message' => $_SESSION['error']]);
      unset($_SESSION['error']);
    } else {
      echo json_encode(['success' => false, 'message' => 'Erro desconhecido']);
    }
    exit;
  }

  header("Location: /participante/$id");
  exit;
}

// ✅ BUSCAR DOCUMENTOS DO PARTICIPANTE
$stmt_documentos = $pdo->prepare("
  SELECT id, nome_arquivo, caminho, tipo, tamanho, criado_em
  FROM documentos
  WHERE participante_id = ?
  ORDER BY criado_em DESC
");
$stmt_documentos->execute([$id]);
$documentos = $stmt_documentos->fetchAll(PDO::FETCH_ASSOC);

// ✅ FUNÇÃO PARA CONTAR TOTAL DE RITUAIS DO PARTICIPANTE
function contarRituaisParticipados($pdo, $participante_id)
{
  $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM inscricoes WHERE participante_id = ? AND presente = 'Sim'");
  $stmt->execute([$participante_id]);
  return $stmt->fetch()['total'];
}

function contarRituaisNaoParticipados($pdo, $participante_id)
{
  $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM inscricoes WHERE participante_id = ? AND presente = 'Não'");
  $stmt->execute([$participante_id]);
  return $stmt->fetch()['total'];
}

function formatarCPF($cpf)
{
  // Remove caracteres não numéricos
  $cpf = preg_replace('/[^0-9]/', '', $cpf);
  // Aplica a máscara ###.###.###-##
  return substr($cpf, 0, 3) . '.' .
    substr($cpf, 3, 3) . '.' .
    substr($cpf, 6, 3) . '-' .
    substr($cpf, 9, 2);
}

function formatarCEP($cep)
{
  if (empty($cep))
    return '';

  $cep = preg_replace('/\D/', '', $cep);

  if (strlen($cep) !== 8)
    return $cep;

  return substr($cep, 0, 5) . '-' . substr($cep, 5);
}

/**
 * Formatar celular com máscara (__) _____-____
 */
function formatarCelular($celular)
{
  if (empty($celular))
    return '';

  $celular = preg_replace('/\D/', '', $celular);

  // 11 dígitos (padrão com 9)
  if (strlen($celular) === 11) {
    return '(' . substr($celular, 0, 2) . ') ' .
      substr($celular, 2, 5) . '-' .
      substr($celular, 7);
  }
  // 10 dígitos (padrão antigo)
  elseif (strlen($celular) === 10) {
    return '(' . substr($celular, 0, 2) . ') ' .
      substr($celular, 2, 4) . '-' .
      substr($celular, 6);
  }

  return $celular;
}

if (!$pessoa) {
  die("Participante não encontrado.");
}

// ✅ ADICIONAR CONTAGEM TOTAL DE RITUAIS
// $total_rituais_participados = contarRituaisParticipados($pdo, $id);
// $total_rituais_nao_participados = contarRituaisParticipados($pdo, $id);

// Paginação
$pagina = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$itens_por_pagina = 9;
$offset = ($pagina - 1) * $itens_por_pagina;

// Filtro por Nome do Ritual
$filtro_nome = isset($_GET['filtro_nome']) ? trim($_GET['filtro_nome']) : '';

// Ordenação
$order_by = isset($_GET['order_by']) ? $_GET['order_by'] : 'data_ritual'; // Coluna padrão: data_ritual
$order_dir = isset($_GET['order_dir']) ? $_GET['order_dir'] : 'DESC'; // Direção padrão: DESC (mais novo primeiro)

// Consulta para contar o total de registros (COM FILTRO)
$sql_count = "
    SELECT COUNT(*) AS total
    FROM inscricoes i
    JOIN rituais r ON i.ritual_id = r.id
    WHERE i.participante_id = ?
";
$params_count = [$id];
if (!empty($filtro_nome)) {
  $sql_count .= " AND r.nome LIKE ?";
  $params_count[] = "%$filtro_nome%";
}
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params_count);
$total_registros = $stmt_count->fetch()['total'];
$total_paginas = ceil($total_registros / $itens_por_pagina);

// Função para verificar se os detalhes obrigatórios estão preenchidos
function temDetalhesCompletos($inscricao) {
  // Campos obrigatórios básicos
  if (empty($inscricao['primeira_vez_instituto']) ||
      empty($inscricao['primeira_vez_ayahuasca']) ||
      empty($inscricao['doenca_psiquiatrica']) ||
      empty($inscricao['uso_medicao'])) {
    return false;
  }

  // Se tem doença psiquiátrica, nome_doenca é obrigatório
  if ($inscricao['doenca_psiquiatrica'] === 'Sim' && empty($inscricao['nome_doenca'])) {
    return false;
  }

  // Se usa medicação, nome_medicao é obrigatório
  if ($inscricao['uso_medicao'] === 'Sim' && empty($inscricao['nome_medicao'])) {
    return false;
  }

  return true;
}

// Consulta para listar os rituais com paginação e ordenação
$sql_rituais = "
    SELECT r.*, i.id as inscricao_id, i.presente, i.observacao,
           i.primeira_vez_instituto, i.primeira_vez_ayahuasca,
           i.doenca_psiquiatrica, i.nome_doenca,
           i.uso_medicao, i.nome_medicao, i.mensagem,
           i.salvo_em, i.assinatura, i.assinatura_data
    FROM inscricoes i
    JOIN rituais r ON i.ritual_id = r.id
    WHERE i.participante_id = ?
";
$params = [$id];
if (!empty($filtro_nome)) {
  $sql_rituais .= " AND r.nome LIKE ?";
  $params[] = "%$filtro_nome%";
}
$sql_rituais .= " ORDER BY $order_by $order_dir LIMIT $itens_por_pagina OFFSET $offset";
$stmt_rituais = $pdo->prepare($sql_rituais);
$stmt_rituais->execute($params);
$rituais = $stmt_rituais->fetchAll();

// Consulta para contar o total REAL de rituais (sem filtro)
$sql_total_rituais = "
    SELECT COUNT(*) AS total
    FROM inscricoes i
    JOIN rituais r ON i.ritual_id = r.id
    WHERE i.participante_id = ?
";
$stmt_total = $pdo->prepare($sql_total_rituais);
$stmt_total->execute([$id]);
$total_rituais_participante = $stmt_total->fetch()['total'];

// Buscar total de participantes (independente da paginação)
$sql_total_inscritos = "
    SELECT COUNT(*) AS total_inscritos
    FROM inscricoes i
    JOIN rituais r ON i.ritual_id = r.id
    WHERE i.participante_id = ?
";
$stmt_total_inscritos = $pdo->prepare($sql_total_inscritos);
$stmt_total_inscritos->execute([$id]);
$total_inscritos = $stmt_total_inscritos->fetch()['total_inscritos'];

// Buscar contagem de participantes presentes (independente da paginação)
$sql_presentes = "
    SELECT COUNT(*) AS total_presentes
    FROM inscricoes i
    JOIN rituais r ON i.ritual_id = r.id
    WHERE i.participante_id = ? AND i.presente = 'Sim'
";
$stmt_presentes = $pdo->prepare($sql_presentes);
$stmt_presentes->execute([$id]);
$total_presentes = $stmt_presentes->fetch()['total_presentes'];

// Determinar o tipo e ID baseado na URL atual
$current_path = $_SERVER['REQUEST_URI'];
$is_participante = strpos($current_path, '/participante/') !== false;
$is_ritual = strpos($current_path, '/ritual/') !== false;

// Extrair ID da URL
if ($is_participante && preg_match('/\/participante\/(\d+)/', $current_path, $matches)) {
  $export_id = $matches[1];
  $export_type = 'participante';
} elseif ($is_ritual && preg_match('/\/ritual\/(\d+)/', $current_path, $matches)) {
  $export_id = $matches[1];
  $export_type = 'ritual';
} else {
  $export_id = null;
  $export_type = null;
}

// Carregar template
require_once __DIR__ . '/../templates/visualizar.php';