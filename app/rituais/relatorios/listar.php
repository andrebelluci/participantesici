<?php
require_once __DIR__ . '/../../functions/check_auth.php';

// Configurar fuso horário para Brasil (-3)
date_default_timezone_set('America/Sao_Paulo');

$formato = filter_input(INPUT_GET, 'formato', FILTER_SANITIZE_STRING) ?: 'pdf';

// Validar formato
if (!in_array($formato, ['pdf', 'excel'])) {
  $_SESSION['error'] = 'Formato de exportação inválido.';
  header('Location: /rituais');
  exit;
}

// Redirecionar para o arquivo específico do formato
if ($formato === 'pdf') {
  require_once __DIR__ . '/lista_pdf.php';
} else {
  require_once __DIR__ . '/lista_excel.php';
}
