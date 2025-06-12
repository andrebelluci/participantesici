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

// Buscar participantes com filtro
$filtro_nome = isset($_GET['filtro_nome']) ? trim($_GET['filtro_nome']) : '';
$sql_participantes = "
    SELECT p.*, i.presente, i.observacao
    FROM inscricoes i
    JOIN participantes p ON i.participante_id = p.id
    WHERE i.ritual_id = ?
";
$params = [$id];

if (!empty($filtro_nome)) {
  $sql_participantes .= " AND p.nome_completo LIKE ?";
  $params[] = "%$filtro_nome%";
}

$stmt_participantes = $pdo->prepare($sql_participantes);
$stmt_participantes->execute($params);
$participantes = $stmt_participantes->fetchAll();

// Carregar template
require __DIR__ . '/../templates/visualizar.php';