<?php
require_once __DIR__ . '/../../functions/check_auth.php';

// Configurar fuso horário para Brasil (-3)
date_default_timezone_set('America/Sao_Paulo');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$formato = filter_input(INPUT_GET, 'formato', FILTER_SANITIZE_STRING) ?: 'pdf';

if (!$id) {
  $_SESSION['error'] = 'ID do participante inválido.';
  header('Location: /participantes');
  exit;
}

// Validar formato
if (!in_array($formato, ['pdf', 'excel'])) {
  $_SESSION['error'] = 'Formato de exportação inválido.';
  header('Location: /participante/' . $id);
  exit;
}

// Redirecionar para o arquivo específico do formato
if ($formato === 'pdf') {
  require_once __DIR__ . '/pdf.php';
} else {
  require_once __DIR__ . '/excel.php';
}
