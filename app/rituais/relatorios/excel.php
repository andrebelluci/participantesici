<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../config/database.php';

// Configurar fuso horário para Brasil (-3)
date_default_timezone_set('America/Sao_Paulo');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
  $_SESSION['error'] = 'ID do ritual inválido.';
  header('Location: /participantesici/public_html/rituais');
  exit;
}

// Buscar dados do ritual
$stmt = $pdo->prepare("SELECT * FROM rituais WHERE id = ?");
$stmt->execute([$id]);
$ritual = $stmt->fetch();

if (!$ritual) {
  $_SESSION['error'] = 'Ritual não encontrado.';
  header('Location: /participantesici/public_html/rituais');
  exit;
}

// Buscar todos os participantes do ritual
$sql_participantes = "
    SELECT p.*, i.presente, i.observacao,
           i.primeira_vez_instituto, i.primeira_vez_ayahuasca,
           i.doenca_psiquiatrica, i.nome_doenca,
           i.uso_medicao, i.nome_medicao, i.mensagem,
           i.salvo_em, i.obs_salvo_em
    FROM inscricoes i
    JOIN participantes p ON i.participante_id = p.id
    WHERE i.ritual_id = ?
    ORDER BY p.nome_completo ASC
";
$stmt_participantes = $pdo->prepare($sql_participantes);
$stmt_participantes->execute([$id]);
$participantes = $stmt_participantes->fetchAll();

// Função para formatar CPF
function formatarCPF($cpf)
{
  return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

// Função para formatar telefone
function formatarTelefone($telefone)
{
  $telefone = preg_replace('/\D/', '', $telefone);
  if (strlen($telefone) == 11) {
    return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7, 4);
  }
  return $telefone;
}

// Contar estatísticas
$total_participantes = count($participantes);
$presentes = array_filter($participantes, function ($p) {
  return $p['presente'] === 'Sim';
});
$ausentes = array_filter($participantes, function ($p) {
  return $p['presente'] === 'Não';
});
$total_presentes = count($presentes);
$total_ausentes = count($ausentes);

// Definir headers para Excel
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="ritual_' . preg_replace('/[^a-zA-Z0-9]/', '_', $ritual['nome']) . '_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Começar saída HTML para Excel
echo "\xEF\xBB\xBF"; // BOM para UTF-8
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel"
  xmlns="http://www.w3.org/TR/REC-html40">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8">
  <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Ritual</x:Name>
                    <x:WorksheetOptions>
                        <x:Print>
                            <x:ValidPrinterInfo/>
                        </x:Print>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
  <style>
    body {
      font-family: Arial, sans-serif;
      font-size: 12px;
    }

    .header-section {
      background-color: #0066cc;
      color: white;
      text-align: center;
      font-weight: bold;
      font-size: 16px;
      padding: 10px;
    }

    .title-main {
      background-color: #0066cc;
      color: white;
      text-align: center;
      font-weight: bold;
      font-size: 18px;
      padding: 15px;
    }

    .section-header {
      background-color: #0066cc;
      color: white;
      font-weight: bold;
      text-align: center;
      padding: 8px;
    }

    .field-name {
      background-color: #e6f3ff;
      font-weight: bold;
      color: #0066cc;
      padding: 8px;
      width: 30%;
    }

    .field-value {
      background-color: white;
      padding: 8px;
      border: 1px solid #ccc;
    }

    .stats-total {
      background-color: #e8f4fd;
      text-align: center;
      font-weight: bold;
      padding: 8px;
      border: 1px solid #ccc;
    }

    .stats-presente {
      background-color: #d4edda;
      color: #155724;
      text-align: center;
      font-weight: bold;
      padding: 8px;
      border: 1px solid #ccc;
    }

    .stats-ausente {
      background-color: #f8d7da;
      color: #721c24;
      text-align: center;
      font-weight: bold;
      padding: 8px;
      border: 1px solid #ccc;
    }

    .stats-taxa {
      background-color: #fff3cd;
      color: #856404;
      text-align: center;
      font-weight: bold;
      padding: 8px;
      border: 1px solid #ccc;
    }

    .table-header {
      background-color: #0066cc;
      color: white;
      font-weight: bold;
      text-align: center;
      padding: 8px;
      border: 1px solid #000;
    }

    .table-cell {
      padding: 6px;
      border: 1px solid #ccc;
      text-align: left;
    }

    .table-cell-center {
      padding: 6px;
      border: 1px solid #ccc;
      text-align: center;
    }

    .presente-sim {
      background-color: #d4edda;
      color: #155724;
      font-weight: bold;
      text-align: center;
      padding: 6px;
      border: 1px solid #ccc;
    }

    .presente-nao {
      background-color: #f8d7da;
      color: #721c24;
      font-weight: bold;
      text-align: center;
      padding: 6px;
      border: 1px solid #ccc;
    }

    .primeira-vez-sim {
      background-color: #fff3cd;
      color: #856404;
      font-weight: bold;
      text-align: center;
      padding: 6px;
      border: 1px solid #ccc;
    }

    .primeira-vez-nao {
      background-color: #e2e3e5;
      color: #6c757d;
      font-weight: bold;
      text-align: center;
      padding: 6px;
      border: 1px solid #ccc;
    }

    .footer-info {
      background-color: #f0f8ff;
      text-align: center;
      padding: 10px;
      margin-top: 20px;
      font-size: 11px;
      color: #666;
    }

    .table-cell-multiline {
      white-space: pre-line;
      /* Preserva quebras de linha */
      vertical-align: top;
      /* Alinha no topo */
      word-wrap: break-word;
      /* Quebra palavras longas */
      max-width: 200px;
      /* Limita largura */
    }
  </style>
