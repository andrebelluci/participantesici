<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../config/database.php';

// Verificar se usuário é administrador
$stmt = $pdo->prepare("
    SELECT p.nome as perfil_nome
    FROM usuarios u
    JOIN perfis p ON u.perfil_id = p.id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user_perfil = $stmt->fetch();

if (!$user_perfil || $user_perfil['perfil_nome'] !== 'Administrador') {
  $_SESSION['error'] = 'Acesso negado. Área restrita para administradores.';
  header('Location: /home');
  exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
  $_SESSION['error'] = 'ID do usuário inválido.';
  header('Location: /usuarios');
  exit;
}

// Buscar usuário
$stmt = $pdo->prepare("
    SELECT u.*, p.nome as perfil_nome
    FROM usuarios u
    JOIN perfis p ON u.perfil_id = p.id
    WHERE u.id = ?
");
$stmt->execute([$id]);
$usuario = $stmt->fetch();

if (!$usuario) {
  $_SESSION['error'] = 'Usuário não encontrado.';
  header('Location: /usuarios');
  exit;
}

// Buscar perfis disponíveis
$stmt_perfis = $pdo->prepare("SELECT * FROM perfis ORDER BY nome");
$stmt_perfis->execute();
$perfis = $stmt_perfis->fetchAll();

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nome = trim($_POST['nome'] ?? '');
  $usuario_nome = trim($_POST['usuario'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $perfil_id = filter_input(INPUT_POST, 'perfil_id', FILTER_VALIDATE_INT);
  $nova_senha = trim($_POST['nova_senha'] ?? '');

  // Validações
  if (empty($nome) || empty($usuario_nome) || empty($email) || !$perfil_id) {
    $_SESSION['error'] = 'Todos os campos são obrigatórios.';
    header("Location: /usuario/$id/editar");
    exit;
  }

  // Validar email
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'E-mail inválido.';
    header("Location: /usuario/$id/editar");
    exit;
  }

  // Verificar se usuário já existe (exceto o atual)
  $stmt_check = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ? AND id != ?");
  $stmt_check->execute([$usuario_nome, $id]);
  if ($stmt_check->fetch()) {
    $_SESSION['error'] = 'Nome de usuário já existe.';
    header("Location: /usuario/$id/editar");
    exit;
  }

  // Verificar se email já existe (exceto o atual)
  $stmt_check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
  $stmt_check->execute([$email, $id]);
  if ($stmt_check->fetch()) {
    $_SESSION['error'] = 'E-mail já está sendo usado por outro usuário.';
    header("Location: /usuario/$id/editar");
    exit;
  }

  try {
    // Preparar dados para atualização
    $sql = "UPDATE usuarios SET nome = ?, usuario = ?, email = ?, perfil_id = ?";
    $params = [$nome, $usuario_nome, $email, $perfil_id];

    // Se nova senha foi informada, adicionar na consulta
    if (!empty($nova_senha)) {
      // Validar nova senha
      if (strlen($nova_senha) < 8) {
        $_SESSION['error'] = 'A nova senha deve ter pelo menos 8 caracteres.';
        header("Location: /usuario/$id/editar");
        exit;
      }
      $sql .= ", senha = ?";
      $params[] = hash('sha256', $nova_senha);
    }

    $sql .= " WHERE id = ?";
    $params[] = $id;

    $stmt_update = $pdo->prepare($sql);
    $stmt_update->execute($params);

    $_SESSION['success'] = 'Usuário atualizado com sucesso!';
    header('Location: /usuarios');
    exit;

  } catch (Exception $e) {
    $_SESSION['error'] = 'Erro ao atualizar usuário: ' . $e->getMessage();
    header("Location: /usuario/$id/editar");
    exit;
  }
}

// Carregar template
require_once __DIR__ . '/../templates/editar.php';