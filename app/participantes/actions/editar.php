<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../config/database.php';

// Obtém o ID da pessoa a ser editada
$id = $_GET['id'] ?? null;
if (!$id) {
  die("ID da pessoa não especificado.");
}

$redirect = $_GET['redirect'] ?? '/participantes';

// Consulta os dados da pessoa no banco de dados
$stmt = $pdo->prepare("SELECT * FROM participantes WHERE id = ?");
$stmt->execute([$id]);
$pessoa = $stmt->fetch();

if (!$pessoa) {
  die("Pessoa não encontrada.");
}

// ✅ FUNÇÃO PARA GERAR NOME DE ARQUIVO INTELIGENTE
function gerarNomeArquivoParticipante($cpf, $extensao) {
  $cpfLimpo = preg_replace('/\D/', '', $cpf); // Remove tudo que não é número
  $numeroAleatorio = uniqid();
  return $numeroAleatorio . '_' . $cpfLimpo . '.' . $extensao;
}

// ✅ FUNÇÃO PARA EXCLUIR FOTO ANTIGA
function excluirFotoAntigaParticipante($cpf) {
  $cpfLimpo = preg_replace('/\D/', '', $cpf);
  $diretorio = __DIR__ . '/../../../public_html/storage/uploads/participantes/';

  if (is_dir($diretorio)) {
    $arquivos = glob($diretorio . '*_' . $cpfLimpo . '.*');
    foreach ($arquivos as $arquivo) {
      if (file_exists($arquivo)) {
        unlink($arquivo);
      }
    }
  }
}

