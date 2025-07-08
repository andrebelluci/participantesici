<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../config/database.php';

// Obtém ID do ritual
$id = $_GET['id'] ?? null;

if (!$id) {
  $_SESSION['error'] = 'Ritual não encontrado.';
  header('Location: /rituais');
  exit;
}

// Busca dados do ritual
try {
  $stmt = $pdo->prepare("SELECT * FROM rituais WHERE id = ?");
  $stmt->execute([$id]);
  $ritual = $stmt->fetch();

  if (!$ritual) {
    $_SESSION['error'] = 'Ritual não encontrado.';
    header('Location: /rituais');
    exit;
  }
} catch (PDOException $e) {
  $_SESSION['error'] = 'Erro ao buscar ritual.';
  header('Location: /rituais');
  exit;
}

// ✅ FUNÇÃO PARA GERAR NOME DE ARQUIVO ÚNICO
function gerarNomeArquivoRitual($nomeRitual, $extensao)
{
  $nomeRitualLimpo = preg_replace(
    '/[^a-zA-Z0-9]/',
    '',
    str_replace(
      [
        'á',
        'à',
        'ã',
        'â',
        'é',
        'è',
        'ê',
        'í',
        'ì',
        'î',
        'ó',
        'ò',
        'õ',
        'ô',
        'ú',
        'ù',
        'û',
        'ç',
        'ñ',
        'Á',
        'À',
        'Ã',
        'Â',
        'É',
        'È',
        'Ê',
        'Í',
        'Ì',
        'Î',
        'Ó',
        'Ò',
        'Õ',
        'Ô',
        'Ú',
        'Ù',
        'Û',
        'Ç',
        'Ñ'
      ],
      [
        'a',
        'a',
        'a',
        'a',
        'e',
        'e',
        'e',
        'i',
        'i',
        'i',
        'o',
        'o',
        'o',
        'o',
        'u',
        'u',
        'u',
        'c',
        'n',
        'A',
        'A',
        'A',
        'A',
        'E',
        'E',
        'E',
        'I',
        'I',
        'I',
        'O',
        'O',
        'O',
        'O',
        'U',
        'U',
        'U',
        'C',
        'N'
      ],
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
      [
        'á',
        'à',
        'ã',
        'â',
        'é',
        'è',
        'ê',
        'í',
        'ì',
        'î',
        'ó',
        'ò',
        'õ',
        'ô',
        'ú',
        'ù',
        'û',
        'ç',
        'ñ',
        'Á',
        'À',
        'Ã',
        'Â',
        'É',
        'È',
        'Ê',
        'Í',
        'Ì',
        'Î',
        'Ó',
        'Ò',
        'Õ',
        'Ô',
        'Ú',
        'Ù',
        'Û',
        'Ç',
        'Ñ'
      ],
      [
        'a',
        'a',
        'a',
        'a',
        'e',
        'e',
        'e',
        'i',
        'i',
        'i',
        'o',
        'o',
        'o',
        'o',
        'u',
        'u',
        'u',
        'c',
        'n',
        'A',
        'A',
        'A',
        'A',
        'E',
        'E',
        'E',
        'I',
        'I',
        'I',
        'O',
        'O',
        'O',
        'O',
        'U',
        'U',
        'U',
        'C',
        'N'
      ],
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

  // ✅ GERENCIAMENTO DE IMAGENS MELHORADO
  $foto = $ritual['foto']; // Mantém a foto atual por padrão

  // ✅ NOVO: PROCESSAR IMAGEM COMPRIMIDA PRIMEIRO
  if (!empty($_POST['foto_comprimida'])) {
    // Imagem foi comprimida no frontend
    $foto_comprimida = $_POST['foto_comprimida'];

    // Remove prefixo data:image/jpeg;base64,
    $image_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $foto_comprimida));

    if ($image_data !== false) {
      // Exclui fotos antigas baseadas no nome do ritual
      excluirFotoAntigaRitual($nome);

      $foto_nome = gerarNomeArquivoRitual($nome, 'jpg'); // Sempre JPG para comprimidas
      $foto_destino = __DIR__ . '/../../../public_html/storage/uploads/rituais/' . $foto_nome;

      // Criar diretório se não existir
      if (!is_dir(dirname($foto_destino))) {
        mkdir(dirname($foto_destino), 0755, true);
      }

      if (file_put_contents($foto_destino, $image_data)) {
        $foto = '/storage/uploads/rituais/' . $foto_nome;
        error_log("✅ Imagem comprimida atualizada: $foto");
      } else {
        error_log("❌ Erro ao salvar imagem comprimida");
      }
    }
  }
  // ✅ FALLBACK: PROCESSAR UPLOAD NORMAL (caso JS falhe)
  elseif (!empty($_FILES['foto']['name'])) {
    // Exclui fotos antigas baseadas no nome do ritual
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
      error_log("✅ Imagem original atualizada (fallback): $foto");
    }
  }
  // ✅ VERIFICAR SE FOI SOLICITADA REMOÇÃO DE FOTO
  elseif (isset($_POST['remover_foto'])) {
    excluirFotoAntigaRitual($ritual['nome']); // Usa nome antigo para excluir
    $foto = null;
    error_log("🗑️ Foto removida do ritual: {$ritual['nome']}");
  }

  // Validações básicas
  if (empty($nome)) {
    $_SESSION['error'] = 'Nome do ritual é obrigatório.';
    header("Location: /ritual/editar?id=$id");
    exit;
  }

  if (empty($data_ritual)) {
    $_SESSION['error'] = 'Data do ritual é obrigatória.';
    header("Location: /ritual/editar?id=$id");
    exit;
  }

  if (empty($padrinho_madrinha)) {
    $_SESSION['error'] = 'Padrinho ou Madrinha é obrigatório.';
    header("Location: /ritual/editar?id=$id");
    exit;
  }

  // ✅ ATUALIZAR RITUAL NO BANCO
  try {
    $stmt = $pdo->prepare("
      UPDATE rituais
      SET nome = ?, data_ritual = ?, padrinho_madrinha = ?, foto = ?
      WHERE id = ?
    ");

    $stmt->execute([$nome, $data_ritual, $padrinho_madrinha, $foto, $id]);

    // Verificar se há redirecionamento
    $redirect = $_POST['redirect'] ?? '/rituais';

    $_SESSION['success'] = 'Ritual atualizado com sucesso!';
    header("Location: $redirect");
    exit;

  } catch (PDOException $e) {
    error_log("Erro ao atualizar ritual: " . $e->getMessage());
    $_SESSION['error'] = 'Erro ao atualizar ritual. Tente novamente.';
    header("Location: /ritual/editar?id=$id");
    exit;
  }
}

// Se não for POST, mostrar formulário
require_once __DIR__ . '/../templates/editar.php';
