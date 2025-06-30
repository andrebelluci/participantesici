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
  header('Location: /participantesici/public_html/home');
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
  $senha = trim($_POST['senha'] ?? '');

  // Validações
  if (empty($nome) || empty($usuario_nome) || empty($email) || !$perfil_id || empty($senha)) {
    $_SESSION['error'] = 'Todos os campos são obrigatórios.';
    header('Location: /participantesici/public_html/usuario/novo');
    exit;
  }

  // Validar email
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'E-mail inválido.';
    header('Location: /participantesici/public_html/usuario/novo');
    exit;
  }

  // Validar senha
  if (strlen($senha) < 8) {
    $_SESSION['error'] = 'A senha deve ter pelo menos 8 caracteres.';
    header('Location: /participantesici/public_html/usuario/novo');
    exit;
  }

  // Verificar se usuário já existe
  $stmt_check = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
  $stmt_check->execute([$usuario_nome]);
  if ($stmt_check->fetch()) {
    $_SESSION['error'] = 'Nome de usuário já existe.';
    header('Location: /participantesici/public_html/usuario/novo');
    exit;
  }

  // Verificar se email já existe
  $stmt_check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
  $stmt_check->execute([$email]);
  if ($stmt_check->fetch()) {
    $_SESSION['error'] = 'E-mail já está sendo usado por outro usuário.';
    header('Location: /participantesici/public_html/usuario/novo');
    exit;
  }

  try {
    // Inserir novo usuário
    $stmt_insert = $pdo->prepare("
            INSERT INTO usuarios (nome, usuario, email, senha, perfil_id)
            VALUES (?, ?, ?, ?, ?)
        ");
    $stmt_insert->execute([
      $nome,
      $usuario_nome,
      $email,
      hash('sha256', $senha),
      $perfil_id
    ]);

    $_SESSION['success'] = 'Usuário criado com sucesso!';
    header('Location: /participantesici/public_html/usuarios');
    exit;

  } catch (Exception $e) {
    $_SESSION['error'] = 'Erro ao criar usuário: ' . $e->getMessage();
    header('Location: /participantesici/public_html/usuario/novo');
    exit;
  }
}

// Carregar template
require_once __DIR__ . '/../templates/novo.php';