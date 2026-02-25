<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../config/database.php';

// Configurar fuso horário para Brasil (-3)
date_default_timezone_set('America/Sao_Paulo');

// Filtros (mesma lógica do listar.php de rituais)
$filtro_nome = $_GET['filtro_nome'] ?? '';
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';

// Monta cláusulas de filtro
$where = "";
$params = [];
if (!empty($filtro_nome)) {
  $where .= " AND nome LIKE ?";
  $params[] = "%$filtro_nome%";
}
if (!empty($data_inicio)) {
  $where .= " AND data_ritual >= ?";
  $params[] = $data_inicio;
}
if (!empty($data_fim)) {
  $where .= " AND data_ritual <= ?";
  $params[] = $data_fim;
}

// Consulta para listar os rituais
$sql = "
    SELECT r.*, COUNT(i.id) AS total_inscritos
    FROM rituais r
    LEFT JOIN inscricoes i ON r.id = i.ritual_id
    WHERE 1=1 $where
    GROUP BY r.id
    ORDER BY data_ritual DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rituais = $stmt->fetchAll();

// Definir headers para Excel
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="lista_rituais_' . date('Ymd_His') . '.xls"');
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
      <td colspan="4" class="header-section">ICI PARTICIPANTES - INSTITUTO CÉU INTERIOR</td>
    </tr>
    <tr>
      <td colspan="4" class="title-main">LISTA DE RITUAIS</td>
    </tr>
  </table>

  <table style="border-collapse: collapse;">
    <?php if (!empty($filtro_nome) || !empty($data_inicio) || !empty($data_fim)): ?>
    <tr>
      <th colspan="4" style="text-align: left; font-style: italic;">
        Filtros:
        <?php
        $f = [];
        if (!empty($filtro_nome)) $f[] = "Nome: $filtro_nome";
        if (!empty($data_inicio)) $f[] = "Início: " . (new DateTime($data_inicio))->format('d/m/Y');
        if (!empty($data_fim)) $f[] = "Fim: " . (new DateTime($data_fim))->format('d/m/Y');
        echo implode('; ', $f);
        ?>
      </th>
    </tr>
    <?php endif; ?>
    <tr><td colspan="4"></td></tr>
    <tr>
      <th class="table-header">Nome do Ritual</th>
      <th class="table-header" align="center">Data</th>
      <th class="table-header">Padrinho/Madrinha</th>
      <th class="table-header" align="center">Inscritos</th>
    </tr>
    <?php foreach ($rituais as $ritual): ?>
      <tr>
        <td class="table-cell"><?= htmlspecialchars($ritual['nome']) ?></td>
        <td class="table-cell-center" align="center"><?= (new DateTime($ritual['data_ritual']))->format('d/m/Y') ?></td>
        <td class="table-cell"><?= htmlspecialchars($ritual['padrinho_madrinha']) ?></td>
        <td class="table-cell-center" align="center"><?= $ritual['total_inscritos'] ?></td>
      </tr>
    <?php endforeach; ?>
  </table>

  <!-- Rodapé -->
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 30px;">
    <tr>
      <td colspan="4" class="footer-info">
        <strong>Relatório gerado em <?= date('d/m/Y H:i:s') ?> | Instituto Céu Interior - Gestão de Participantes</strong>
      </td>
    </tr>
  </table>
</body>
</html>
<?php exit; ?>
