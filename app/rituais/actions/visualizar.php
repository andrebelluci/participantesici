<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
  $_SESSION['error'] = 'ID do ritual inválido.';
  header('Location: /rituais');
  exit;
}

// Buscar ritual
$stmt = $pdo->prepare("SELECT * FROM rituais WHERE id = ?");
$stmt->execute([$id]);
$ritual = $stmt->fetch();

if (!$ritual) {
  $_SESSION['error'] = 'Ritual não encontrado.';
  header('Location: /rituais');
  exit;
}

// ✅ PAGINAÇÃO - Adicionar estas variáveis
$pagina = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$itens_por_pagina = 9;
$offset = ($pagina - 1) * $itens_por_pagina;

// Buscar participantes com filtro e dados completos de inscrição
$filtro_nome = isset($_GET['filtro_nome']) ? trim($_GET['filtro_nome']) : '';
$filtro_aniversariantes = isset($_GET['filtro_aniversariantes']) && $_GET['filtro_aniversariantes'] == '1';

// ✅ ORDENAÇÃO - Adicionar estas variáveis
$order_by = isset($_GET['order_by']) ? $_GET['order_by'] : 'nome_completo'; // Coluna padrão: nome_completo
$order_dir = isset($_GET['order_dir']) ? $_GET['order_dir'] : 'ASC'; // Direção padrão: ASC

// ✅ CONSULTA PRINCIPAL - Buscar todos os participantes (sem paginação) para aplicar filtro de aniversariantes
$sql_participantes = "
    SELECT p.*, i.id as inscricao_id, i.presente, i.observacao,
           i.primeira_vez_instituto, i.primeira_vez_ayahuasca,
           i.doenca_psiquiatrica, i.nome_doenca,
           i.uso_medicao, i.nome_medicao, i.mensagem,
           i.salvo_em, i.obs_salvo_em,
           i.assinatura, i.assinatura_data
    FROM inscricoes i
    JOIN participantes p ON i.participante_id = p.id
    WHERE i.ritual_id = ?
";
$params = [$id];

if (!empty($filtro_nome)) {
  $sql_participantes .= " AND p.nome_completo LIKE ?";
  $params[] = "%$filtro_nome%";
}

// Adicionar ordenação
$sql_participantes .= " ORDER BY $order_by $order_dir";

$stmt_participantes = $pdo->prepare($sql_participantes);
$stmt_participantes->execute($params);
$todos_participantes = $stmt_participantes->fetchAll();

// Aplicar filtro de aniversariantes se necessário
if ($filtro_aniversariantes) {
  $todos_participantes = array_filter($todos_participantes, function($participante) use ($ritual) {
    if (empty($participante['nascimento'])) {
      return false;
    }
    return aniversarioNoIntervalo($participante['nascimento'], $ritual['data_ritual']);
  });
  $todos_participantes = array_values($todos_participantes);
}

// Calcular total após filtros
$total_registros = count($todos_participantes);
$total_paginas = ceil($total_registros / $itens_por_pagina);

// Aplicar paginação
$participantes = array_slice($todos_participantes, $offset, $itens_por_pagina);

// Buscar contagem de participantes presentes (independente da paginação)
$sql_presentes = "
    SELECT COUNT(*) AS total_presentes
    FROM inscricoes i
    WHERE i.ritual_id = ? AND i.presente = 'Sim'
";
$stmt_presentes = $pdo->prepare($sql_presentes);
$stmt_presentes->execute([$id]);
$total_presentes = $stmt_presentes->fetch()['total_presentes'];

// Buscar total de participantes (independente da paginação)
$sql_total_inscritos = "
    SELECT COUNT(*) AS total_inscritos
    FROM inscricoes i
    WHERE i.ritual_id = ?
";
$stmt_total_inscritos = $pdo->prepare($sql_total_inscritos);
$stmt_total_inscritos->execute([$id]);
$total_inscritos = $stmt_total_inscritos->fetch()['total_inscritos'];

// Buscar contagem de participantes por sexo (independente da paginação)
$sql_masculinos = "
    SELECT COUNT(*) AS total_masculinos
    FROM inscricoes i
    JOIN participantes p ON i.participante_id = p.id
    WHERE i.ritual_id = ? AND p.sexo = 'M'
