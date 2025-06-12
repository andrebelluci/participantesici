<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../config/database.php';

// Processar parâmetros
$pagina = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$itens_por_pagina = 10;
$offset = ($pagina - 1) * $itens_por_pagina;

// Construir consulta com filtros
$where = "1=1";
$params = [];
$filtros = [];

if (!empty($_GET['filtro_nome'])) {
  $where .= " AND r.nome LIKE ?";
  $params[] = "%{$_GET['filtro_nome']}%";
  $filtros['filtro_nome'] = $_GET['filtro_nome'];
}

if (!empty($_GET['data_inicio']) && !empty($_GET['data_fim'])) {
  $where .= " AND r.data_ritual BETWEEN ? AND ?";
  $params[] = $_GET['data_inicio'];
  $params[] = $_GET['data_fim'];
  $filtros['data_inicio'] = $_GET['data_inicio'];
  $filtros['data_fim'] = $_GET['data_fim'];
}

// Ordenação
$order_by = in_array($_GET['order_by'] ?? '', ['nome', 'data_ritual', 'inscritos'])
  ? $_GET['order_by']
  : 'data_ritual';
$order_dir = ($_GET['order_dir'] ?? '') === 'ASC' ? 'ASC' : 'DESC';

// Contar total
$stmt_count = $pdo->prepare("SELECT COUNT(*) AS total FROM rituais r WHERE $where");
$stmt_count->execute($params);
$total_registros = $stmt_count->fetch()['total'];
$total_paginas = ceil($total_registros / $itens_por_pagina);

// Buscar dados
$sql = "
    SELECT r.*, COUNT(i.id) AS inscritos
    FROM rituais r
    LEFT JOIN inscricoes i ON r.id = i.ritual_id
    WHERE $where
    GROUP BY r.id
    ORDER BY $order_by $order_dir
    LIMIT $itens_por_pagina OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rituais = $stmt->fetchAll();

// Incluir template
require __DIR__ . '/../templates/listar.php';