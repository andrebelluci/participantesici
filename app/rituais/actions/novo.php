<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../config/database.php';

// ✅ FUNÇÃO PARA GERAR NOME DE ARQUIVO ÚNICO
function gerarNomeArquivoRitual($nomeRitual, $extensao)
{
  $nomeRitualLimpo = preg_replace(
    '/[^a-zA-Z0-9]/',
    '',
    str_replace(
      ['á', 'à', 'ã', 'â', 'é', 'è', 'ê', 'í', 'ì', 'î', 'ó', 'ò', 'õ', 'ô', 'ú', 'ù', 'û', 'ç', 'ñ',
       'Á', 'À', 'Ã', 'Â', 'É', 'È', 'Ê', 'Í', 'Ì', 'Î', 'Ó', 'Ò', 'Õ', 'Ô', 'Ú', 'Ù', 'Û', 'Ç', 'Ñ'],
      ['a', 'a', 'a', 'a', 'e', 'e', 'e', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'c', 'n',
       'A', 'A', 'A', 'A', 'E', 'E', 'E', 'I', 'I', 'I', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'C', 'N'],
      $nomeRitual
    )
  );

  return date('Y-m-d_H-i-s') . '_' . substr($nomeRitualLimpo, 0, 20) . '.' . $extensao;
}

// ✅ FUNÇÃO PARA EXCLUIR FOTO ANTIGA
function excluirFotoAntigaRitual($nomeRitual)
{
  $nomeRitualLimpo = preg_replace(
    '/[^a-zA-Z0-9]/',
    '',
    str_replace(
      ['á', 'à', 'ã', 'â', 'é', 'è', 'ê', 'í', 'ì', 'î', 'ó', 'ò', 'õ', 'ô', 'ú', 'ù', 'û', 'ç', 'ñ',
       'Á', 'À', 'Ã', 'Â', 'É', 'È', 'Ê', 'Í', 'Ì', 'Î', 'Ó', 'Ò', 'Õ', 'Ô', 'Ú', 'Ù', 'Û', 'Ç', 'Ñ'],
      ['a', 'a', 'a', 'a', 'e', 'e', 'e', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'c', 'n',
       'A', 'A', 'A', 'A', 'E', 'E', 'E', 'I', 'I', 'I', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'C', 'N'],
      $nomeRitual
    )
  );

  $diretorio = __DIR__ . '/../../../public_html/storage/uploads/rituais/';

  if (is_dir($diretorio)) {
    $arquivos = glob($diretorio . '*_' . substr($nomeRitualLimpo, 0, 20) . '.*');
    foreach ($arquivos as $arquivo) {
      if (file_exists($arquivo)) {
        unlink($arquivo);
      }
    }
  }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $nome = $_POST['nome'];
  $data_ritual = $_POST['data_ritual'];
  $padrinho_madrinha = $_POST['padrinho_madrinha'];

  $redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? '/rituais';

  // Extrair participante_id do redirect
  $participante_id_from_redirect = null;
  if ($redirect && strpos($redirect, '/participante/') !== false) {
    preg_match('/\/participante\/(\d+)/', $redirect, $matches);
    if (isset($matches[1])) {
      $participante_id_from_redirect = (int) $matches[1];
    }
  }

  $foto = null; // Inicialmente sem foto

  // ✅ NOVO: PROCESSAR IMAGEM COMPRIMIDA PRIMEIRO
  if (!empty($_POST['foto_comprimida'])) {
    // Imagem foi comprimida no frontend
    $foto_comprimida = $_POST['foto_comprimida'];

    // Remove prefixo data:image/jpeg;base64,
    $image_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $foto_comprimida));

    if ($image_data !== false) {
      // Exclui qualquer foto existente com este nome de ritual
      excluirFotoAntigaRitual($nome);

      $foto_nome = gerarNomeArquivoRitual($nome, 'jpg'); // Sempre JPG para comprimidas
      $foto_destino = __DIR__ . '/../../../public_html/storage/uploads/rituais/' . $foto_nome;

      // Criar diretório se não existir
      if (!is_dir(dirname($foto_destino))) {
        mkdir(dirname($foto_destino), 0755, true);
      }

      if (file_put_contents($foto_destino, $image_data)) {
        $foto = '/storage/uploads/rituais/' . $foto_nome;
        error_log("✅ Imagem comprimida salva: $foto");
      } else {
        error_log("❌ Erro ao salvar imagem comprimida");
      }
    }
  }
  // ✅ FALLBACK: PROCESSAR UPLOAD NORMAL (caso JS falhe)
  elseif (!empty($_FILES['foto']['name'])) {
    // Exclui qualquer foto existente com este nome de ritual
    excluirFotoAntigaRitual($nome);

    $extensao = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
    $foto_nome = gerarNomeArquivoRitual($nome, $extensao);
    $foto_destino = __DIR__ . '/../../../public_html/storage/uploads/rituais/' . $foto_nome;

    // Criar diretório se não existir
    if (!is_dir(dirname($foto_destino))) {
      mkdir(dirname($foto_destino), 0755, true);
    }

    if (move_uploaded_file($_FILES['foto']['tmp_name'], $foto_destino)) {
      $foto = '/storage/uploads/rituais/' . $foto_nome;
      error_log("✅ Imagem original salva (fallback): $foto");
    }
  }

  // Validações básicas
  if (empty($nome)) {
    $_SESSION['error'] = 'Nome do ritual é obrigatório.';
    header('Location: /ritual/novo');
    exit;
  }

  if (empty($data_ritual)) {
    $_SESSION['error'] = 'Data do ritual é obrigatória.';
    header('Location: /ritual/novo');
    exit;
  }

  if (empty($padrinho_madrinha)) {
    $_SESSION['error'] = 'Padrinho ou Madrinha é obrigatório.';
    header('Location: /ritual/novo');
    exit;
  }

  // ✅ INSERIR RITUAL NO BANCO
  try {
    $stmt = $pdo->prepare("
      INSERT INTO rituais (nome, data_ritual, padrinho_madrinha, foto)
      VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([$nome, $data_ritual, $padrinho_madrinha, $foto]);
    $novoRitualId = $pdo->lastInsertId();

    // ✅ SE HÁ PARTICIPANTE PARA VINCULAR
    if ($participante_id_from_redirect) {
      $stmt_inscricao = $pdo->prepare("
        INSERT INTO inscricoes (ritual_id, participante_id)
        VALUES (?, ?)
      ");
      $stmt_inscricao->execute([$novoRitualId, $participante_id_from_redirect]);

      $_SESSION['success'] = 'Ritual criado e participante vinculado com sucesso!';
      header("Location: $redirect");
      exit;
    } else {
      $_SESSION['success'] = 'Ritual criado com sucesso!';
      header('Location: /rituais');
      exit;
    }

  } catch (PDOException $e) {
    error_log("Erro ao criar ritual: " . $e->getMessage());
    $_SESSION['error'] = 'Erro ao criar ritual. Tente novamente.';
    header('Location: /ritual/novo');
    exit;
  }
}

// Se não for POST, mostrar formulário
require_once __DIR__ . '/../templates/novo.php';
