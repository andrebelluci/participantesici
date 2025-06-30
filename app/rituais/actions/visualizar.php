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

// ✅ PAGINAÇÃO - Adicionar estas variáveis
$pagina = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$itens_por_pagina = 10;
$offset = ($pagina - 1) * $itens_por_pagina;

// Buscar participantes com filtro e dados completos de inscrição
$filtro_nome = isset($_GET['filtro_nome']) ? trim($_GET['filtro_nome']) : '';

// ✅ ORDENAÇÃO - Adicionar estas variáveis
$order_by = isset($_GET['order_by']) ? $_GET['order_by'] : 'nome_completo'; // Coluna padrão: nome_completo
$order_dir = isset($_GET['order_dir']) ? $_GET['order_dir'] : 'ASC'; // Direção padrão: ASC

// ✅ CONSULTA DE CONTAGEM - Para calcular total de páginas
$sql_count = "
    SELECT COUNT(*) AS total
    FROM inscricoes i
    JOIN participantes p ON i.participante_id = p.id
    WHERE i.ritual_id = ?
";
$params_count = [$id];

if (!empty($filtro_nome)) {
  $sql_count .= " AND p.nome_completo LIKE ?";
  $params_count[] = "%$filtro_nome%";
}

$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params_count);
$total_registros = $stmt_count->fetch()['total'];
$total_paginas = ceil($total_registros / $itens_por_pagina);

// ✅ CONSULTA PRINCIPAL - Modificar a consulta existente para incluir paginação
$sql_participantes = "
    SELECT p.*, i.presente, i.observacao,
           i.primeira_vez_instituto, i.primeira_vez_ayahuasca,
           i.doenca_psiquiatrica, i.nome_doenca,
           i.uso_medicao, i.nome_medicao, i.mensagem,
           i.salvo_em, i.obs_salvo_em
    FROM inscricoes i
    JOIN participantes p ON i.participante_id = p.id
    WHERE i.ritual_id = ?
";
$params = [$id];

if (!empty($filtro_nome)) {
  $sql_participantes .= " AND p.nome_completo LIKE ?";
  $params[] = "%$filtro_nome%";
}

// ✅ Adicionar ordenação e paginação
$sql_participantes .= " ORDER BY $order_by $order_dir LIMIT $itens_por_pagina OFFSET $offset";

$stmt_participantes = $pdo->prepare($sql_participantes);
$stmt_participantes->execute($params);
$participantes = $stmt_participantes->fetchAll();

// Carregar template
require_once __DIR__ . '/../templates/visualizar.php';