// Processamento do formulário de edição
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // Captura os dados do formulário
  $nome_completo = $_POST['nome_completo'];
  $nascimento = $_POST['nascimento'];
  $sexo = $_POST['sexo'];
  $cpf = $_POST['cpf'];
  $rg = $_POST['rg'];
  $passaporte = $_POST['passaporte'];
  $celular = $_POST['celular'];
  $email = $_POST['email'];
  $como_soube = $_POST['como_soube'];
  $cep = $_POST['cep'];
  $endereco_rua = $_POST['endereco_rua'];
  $endereco_numero = $_POST['endereco_numero'];
  $endereco_complemento = $_POST['endereco_complemento'];
  $cidade = $_POST['cidade'];
  $estado = $_POST['estado'];
  $bairro = $_POST['bairro'];
  $sobre_participante = $_POST['sobre_participante'];
  $pode_vincular_rituais = $_POST['pode_vincular_rituais'] ?? 'Sim';
  $motivo_bloqueio_vinculacao = $_POST['motivo_bloqueio_vinculacao'] ?? null;

  // Se pode vincular é "Sim", limpar motivo
  if ($pode_vincular_rituais === 'Sim') {
    $motivo_bloqueio_vinculacao = null;
  }

  // ✅ GERENCIAMENTO DE IMAGENS MELHORADO
  $foto = $pessoa['foto']; // Mantém a foto atual por padrão

  // ✅ PROCESSAR IMAGEM COMPRIMIDA (PRIORIDADE MÁXIMA)
  if (!empty($_POST['foto_comprimida'])) {
    // Exclui fotos antigas baseadas no CPF
    excluirFotoAntigaParticipante($cpf);

    // Processa imagem comprimida (base64)
    $imageData = $_POST['foto_comprimida'];
    if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $matches)) {
      $imageType = $matches[1];
      $imageData = substr($imageData, strpos($imageData, ',') + 1);
      $imageData = base64_decode($imageData);

      $foto_nome = gerarNomeArquivoParticipante($cpf, $imageType);
      $foto_destino = __DIR__ . '/../../../public_html/storage/uploads/participantes/' . $foto_nome;

      if (!is_dir(dirname($foto_destino))) {
        mkdir(dirname($foto_destino), 0755, true);
      }

      if (file_put_contents($foto_destino, $imageData)) {
        $foto = '/storage/uploads/participantes/' . $foto_nome;
        error_log("✅ Imagem comprimida atualizada: $foto");
      }
    }
  }
  // ✅ FALLBACK: PROCESSAR IMAGEM CROPADA (sem compressão)
  elseif (!empty($_POST['foto_cropada'])) {
    // Exclui fotos antigas baseadas no CPF
    excluirFotoAntigaParticipante($cpf);

    // Processa imagem cropada (base64)
    $imageData = $_POST['foto_cropada'];
    if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $matches)) {
      $imageType = $matches[1];
      $imageData = substr($imageData, strpos($imageData, ',') + 1);
      $imageData = base64_decode($imageData);

      $foto_nome = gerarNomeArquivoParticipante($cpf, $imageType);
      $foto_destino = __DIR__ . '/../../../public_html/storage/uploads/participantes/' . $foto_nome;

      if (!is_dir(dirname($foto_destino))) {
        mkdir(dirname($foto_destino), 0755, true);
      }

      if (file_put_contents($foto_destino, $imageData)) {
        $foto = '/storage/uploads/participantes/' . $foto_nome;
      }
    }
  }
  // Verifica se há upload de nova foto
  elseif (!empty($_FILES['foto']['name'])) {
    // Exclui fotos antigas baseadas no CPF
    excluirFotoAntigaParticipante($cpf);

    $extensao = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
    $foto_nome = gerarNomeArquivoParticipante($cpf, $extensao);
    $foto_destino = __DIR__ . '/../../../public_html/storage/uploads/participantes/' . $foto_nome;

    if (!is_dir(dirname($foto_destino))) {
      mkdir(dirname($foto_destino), 0755, true);
    }

    if (move_uploaded_file($_FILES['foto']['tmp_name'], $foto_destino)) {
      $foto = '/storage/uploads/participantes/' . $foto_nome;
    }
  }
  // ✅ VERIFICAR SE FOI SOLICITADA REMOÇÃO DE FOTO
  elseif (isset($_POST['remover_foto'])) {
    excluirFotoAntigaParticipante($cpf);
    $foto = null;
  }

  // Verifica se o e-mail é válido
  if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._%+-]{3,}@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
    $_SESSION['error'] = 'Erro: Por favor, digite um e-mail válido.';
    header("Location: /participante/editar/$id");
    exit;
  }

  // Verifica se o CPF já existe no banco de dados (ignorando o próprio participante)
  $stmt_check_cpf = $pdo->prepare("SELECT id FROM participantes WHERE cpf = ? AND id != ?");
  $stmt_check_cpf->execute([$cpf, $id]);
  if ($stmt_check_cpf->rowCount() > 0) {
    $_SESSION['error'] = 'Erro: Este CPF já está cadastrado.';
    header("Location: /participante/editar/$id");
    exit;
  }

  // ✅ SE O CPF MUDOU, PRECISAMOS RENOMEAR A FOTO
  if ($cpf !== $pessoa['cpf'] && $foto) {
    // Encontra foto atual
    $cpfAntigo = preg_replace('/\D/', '', $pessoa['cpf']);
    $cpfNovo = preg_replace('/\D/', '', $cpf);
    $diretorio = __DIR__ . '/../../../public_html/storage/uploads/participantes/';

    $arquivosAntigos = glob($diretorio . '*_' . $cpfAntigo . '.*');
    if (!empty($arquivosAntigos)) {
      $arquivoAntigo = $arquivosAntigos[0];
      $extensao = pathinfo($arquivoAntigo, PATHINFO_EXTENSION);
      $novoNome = gerarNomeArquivoParticipante($cpf, $extensao);
      $novoArquivo = $diretorio . $novoNome;

      if (rename($arquivoAntigo, $novoArquivo)) {
        $foto = '/storage/uploads/participantes/' . $novoNome;
      }
    }
  }

  // Atualiza os dados no banco de dados
  $stmt_update = $pdo->prepare("
    UPDATE participantes SET
        foto = ?, nome_completo = ?, nascimento = ?, sexo = ?, cpf = ?, rg = ?, passaporte = ?,
        celular = ?, email = ?, como_soube = ?, cep = ?, endereco_rua = ?, endereco_numero = ?,
        endereco_complemento = ?, cidade = ?, estado = ?, bairro = ?, sobre_participante = ?,
        pode_vincular_rituais = ?, motivo_bloqueio_vinculacao = ?
    WHERE id = ?
  ");

  $stmt_update->execute([
    $foto,
    $nome_completo,
    $nascimento,
    $sexo,
    $cpf,
    $rg,
    $passaporte,
    $celular,
    $email,
    $como_soube,
    $cep,
    $endereco_rua,
    $endereco_numero,
    $endereco_complemento,
    $cidade,
    $estado,
    $bairro,
    $sobre_participante,
    $pode_vincular_rituais,
    $motivo_bloqueio_vinculacao,
    $id
  ]);

  $_SESSION['success'] = 'Pessoa atualizada com sucesso!';
  header("Location: $redirect");
  exit;
}

// Se não for POST, mostrar formulário
require_once __DIR__ . '/../templates/editar.php';