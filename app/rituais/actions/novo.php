<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../config/database.php';

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

// ✅ FUNÇÃO PARA EXCLUIR FOTO ANTIGA (para caso de substituição)
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

  $foto = null; // Inicialmente sem foto

  // ✅ PROCESSAR UPLOAD DE FOTO
  if (!empty($_FILES['foto']['name'])) {
    // Exclui qualquer foto existente com este nome de ritual
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

  // Validações básicas
  if (empty($nome)) {
    $_SESSION['error'] = 'Nome do ritual é obrigatório.';
    header('Location: /participantesici/public_html/ritual/novo');
    exit;
  }

  if (empty($data_ritual)) {
    $_SESSION['error'] = 'Data do ritual é obrigatória.';
    header('Location: /participantesici/public_html/ritual/novo');
    exit;
  }

  if (empty($padrinho_madrinha)) {
    $_SESSION['error'] = 'Padrinho ou Madrinha é obrigatório.';
    header('Location: /participantesici/public_html/ritual/novo');
    exit;
  }

  // Inserir no banco de dados
  $stmt = $pdo->prepare("
    INSERT INTO rituais (nome, data_ritual, foto, padrinho_madrinha)
    VALUES (?, ?, ?, ?)
  ");

  $stmt->execute([$nome, $data_ritual, $foto, $padrinho_madrinha]);

  $_SESSION['success'] = 'Ritual criado com sucesso!';
  header('Location: /participantesici/public_html/rituais');
  exit;
}

// Se não for POST, mostrar formulário
require __DIR__ . '/../templates/novo.php';