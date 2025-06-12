<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
  $_SESSION['error'] = 'ID do ritual inválido.';
  header('Location: /participantesici/public_html/rituais');
  exit;
}

// Buscar ritual
$stmt = $pdo->prepare("SELECT * FROM rituais WHERE id = ?");
$stmt->execute([$id]);
$ritual = $stmt->fetch();

if (!$ritual) {
  $_SESSION['error'] = 'Ritual não encontrado.';
  header('Location: /participantesici/public_html/rituais');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $nome = $_POST['nome'];
  $data_ritual = $_POST['data_ritual'];
  $padrinho_madrinha = $_POST['padrinho_madrinha'];
  $foto = $ritual['foto']; // Mantém a foto atual por padrão

  // Processar upload de nova foto
  if (!empty($_FILES['foto']['name'])) {
    $foto_nome = uniqid() . '_' . basename($_FILES['foto']['name']);
    $foto_destino = __DIR__ . '/../../../storage/uploads/rituais/' . $foto_nome;

    // Criar diretório se não existir
    if (!is_dir(dirname($foto_destino))) {
      mkdir(dirname($foto_destino), 0755, true);
    }

    if (move_uploaded_file($_FILES['foto']['tmp_name'], $foto_destino)) {
      $foto = '/participantesici/storage/uploads/rituais/' . $foto_nome;

      // Opcional: remover a foto antiga se existir
      if ($ritual['foto'] && file_exists(__DIR__ . '/../../..' . $ritual['foto'])) {
        unlink(__DIR__ . '/../../..' . $ritual['foto']);
      }
    }
  }

  // Atualizar no banco
  $stmt_update = $pdo->prepare("UPDATE rituais SET nome = ?, data_ritual = ?, foto = ?, padrinho_madrinha = ? WHERE id = ?");
  $stmt_update->execute([$nome, $data_ritual, $foto, $padrinho_madrinha, $id]);

  $_SESSION['success'] = 'Ritual atualizado com sucesso!';
  header('Location: /participantesici/public_html/rituais');
  exit;

}

// Se não for POST, mostrar formulário
require __DIR__ . '/../templates/editar.php';