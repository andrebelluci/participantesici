<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $nome = $_POST['nome'];
  $data_ritual = $_POST['data_ritual'];
  $padrinho_madrinha = $_POST['padrinho_madrinha'];

  // Upload da foto
  $foto = null;
  if (!empty($_FILES['foto']['name'])) {
    $foto_nome = uniqid() . '_' . basename($_FILES['foto']['name']);
    $foto_destino = __DIR__ . '/../../../storage/uploads/rituais' . $foto_nome;
    move_uploaded_file($_FILES['foto']['tmp_name'], $foto_destino);
    $foto = '/participantesici/storage/uploads/rituais' . $foto_nome;
  }

  $stmt = $pdo->prepare("INSERT INTO rituais (nome, data_ritual, foto, padrinho_madrinha) VALUES (?, ?, ?, ?)");
  $stmt->execute([$nome, $data_ritual, $foto, $padrinho_madrinha]);

  $novoRitualId = $pdo->lastInsertId();

  if (isset($_GET['redirect']) && isset($_GET['id'])) {
    $redirectUrl = $_GET['redirect'];
    $participanteId = $_GET['id'];

    // Insere o novo ritual ao participante
    $stmt = $pdo->prepare("
            INSERT INTO inscricoes (ritual_id, participante_id)
            VALUES (?, ?)
        ");
    $stmt->execute([$novoRitualId, $participanteId]);

    $_SESSION['success'] = 'Ritual criado e vinculado ao participante com sucesso!';
    header("Location: $redirectUrl?id=$participanteId");
  } else {
    $_SESSION['success'] = 'Ritual criado com sucesso!';
    header("Location: /participantesici/public_html/rituais");
  }
  exit;
}

// Se não for POST, mostra o formulário
require __DIR__ . '/../templates/novo.php';