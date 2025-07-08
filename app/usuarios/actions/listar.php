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

// Processar parâmetros
$pagina = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$itens_por_pagina = 9;
$offset = ($pagina - 1) * $itens_por_pagina;

// Construir consulta com filtros
$where = "1=1";
$params = [];
$filtros = [];

if (!empty($_GET['filtro_nome'])) {
  $where .= " AND (u.nome LIKE ? OR u.usuario LIKE ?)";
  $params[] = "%{$_GET['filtro_nome']}%";
  $params[] = "%{$_GET['filtro_nome']}%";
  $filtros['filtro_nome'] = $_GET['filtro_nome'];
}

// Ordenação
$order_by = in_array($_GET['order_by'] ?? '', ['nome', 'usuario', 'email', 'perfil_nome'])
  ? $_GET['order_by']
  : 'nome';
$order_dir = ($_GET['order_dir'] ?? '') === 'ASC' ? 'ASC' : 'DESC';

// Contar total
$stmt_count = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM usuarios u
    JOIN perfis p ON u.perfil_id = p.id
    WHERE $where
");
$stmt_count->execute($params);
$total_registros = $stmt_count->fetch()['total'];
$total_paginas = ceil($total_registros / $itens_por_pagina);

// Buscar dados
$sql = "
    SELECT u.*, p.nome as perfil_nome
    FROM usuarios u
    JOIN perfis p ON u.perfil_id = p.id
    WHERE $where
    ORDER BY $order_by $order_dir
    LIMIT $itens_por_pagina OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

// Incluir template
require_once __DIR__ . '/../templates/listar.php';