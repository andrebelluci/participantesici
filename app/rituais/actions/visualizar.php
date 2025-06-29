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

// Buscar participantes com filtro e dados completos de inscrição
$filtro_nome = isset($_GET['filtro_nome']) ? trim($_GET['filtro_nome']) : '';
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

// Ordenação por nome do participante
$sql_participantes .= " ORDER BY p.nome_completo ASC";

$stmt_participantes = $pdo->prepare($sql_participantes);
$stmt_participantes->execute($params);
$participantes = $stmt_participantes->fetchAll();

// Carregar template
require_once __DIR__ . '/../templates/visualizar.php';
