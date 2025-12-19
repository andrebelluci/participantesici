<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../config/database.php';

// ✅ FUNÇÃO PARA GERAR NOME DE ARQUIVO INTELIGENTE
function gerarNomeArquivoParticipante($cpf, $extensao)
{
  $cpfLimpo = preg_replace('/\D/', '', $cpf); // Remove tudo que não é número
  $numeroAleatorio = uniqid();
  return $numeroAleatorio . '_' . $cpfLimpo . '.' . $extensao;
}

// ✅ FUNÇÃO PARA EXCLUIR FOTO ANTIGA (para caso de substituição)
function excluirFotoAntigaParticipante($cpf)
{
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

  $redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? '/participantes';

  // Extrair ritual_id do redirect
  $ritual_id_from_redirect = null;
  if ($redirect && strpos($redirect, '/ritual/') !== false) {
    preg_match('/\/ritual\/(\d+)/', $redirect, $matches);
    if (isset($matches[1])) {
      $ritual_id_from_redirect = (int) $matches[1];
    }
  }

  $foto = null; // Inicialmente sem foto

  // ✅ PROCESSAR IMAGEM COMPRIMIDA (PRIORIDADE MÁXIMA)
  if (!empty($_POST['foto_comprimida'])) {
    // Exclui qualquer foto existente com este CPF
    excluirFotoAntigaParticipante($cpf);

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
        error_log("✅ Imagem comprimida salva: $foto");
      }
    }
  }
  // ✅ FALLBACK: PROCESSAR IMAGEM CROPADA (sem compressão)
  elseif (!empty($_POST['foto_cropada'])) {
    // Exclui qualquer foto existente com este CPF
    excluirFotoAntigaParticipante($cpf);

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
  // ✅ PROCESSAR UPLOAD NORMAL
  elseif (!empty($_FILES['foto']['name'])) {
    // Exclui qualquer foto existente com este CPF
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

  // Verifica se o e-mail é válido
  if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._%+-]{3,}@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
    $_SESSION['error'] = 'Erro: Por favor, digite um e-mail válido.';
    header('Location: /participante/novo');
    exit;
  }

  // Verifica se o CPF já existe no banco de dados
  $stmt_check_cpf = $pdo->prepare("SELECT id FROM participantes WHERE cpf = ?");
  $stmt_check_cpf->execute([$cpf]);
  if ($stmt_check_cpf->rowCount() > 0) {
    $_SESSION['error'] = 'Erro: Este CPF já está cadastrado.';
    header('Location: /participante/novo');
    exit;
  }

  // Insere participante no banco
  try {
    $stmt = $pdo->prepare("
    INSERT INTO participantes (
      foto, nome_completo, nascimento, sexo, cpf, rg, passaporte, celular, email, como_soube,
      cep, endereco_rua, endereco_numero, endereco_complemento, cidade, estado, bairro, sobre_participante,
      pode_vincular_rituais, motivo_bloqueio_vinculacao
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  ");

    $stmt->execute([
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
      $motivo_bloqueio_vinculacao
    ]);

    $novoParticipanteId = $pdo->lastInsertId();

    if ($ritual_id_from_redirect) {
      $stmt_inscricao = $pdo->prepare("
        INSERT INTO inscricoes (ritual_id, participante_id)
        VALUES (?, ?)
      ");
      $stmt_inscricao->execute([$ritual_id_from_redirect, $novoParticipanteId]);

      $_SESSION['success'] = 'Participante criado e vinculado ao ritual com sucesso!';
      header("Location: $redirect");
      exit;
    } else {
      $_SESSION['success'] = 'Pessoa cadastrada com sucessoo!';
      header('Location: /participantes');
      exit;
    }

  } catch (PDOException $e) {
    error_log("Erro ao criar participante: " . $e->getMessage());
    $_SESSION['error'] = 'Erro ao criar participante. Tente novamente.';
    header('Location: /participante/novo');
    exit;
  }
}

// Se não for POST, mostrar formulário
require_once __DIR__ . '/../templates/novo.php';
