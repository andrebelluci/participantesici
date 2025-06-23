<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../config/database.php';

// Obter o ID do participante da URL
$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM participantes WHERE id = ?");
$stmt->execute([$id]);
$pessoa = $stmt->fetch();

// ✅ FUNÇÃO PARA CONTAR TOTAL DE RITUAIS DO PARTICIPANTE
function contarRituaisParticipados($pdo, $participante_id)
{
  $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM inscricoes WHERE participante_id = ? AND presente = 'Sim'");
  $stmt->execute([$participante_id]);
  return $stmt->fetch()['total'];
}

function contarRituaisNaoParticipados($pdo, $participante_id)
{
  $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM inscricoes WHERE participante_id = ? AND presente = 'Não'");
  $stmt->execute([$participante_id]);
  return $stmt->fetch()['total'];
}

function formatarCPF($cpf)
{
  // Remove caracteres não numéricos
  $cpf = preg_replace('/[^0-9]/', '', $cpf);
  // Aplica a máscara ###.###.###-##
  return substr($cpf, 0, 3) . '.' .
    substr($cpf, 3, 3) . '.' .
    substr($cpf, 6, 3) . '-' .
    substr($cpf, 9, 2);
}

function formatarCEP($cep)
{
  if (empty($cep))
    return '';

  $cep = preg_replace('/\D/', '', $cep);

  if (strlen($cep) !== 8)
    return $cep;

  return substr($cep, 0, 5) . '-' . substr($cep, 5);
}

/**
 * Formatar celular com máscara (__) _____-____
 */
function formatarCelular($celular)
{
  if (empty($celular))
    return '';

  $celular = preg_replace('/\D/', '', $celular);

  // 11 dígitos (padrão com 9)
  if (strlen($celular) === 11) {
    return '(' . substr($celular, 0, 2) . ') ' .
      substr($celular, 2, 5) . '-' .
      substr($celular, 7);
  }
  // 10 dígitos (padrão antigo)
  elseif (strlen($celular) === 10) {
    return '(' . substr($celular, 0, 2) . ') ' .
      substr($celular, 2, 4) . '-' .
      substr($celular, 6);
  }

  return $celular;
}

if (!$pessoa) {
  die("Participante não encontrado.");
}

// ✅ ADICIONAR CONTAGEM TOTAL DE RITUAIS
$total_rituais_participados = contarRituaisParticipados($pdo, $id);
$total_rituais_nao_participados = contarRituaisParticipados($pdo, $id);

// Paginação
$pagina = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$itens_por_pagina = 10;
$offset = ($pagina - 1) * $itens_por_pagina;

// Filtro por Nome do Ritual
$filtro_nome = isset($_GET['filtro_nome']) ? trim($_GET['filtro_nome']) : '';

// Ordenação
$order_by = isset($_GET['order_by']) ? $_GET['order_by'] : 'data_ritual'; // Coluna padrão: data_ritual
$order_dir = isset($_GET['order_dir']) ? $_GET['order_dir'] : 'DESC'; // Direção padrão: DESC (mais novo primeiro)

// Consulta para contar o total de registros (COM FILTRO)
$sql_count = "
    SELECT COUNT(*) AS total
    FROM inscricoes i
    JOIN rituais r ON i.ritual_id = r.id
    WHERE i.participante_id = ?
";
$params_count = [$id];
if (!empty($filtro_nome)) {
  $sql_count .= " AND r.nome LIKE ?";
  $params_count[] = "%$filtro_nome%";
}
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params_count);
$total_registros = $stmt_count->fetch()['total'];
$total_paginas = ceil($total_registros / $itens_por_pagina);

// Consulta para listar os rituais com paginação e ordenação
$sql_rituais = "
    SELECT r.*, i.presente, i.observacao
    FROM inscricoes i
    JOIN rituais r ON i.ritual_id = r.id
    WHERE i.participante_id = ?
";
$params = [$id];
if (!empty($filtro_nome)) {
  $sql_rituais .= " AND r.nome LIKE ?";
  $params[] = "%$filtro_nome%";
}
$sql_rituais .= " ORDER BY $order_by $order_dir LIMIT $itens_por_pagina OFFSET $offset";
$stmt_rituais = $pdo->prepare($sql_rituais);
$stmt_rituais->execute($params);
$rituais = $stmt_rituais->fetchAll();

// Carregar template
require __DIR__ . '/../templates/visualizar.php';