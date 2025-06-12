<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../config/database.php';

// Paginação
$pagina = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$itens_por_pagina = 10;
$offset = ($pagina - 1) * $itens_por_pagina;

// Filtros
$filtro_nome = isset($_GET['filtro_nome']) ? $_GET['filtro_nome'] : '';
$filtro_cpf = isset($_GET['filtro_cpf']) ? $_GET['filtro_cpf'] : '';

// Ordenação
$order_by = isset($_GET['order_by']) ? $_GET['order_by'] : 'nome_completo'; // Coluna padrão: nome_completo
$order_dir = isset($_GET['order_dir']) ? $_GET['order_dir'] : 'ASC'; // Direção padrão: ASC

$where = "";
$params = [];
if (!empty($filtro_nome)) {
  $where .= " AND nome_completo LIKE ?";
  $params[] = "%$filtro_nome%";
}
if (!empty($filtro_cpf)) {
  $where .= " AND cpf LIKE ?";
  $params[] = "%$filtro_cpf%";
}

// Consulta para contar o total de registros
$stmt_count = $pdo->prepare("SELECT COUNT(*) AS total FROM participantes WHERE 1=1 $where");
$stmt_count->execute($params);
$total_registros = $stmt_count->fetch()['total'];
$total_paginas = ceil($total_registros / $itens_por_pagina);

// Consulta para listar as pessoas com o número de rituais em que estiveram presentes
$sql = "
    SELECT p.*, COUNT(i.id) AS rituais_participados
    FROM participantes p
    LEFT JOIN inscricoes i ON p.id = i.participante_id AND i.presente = 'Sim'
    WHERE 1=1 $where
    GROUP BY p.id
    ORDER BY $order_by $order_dir
    LIMIT $itens_por_pagina OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pessoas = $stmt->fetchAll();

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

// Função para formatar telefone
function formatarTelefone($telefone)
{
  // Remove caracteres não numéricos
  $telefone = preg_replace('/[^0-9]/', '', $telefone);
  // Verifica o tamanho do número (fixo ou celular)
  if (strlen($telefone) === 10) { // Telefone fixo: (##) ####-####
    return '(' . substr($telefone, 0, 2) . ') ' .
      substr($telefone, 2, 4) . '-' .
      substr($telefone, 6, 4);
  } elseif (strlen($telefone) === 11) { // Celular: (##) #####-####
    return '(' . substr($telefone, 0, 2) . ') ' .
      substr($telefone, 2, 5) . '-' .
      substr($telefone, 7, 4);
  } else {
    return $telefone; // Retorna o valor original caso o formato seja inválido
  }
}

// Incluir template
require __DIR__ . '/../templates/listar.php';
