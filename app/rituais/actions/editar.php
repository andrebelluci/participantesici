<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
  $_SESSION['error'] = 'ID do ritual inválido.';
  header('Location: /participantesici/public_html/rituais');
  exit;
}

$redirect = $_GET['redirect'] ?? '/participantesici/public_html/rituais';

// Buscar ritual
$stmt = $pdo->prepare("SELECT * FROM rituais WHERE id = ?");
$stmt->execute([$id]);
$ritual = $stmt->fetch();

if (!$ritual) {
  $_SESSION['error'] = 'Ritual não encontrado.';
  header('Location: /participantesici/public_html/rituais');
  exit;
}

// ✅ FUNÇÃO PARA GERAR NOME DE ARQUIVO INTELIGENTE
function gerarNomeArquivoRitual($nomeRitual, $extensao)
{
  // Limpa o nome do ritual (remove acentos, espaços, caracteres especiais)
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

  $numeroAleatorio = uniqid();
  return $numeroAleatorio . '_' . substr($nomeRitualLimpo, 0, 20) . '.' . $extensao;
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

  $diretorio = __DIR__ . '/../../../storage/uploads/rituais/';

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

  // Verifica se há upload de nova foto
  if (!empty($_FILES['foto']['name'])) {
    // Exclui fotos antigas baseadas no nome do ritual
    excluirFotoAntigaRitual($nome);

    $extensao = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
    $foto_nome = gerarNomeArquivoRitual($nome, $extensao);
    $foto_destino = __DIR__ . '/../../../storage/uploads/rituais/' . $foto_nome;

    // Criar diretório se não existir
    if (!is_dir(dirname($foto_destino))) {
      mkdir(dirname($foto_destino), 0755, true);
    }

    if (move_uploaded_file($_FILES['foto']['tmp_name'], $foto_destino)) {
      $foto = '/participantesici/storage/uploads/rituais/' . $foto_nome;
    }
  }
  // ✅ VERIFICAR SE FOI SOLICITADA REMOÇÃO DE FOTO
  elseif (isset($_POST['remover_foto'])) {
    excluirFotoAntigaRitual($ritual['nome']); // Usa nome antigo para excluir
    $foto = null;
  }

  // Validações básicas
  if (empty($nome)) {
    $_SESSION['error'] = 'Nome do ritual é obrigatório.';
    header("Location: /participantesici/public_html/ritual/editar?id=$id");
    exit;
  }

  if (empty($data_ritual)) {
    $_SESSION['error'] = 'Data do ritual é obrigatória.';
    header("Location: /participantesici/public_html/ritual/editar?id=$id");
    exit;
  }

  if (empty($padrinho_madrinha)) {
    $_SESSION['error'] = 'Padrinho ou Madrinha é obrigatório.';
    header("Location: /participantesici/public_html/ritual/editar?id=$id");
    exit;
  }

  // ✅ SE O NOME MUDOU, PRECISAMOS RENOMEAR A FOTO
  if ($nome !== $ritual['nome'] && $foto) {
    $nomeAntigoLimpo = preg_replace(
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
        $ritual['nome']
      )
    );

    $diretorio = __DIR__ . '/../../../storage/uploads/rituais/';
    $arquivosAntigos = glob($diretorio . '*_' . substr($nomeAntigoLimpo, 0, 20) . '.*');

    if (!empty($arquivosAntigos)) {
      $arquivoAntigo = $arquivosAntigos[0];
      $extensao = pathinfo($arquivoAntigo, PATHINFO_EXTENSION);
      $novoNome = gerarNomeArquivoRitual($nome, $extensao);
      $novoArquivo = $diretorio . $novoNome;

      if (rename($arquivoAntigo, $novoArquivo)) {
        $foto = '/participantesici/storage/uploads/rituais/' . $novoNome;
      }
    }
  }

  // Atualizar no banco
  $stmt_update = $pdo->prepare("
    UPDATE rituais SET
        nome = ?,
        data_ritual = ?,
        foto = ?,
        padrinho_madrinha = ?
    WHERE id = ?
  ");

  $stmt_update->execute([
    $nome,
    $data_ritual,
    $foto,
    $padrinho_madrinha,
    $id
  ]);

  $_SESSION['success'] = 'Ritual atualizado com sucesso!';
  header("Location: $redirect?id=$id");
  exit;
}

// Se não for POST, mostrar formulário
require_once __DIR__ . '/../templates/editar.php';