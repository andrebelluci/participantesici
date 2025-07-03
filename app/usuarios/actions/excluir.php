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

// Não permitir que usuário exclua a si mesmo
if ($id == $_SESSION['user_id']) {
  $_SESSION['error'] = 'Você não pode excluir seu próprio usuário.';
  header('Location: /usuarios');
  exit;
}

// Buscar usuário
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch();

if (!$usuario) {
  $_SESSION['error'] = 'Usuário não encontrado.';
  header('Location: /usuarios');
  exit;
}

// Verificar se é o último administrador
$stmt_admin_count = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM usuarios u
    JOIN perfis p ON u.perfil_id = p.id
    WHERE p.nome = 'Administrador'
");
$stmt_admin_count->execute();
$admin_count = $stmt_admin_count->fetch()['total'];

$stmt_is_admin = $pdo->prepare("
    SELECT p.nome as perfil_nome
    FROM usuarios u
    JOIN perfis p ON u.perfil_id = p.id
    WHERE u.id = ?
");
$stmt_is_admin->execute([$id]);
$usuario_perfil = $stmt_is_admin->fetch();

if ($usuario_perfil && $usuario_perfil['perfil_nome'] === 'Administrador' && $admin_count <= 1) {
  $_SESSION['error'] = 'Não é possível excluir o último administrador do sistema.';
  header('Location: /usuarios');
  exit;
}

try {
  // Excluir usuário
  $stmt_delete = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
  $stmt_delete->execute([$id]);

  $_SESSION['success'] = "Usuário '{$usuario['nome']}' foi excluído com sucesso!";
  header('Location: /usuarios');
  exit;

} catch (Exception $e) {
  $_SESSION['error'] = 'Erro ao excluir usuário: ' . $e->getMessage();
  header('Location: /usuarios');
  exit;
}