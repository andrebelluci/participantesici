<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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

  // Processamento do formulário (mantido igual)
  $foto = null;
  if (!empty($_FILES['foto']['name'])) {
    $foto_nome = uniqid() . '_' . basename($_FILES['foto']['name']);
    $foto_destino = __DIR__ . '/../../../storage/uploads/participantes' . $foto_nome;
    move_uploaded_file($_FILES['foto']['tmp_name'], $foto_destino);
    $foto = '/participantesici/storage/uploads/participantes' . $foto_nome;
  }

  // Verifica se o e-mail é válido
  if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._%+-]{3,}@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
    die("Erro: Por favor, digite um e-mail válido.");
  }

  // Verifica se o CPF já existe no banco de dados
  $stmt_check_cpf = $pdo->prepare("SELECT id FROM participantes WHERE cpf = ?");
  $stmt_check_cpf->execute([$cpf]);
  if ($stmt_check_cpf->rowCount() > 0) {
    die("<script>alert('Erro: Este CPF já está cadastrado.'); window.location.href = '/participantesici/public_html/participante/novo';</script>");
  }

  $stmt = $pdo->prepare("
      INSERT INTO participantes (
          foto, nome_completo, nascimento, sexo, cpf, rg, passaporte, celular, email, como_soube, cep, endereco_rua, endereco_numero, endereco_complemento, cidade, estado, bairro, sobre_participante
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  ");
  $stmt->execute([$foto, $nome_completo, $nascimento, $sexo, $cpf, $rg, $passaporte, $celular, $email, $como_soube, $cep, $endereco_rua, $endereco_numero, $endereco_complemento, $cidade, $estado, $bairro, $sobre_participante]);

  $novoParticipanteId = $pdo->lastInsertId();

  // Redireciona para a página original, se houver parâmetros de redirecionamento
  if (isset($_GET['redirect']) && isset($_GET['id'])) {
    $redirectUrl = $_GET['redirect'];
    $ritualId = $_GET['id'];

    // Insere o novo participante no ritual
    $stmt = $pdo->prepare("
          INSERT INTO inscricoes (ritual_id, participante_id)
          VALUES (?, ?)
      ");
    $stmt->execute([$ritualId, $novoParticipanteId]);

    echo "<script>alert('Pessoa cadastrada e vinculada ao ritual com sucesso!');</script>";
    echo "<script>window.location.href = '$redirectUrl?id=$ritualId';</script>";
    exit;
  } else {
    echo "<script>alert('Pessoa cadastrada com sucesso!');</script>";
    echo "<script>window.location.href = '/participantesici/public_html/participantes';</script>";
  }
}

// Se não for POST, mostra o formulário
require __DIR__ . '/../templates/novo.php';