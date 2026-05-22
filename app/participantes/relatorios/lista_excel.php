<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../functions/participante_status.php';
require_once __DIR__ . '/../../config/database.php';

// Configurar fuso horário para Brasil (-3)
date_default_timezone_set('America/Sao_Paulo');

// Filtros (mesma lógica do listar.php)
$filtro_nome = isset($_GET['filtro_nome']) ? $_GET['filtro_nome'] : '';
$filtro_cpf = isset($_GET['filtro_cpf']) ? $_GET['filtro_cpf'] : '';
$filtro_cpf = preg_replace('/\D/', '', $filtro_cpf);
$filtro_mes_aniversario = isset($_GET['filtro_mes_aniversario']) ? (int)$_GET['filtro_mes_aniversario'] : null;

if ($filtro_mes_aniversario === null && isset($_GET['filtro_aniversariantes']) && $_GET['filtro_aniversariantes'] == '1') {
  $filtro_mes_aniversario = (int)date('m');
}

// Monta cláusulas de filtro
$where = "";
$params = [];
if (!empty($filtro_nome)) {
  $where .= " AND p.nome_completo LIKE ?";
  $params[] = "%$filtro_nome%";
}
if (!empty($filtro_cpf)) {
  $where .= " AND p.cpf = ?";
  $params[] = $filtro_cpf;
}
if ($filtro_mes_aniversario !== null && $filtro_mes_aniversario > 0 && $filtro_mes_aniversario <= 12) {
  $where .= " AND MONTH(p.nascimento) = ?";
  $params[] = $filtro_mes_aniversario;
}

$filtroStatus = participanteFiltroStatusFromRequest();
$where .= $filtroStatus['where'];
$params = array_merge($params, $filtroStatus['params']);

// Consulta para listar as pessoas
$sql = "
    SELECT p.*, COUNT(i.id) AS rituais_participados
    FROM participantes p
    LEFT JOIN inscricoes i ON p.id = i.participante_id AND i.presente = 'Sim'
    WHERE 1=1 $where
    GROUP BY p.id
    ORDER BY p.nome_completo ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pessoas = $stmt->fetchAll();

// Funções de formatação
function formatarCPF($cpf) {
  $cpf = preg_replace('/\D/', '', $cpf);
  if (strlen($cpf) === 11) {
    return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
  }
  return $cpf;
}

function formatarTelefone($telefone) {
  $telefone = preg_replace('/\D/', '', $telefone);
  if (strlen($telefone) === 10) {
    return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6, 4);
  } elseif (strlen($telefone) === 11) {
    return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7, 4);
  }
  return $telefone;
}

// Definir headers para Excel
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="lista_participantes_' . date('Ymd_His') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Começar saída HTML para Excel
echo "\xEF\xBB\xBF"; // BOM para UTF-8
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel"
  xmlns="http://www.w3.org/TR/REC-html40">

<head>
  <meta charset="UTF-8">
  <style>
    .header-section { background-color: #0066cc; color: white; text-align: center; font-weight: bold; font-size: 16px; padding: 10px; }
    .title-main { background-color: #0066cc; color: white; text-align: center; font-weight: bold; font-size: 18px; padding: 15px; }
    .table-header { background-color: #f2f2f2; font-weight: bold; border: 1.0pt solid #ccc; }
    .table-cell { border: 1.0pt solid #ccc; }
    .table-cell-center { border: 1.0pt solid #ccc; text-align: center; }
    .footer-info { background-color: #f0f8ff; text-align: center; padding: 10px; margin-top: 20px; font-size: 11px; color: #666; }
  </style>
</head>

<body>
  <!-- Cabeçalho -->
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 20px;">
    <tr>
      <td colspan="6" class="header-section">ICI PARTICIPANTES - INSTITUTO CÉU INTERIOR</td>
    </tr>
    <tr>
      <td colspan="6" class="title-main">LISTA DE PARTICIPANTES</td>
    </tr>
  </table>

  <table style="border-collapse: collapse;">
    <?php if (!empty($filtro_nome) || !empty($filtro_cpf) || $filtro_mes_aniversario !== null): ?>
    <tr>
      <th colspan="6" style="text-align: left; font-style: italic;">
        Filtros:
        <?php
        $f = [];
        if (!empty($filtro_nome)) $f[] = "Nome: $filtro_nome";
        if (!empty($filtro_cpf)) $f[] = "CPF: " . formatarCPF($filtro_cpf);
        if ($filtro_mes_aniversario !== null) {
          $meses = [1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'];
          $f[] = "Aniversariantes: " . $meses[$filtro_mes_aniversario];
        }
        echo implode('; ', $f);
        ?>
      </th>
    </tr>
    <?php endif; ?>
    <tr><td colspan="7"></td></tr>
    <tr>
      <th class="table-header">Nome Completo</th>
      <th class="table-header" align="center">Status</th>
      <th class="table-header" align="center">CPF</th>
      <th class="table-header" align="center">Nascimento</th>
      <th class="table-header" align="center">Celular</th>
      <th class="table-header">Cidade/UF</th>
      <th class="table-header" align="center">Rituais Participados</th>
    </tr>
    <?php foreach ($pessoas as $pessoa): ?>
      <?php
      $st = participanteNormalizarStatus($pessoa['status'] ?? null);
      ?>
      <tr>
        <td class="table-cell"><?= htmlspecialchars($pessoa['nome_completo']) ?></td>
        <td class="table-cell-center" align="center"><?= htmlspecialchars(participanteStatusLabel($st)) ?></td>
        <td class="table-cell-center" align="center"><?= formatarCPF($pessoa['cpf']) ?></td>
        <td class="table-cell-center" align="center"><?= (new DateTime($pessoa['nascimento']))->format('d/m/Y') ?></td>
        <td class="table-cell-center" align="center"><?= formatarTelefone($pessoa['celular']) ?></td>
        <td class="table-cell"><?= htmlspecialchars($pessoa['cidade']) ?>/<?= htmlspecialchars($pessoa['estado']) ?></td>
        <td class="table-cell-center" align="center"><?= $pessoa['rituais_participados'] ?></td>
      </tr>
    <?php endforeach; ?>
  </table>

  <!-- Rodapé -->
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 30px;">
    <tr>
      <td colspan="6" class="footer-info">
        <strong>Relatório gerado em <?= date('d/m/Y H:i:s') ?> | Instituto Céu Interior - Gestão de Participantes</strong>
      </td>
    </tr>
  </table>
</body>
</html>
<?php exit; ?>
