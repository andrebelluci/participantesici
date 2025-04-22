<?php
require_once 'includes/db.php';

$cpf = $_GET['cpf'] ?? null;
$id = $_GET['id'] ?? null;

// Função para validar o CPF
function validarCPF($cpf)
{
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) !== 11 || preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }

    // Calcula o primeiro dígito verificador
    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += $cpf[$i] * (10 - $i);
    }
    $resto = $soma % 11;
    $digito1 = ($resto < 2) ? 0 : 11 - $resto;

    // Calcula o segundo dígito verificador
    $soma = 0;
    for ($i = 0; $i < 10; $i++) {
        $soma += $cpf[$i] * (11 - $i);
    }
    $resto = $soma % 11;
    $digito2 = ($resto < 2) ? 0 : 11 - $resto;

    return $cpf[9] == $digito1 && $cpf[10] == $digito2;
}

if ($cpf) {
    // Primeiro, valida o CPF
    if (!validarCPF($cpf)) {
        echo json_encode(['exists' => false, 'error' => 'CPF inválido']);
        exit;
    }

    // Depois, verifica se o CPF já existe no banco de dados
    $sql = "SELECT id FROM participantes WHERE cpf = ?";
    $params = [$cpf];

    // Ignora o ID do participante atual, se fornecido
    if ($id) {
        $sql .= " AND id != ?";
        $params[] = $id;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $exists = $stmt->rowCount() > 0;

    echo json_encode(['exists' => $exists]);
} else {
    echo json_encode(['exists' => false, 'error' => 'CPF não informado']);
}
