<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../config/database.php';

// Obtém o ID da pessoa a ser editada
$id = $_GET['id'] ?? null;
if (!$id) {
  die("ID da pessoa não especificado.");
}
$redirect = $_GET['redirect'] ?? '/participantesici/public_html/participantes'; // Página padrão se não houver redirect


// Consulta os dados da pessoa no banco de dados
$stmt = $pdo->prepare("SELECT * FROM participantes WHERE id = ?");
$stmt->execute([$id]);
$pessoa = $stmt->fetch();

if (!$pessoa) {
  die("Pessoa não encontrada.");
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

  // Processar upload de nova foto
  if (!empty($_FILES['foto']['name'])) {
    $foto_nome = uniqid() . '_' . basename($_FILES['foto']['name']);
    $foto_destino = __DIR__ . '/../../../storage/uploads/participantes/' . $foto_nome;

    // Criar diretório se não existir
    if (!is_dir(dirname($foto_destino))) {
      mkdir(dirname($foto_destino), 0755, true);
    }

    if (move_uploaded_file($_FILES['foto']['tmp_name'], $foto_destino)) {
      $foto = '/participantesici/storage/uploads/participantes/' . $foto_nome;

      // Opcional: remover a foto antiga se existir
      if ($pessoa['foto'] && file_exists(__DIR__ . '/../../..' . $pessoa['foto'])) {
        unlink(__DIR__ . '/../../..' . $pessoa['foto']);
      }
    }
  }

  // Verifica se o e-mail é válido
  if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._%+-]{3,}@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
    die("Erro: Por favor, digite um e-mail válido.");
  }

  // Verifica se o CPF já existe no banco de dados (ignorando o próprio participante)
  $stmt_check_cpf = $pdo->prepare("SELECT id FROM participantes WHERE cpf = ? AND id != ?");
  $stmt_check_cpf->execute([$cpf, $id]); // Passa o CPF e o ID do participante atual
  if ($stmt_check_cpf->rowCount() > 0) {
    die("<script>alert('Erro: Este CPF já está cadastrado.'); window.location.href = 'participante-editar?id=$id';</script>");
  }

  // Atualiza os dados no banco de dados
  $stmt_update = $pdo->prepare("
        UPDATE participantes SET
            foto = ?, nome_completo = ?, nascimento = ?, sexo = ?, cpf = ?, rg = ?, passaporte = ?,
            celular = ?, email = ?, como_soube = ?, cep = ?, endereco_rua = ?, endereco_numero = ?,
            endereco_complemento = ?, cidade = ?, estado = ?, bairro = ?, sobre_participante = ?
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
    $id
  ]);

  echo "<script>alert('Pessoa atualizada com sucesso!');</script>";
  echo "<script>window.location.href = '$redirect?id=$id';</script>";
}

// Se não for POST, mostrar formulário
require __DIR__ . '/../templates/editar.php';