";
$stmt_masculinos = $pdo->prepare($sql_masculinos);
$stmt_masculinos->execute([$id]);
$total_masculinos = $stmt_masculinos->fetch()['total_masculinos'];

$sql_femininos = "
    SELECT COUNT(*) AS total_femininos
    FROM inscricoes i
    JOIN participantes p ON i.participante_id = p.id
    WHERE i.ritual_id = ? AND p.sexo = 'F'
";
$stmt_femininos = $pdo->prepare($sql_femininos);
$stmt_femininos->execute([$id]);
$total_femininos = $stmt_femininos->fetch()['total_femininos'];

// Função para verificar se aniversário está no intervalo do ritual
function aniversarioNoIntervalo($nascimento, $dataRitual)
{
  if (empty($nascimento)) {
    return false;
  }

  $dataRitualObj = new DateTime($dataRitual);
  $nascimentoObj = new DateTime($nascimento);

  // Extrair dia e mês do nascimento
  $diaNascimento = (int) $nascimentoObj->format('d');
  $mesNascimento = (int) $nascimentoObj->format('m');

  // Calcular intervalo: 7 dias antes e depois da data do ritual
  $dataInicio = clone $dataRitualObj;
  $dataInicio->modify('-7 days');
  $dataFim = clone $dataRitualObj;
  $dataFim->modify('+7 days');

  // Criar data de aniversário no ano do ritual para comparação
  $anoRitual = (int) $dataRitualObj->format('Y');

  // Verificar aniversário no ano do ritual
  try {
    $aniversarioAnoRitual = new DateTime("$anoRitual-$mesNascimento-$diaNascimento");
    if ($aniversarioAnoRitual >= $dataInicio && $aniversarioAnoRitual <= $dataFim) {
      return true;
    }
  } catch (Exception $e) {
    // Data inválida (ex: 29/02 em ano não bissexto)
  }

  // Verificar aniversário no ano anterior (para casos próximos ao fim do ano)
  try {
    $aniversarioAnoAnterior = new DateTime(($anoRitual - 1) . "-$mesNascimento-$diaNascimento");
    if ($aniversarioAnoAnterior >= $dataInicio && $aniversarioAnoAnterior <= $dataFim) {
      return true;
    }
  } catch (Exception $e) {
    // Data inválida
  }

  // Verificar aniversário no ano seguinte (para casos próximos ao início do ano)
  try {
    $aniversarioAnoSeguinte = new DateTime(($anoRitual + 1) . "-$mesNascimento-$diaNascimento");
    if ($aniversarioAnoSeguinte >= $dataInicio && $aniversarioAnoSeguinte <= $dataFim) {
      return true;
    }
  } catch (Exception $e) {
    // Data inválida
  }

  return false;
}

// Função para verificar se os detalhes obrigatórios estão preenchidos
function temDetalhesCompletos($inscricao)
{
  // Campos obrigatórios básicos
  if (
    empty($inscricao['primeira_vez_instituto']) ||
    empty($inscricao['primeira_vez_ayahuasca']) ||
    empty($inscricao['doenca_psiquiatrica']) ||
    empty($inscricao['uso_medicao'])
  ) {
    return false;
  }

  // Se tem doença psiquiátrica, nome_doenca é obrigatório
  if ($inscricao['doenca_psiquiatrica'] === 'Sim' && empty($inscricao['nome_doenca'])) {
    return false;
  }

  // Se usa medicação, nome_medicao é obrigatório
  if ($inscricao['uso_medicao'] === 'Sim' && empty($inscricao['nome_medicao'])) {
    return false;
  }

  return true;
}

// Determinar o tipo e ID baseado na URL atual
$current_path = $_SERVER['REQUEST_URI'];
$is_participante = strpos($current_path, '/participante/') !== false;
$is_ritual = strpos($current_path, '/ritual/') !== false;

// Extrair ID da URL
if ($is_participante && preg_match('/\/participante\/(\d+)/', $current_path, $matches)) {
  $export_id = $matches[1];
  $export_type = 'participante';
} elseif ($is_ritual && preg_match('/\/ritual\/(\d+)/', $current_path, $matches)) {
  $export_id = $matches[1];
  $export_type = 'ritual';
} else {
  $export_id = null;
  $export_type = null;
}

// Carregar template
require_once __DIR__ . '/../templates/visualizar.php';