</head>

<body>

  <!-- Cabeçalho -->
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 20px;">
    <tr>
      <td colspan="8" class="header-section">PARTICIPANTES ICI - INSTITUTO CÉU INTERIOR</td>
    </tr>
    <tr>
      <td colspan="8" class="title-main">RELATÓRIO DO RITUAL</td>
    </tr>
  </table>

  <!-- Dados do Ritual -->
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 20px; border-collapse: collapse;">
    <tr>
      <td colspan="8" class="section-header">DADOS DO RITUAL</td>
    </tr>
    <tr>
      <td class="field-name">Nome:</td>
      <td class="field-value"><?= htmlspecialchars($ritual['nome']) ?></td>
    </tr>
    <tr>
      <td class="field-name">Data:</td>
      <td class="field-value"><?= (new DateTime($ritual['data_ritual']))->format('d/m/Y') ?></td>
    </tr>
    <tr>
      <td class="field-name">Padrinho/Madrinha:</td>
      <td class="field-value"><?= htmlspecialchars($ritual['padrinho_madrinha']) ?></td>
    </tr>
  </table>

  <!-- Estatísticas -->
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 20px; border-collapse: collapse;">
    <tr>
      <td colspan="8" class="section-header">ESTATÍSTICAS DE PARTICIPAÇÃO</td>
    </tr>
    <tr>
      <td class="field-name">Total de Participantes:</td>
      <td class="stats-total"><?= $total_participantes ?></td>
    </tr>
    <tr>
      <td class="field-name">Presentes:</td>
      <td class="stats-presente"><?= $total_presentes ?></td>
    </tr>
    <tr>
      <td class="field-name">Ausentes:</td>
      <td class="stats-ausente"><?= $total_ausentes ?></td>
    </tr>
    <tr>
      <td class="field-name">Taxa de Presença:</td>
      <td class="stats-taxa">
        <?= $total_participantes > 0 ? round(($total_presentes / $total_participantes) * 100, 1) : 0 ?>%
      </td>
    </tr>
  </table>

  <!-- Lista de Participantes -->
  <?php if (!empty($participantes)): ?>
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
      <tr>
        <td colspan="8" class="section-header">LISTA DE PARTICIPANTES</td>
      </tr>
      <tr>
        <td class="table-header" width="18%">Nome Completo</td>
        <td class="table-header" width="12%">CPF</td>
        <td class="table-header" width="12%">Celular</td>
        <td class="table-header" width="15%">E-mail</td>
        <td class="table-header" width="8%">Presente</td>
        <td class="table-header" width="8%">1ª Vez Instituto</td>
        <td class="table-header" width="8%">1ª Vez Ayahuasca</td>
        <td class="table-header" width="19%">Observação</td>
      </tr>
      <?php foreach ($participantes as $participante): ?>
        <tr>
          <td class="table-cell"><?= htmlspecialchars($participante['nome_completo']) ?></td>
          <td class="table-cell-center"><?= formatarCPF($participante['cpf']) ?></td>
          <td class="table-cell-center"><?= formatarTelefone($participante['celular']) ?></td>
          <td class="table-cell"><?= htmlspecialchars($participante['email'] ?: '-') ?></td>
          <td class="<?= $participante['presente'] === 'Sim' ? 'presente-sim' : 'presente-nao' ?>">
            <?= $participante['presente'] === 'Sim' ? 'SIM' : 'NÃO' ?>
          </td>
          <td class="<?= $participante['primeira_vez_instituto'] === 'Sim' ? 'primeira-vez-sim' : 'primeira-vez-nao' ?>">
            <?= $participante['primeira_vez_instituto'] === 'Sim' ? 'SIM' : 'NÃO' ?>
          </td>
          <td class="<?= $participante['primeira_vez_ayahuasca'] === 'Sim' ? 'primeira-vez-sim' : 'primeira-vez-nao' ?>">
            <?= $participante['primeira_vez_ayahuasca'] === 'Sim' ? 'SIM' : 'NÃO' ?>
          </td>
          <td class="table-cell-multiline">
            <?php
            $observacao = $participante['observacao'] ?: 'Nenhuma observação';
            // Quebra automática a cada 40 caracteres para melhor visualização
            echo htmlspecialchars(wordwrap($observacao, 40, "\n", true));
            ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
      <tr>
        <td colspan="8" class="section-header">LISTA DE PARTICIPANTES</td>
      </tr>
      <tr>
        <td class="table-cell-center" style="padding: 20px; font-style: italic; color: #666;">
          Este ritual ainda não possui participantes inscritos.
        </td>
      </tr>
    </table>
  <?php endif; ?>

  <!-- Rodapé -->
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 30px;">
    <tr>
      <td colspan="8" class="footer-info">
        <strong>Relatório gerado em <?= date('d/m/Y H:i:s') ?> | Instituto Céu Interior - Gestão de
          Participantes</strong>
      </td>
    </tr>
  </table>

</body>

</html>