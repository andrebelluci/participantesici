<?php
require_once __DIR__ . '/../../config/database.php';

$cpf = $_GET['cpf'] ?? null;
$id = $_GET['id'] ?? null;

// Função para validar o CPF
function validarCPF($cpf)
{
  // ✅ Remove máscara (pontos, hífens, espaços)
  $cpf = preg_replace('/[^0-9]/', '', $cpf);

  // ✅ Verifica se tem menos de 11 dígitos
  if (strlen($cpf) < 11) {
    return ['valid' => false, 'error' => 'CPF deve ter 11 dígitos'];
  }

  // ✅ Verifica se tem mais de 11 dígitos
  if (strlen($cpf) > 11) {
    return ['valid' => false, 'error' => 'CPF deve ter exatamente 11 dígitos'];
  }

  // ✅ Verifica se todos os dígitos são iguais (ex: 111.111.111-11)
  if (preg_match('/(\d)\1{10}/', $cpf)) {
    return ['valid' => false, 'error' => 'CPF inválido'];
  }

  // ✅ Calcula o primeiro dígito verificador
  $soma = 0;
  for ($i = 0; $i < 9; $i++) {
    $soma += $cpf[$i] * (10 - $i);
  }
  $resto = $soma % 11;
  $digito1 = ($resto < 2) ? 0 : 11 - $resto;

  // ✅ Calcula o segundo dígito verificador
  $soma = 0;
  for ($i = 0; $i < 10; $i++) {
    $soma += $cpf[$i] * (11 - $i);
  }
  $resto = $soma % 11;
  $digito2 = ($resto < 2) ? 0 : 11 - $resto;

  // ✅ Verifica se os dígitos verificadores estão corretos
  if ($cpf[9] != $digito1 || $cpf[10] != $digito2) {
    return ['valid' => false, 'error' => 'CPF inválido'];
  }

  // ✅ CPF válido
  return ['valid' => true, 'cpf_clean' => $cpf];
}

// ✅ Valida se CPF foi informado
if (!$cpf) {
  echo json_encode(['exists' => false, 'error' => 'CPF não informado']);
  exit;
}

// ✅ Valida se CPF está vazio ou só com espaços
if (trim($cpf) === '') {
  echo json_encode(['exists' => false, 'error' => 'CPF não pode estar vazio']);
  exit;
}

// ✅ Primeiro, valida o formato e dígitos do CPF
$validacao = validarCPF($cpf);

if (!$validacao['valid']) {
  echo json_encode(['exists' => false, 'error' => $validacao['error']]);
  exit;
}

// ✅ Usa o CPF limpo (sem máscara) para consulta no banco
$cpfLimpo = $validacao['cpf_clean'];

try {
  // ✅ Verifica se o CPF já existe no banco de dados
  $sql = "SELECT id FROM participantes WHERE cpf = ?";
  $params = [$cpfLimpo];

  // ✅ Ignora o ID do participante atual, se fornecido (para edição)
  if ($id && is_numeric($id)) {
    $sql .= " AND id != ?";
    $params[] = $id;
  }

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $exists = $stmt->rowCount() > 0;

  // ✅ Retorna resultado da validação
  echo json_encode([
    'exists' => $exists,
    'cpf_clean' => $cpfLimpo,
    'message' => $exists ? 'CPF já cadastrado' : 'CPF disponível'
  ]);

} catch (Exception $e) {
  // ✅ Trata erros de banco de dados
  echo json_encode([
    'exists' => false,
    'error' => 'Erro interno do servidor'
  ]);
}
