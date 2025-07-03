<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../config/database.php';

// Configurar fuso horário para Brasil (-3)
date_default_timezone_set('America/Sao_Paulo');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
  $_SESSION['error'] = 'ID do participante inválido.';
  header('Location: /participantes');
  exit;
}

// Buscar dados do participante
$stmt = $pdo->prepare("SELECT * FROM participantes WHERE id = ?");
$stmt->execute([$id]);
$participante = $stmt->fetch();

if (!$participante) {
  $_SESSION['error'] = 'Participante não encontrado.';
  header('Location: /participantes');
  exit;
}

// Buscar todos os rituais que o participante participou
$sql_rituais = "
    SELECT r.*, i.presente, i.observacao,
           i.primeira_vez_instituto, i.primeira_vez_ayahuasca,
           i.doenca_psiquiatrica, i.nome_doenca,
           i.uso_medicao, i.nome_medicao, i.mensagem,
           i.salvo_em, i.obs_salvo_em
    FROM inscricoes i
    JOIN rituais r ON i.ritual_id = r.id
    WHERE i.participante_id = ?
    ORDER BY r.data_ritual DESC
";
$stmt_rituais = $pdo->prepare($sql_rituais);
$stmt_rituais->execute([$id]);
$rituais = $stmt_rituais->fetchAll();

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

// Definir headers para Excel
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="participante_' . preg_replace('/[^a-zA-Z0-9]/', '_', $participante['nome_completo']) . '_' . date('Y-m-d') . '.xls"');
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
                    <x:Name>Participante</x:Name>
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
      <td colspan="5" class="header-section">PARTICIPANTES ICI - INSTITUTO CÉU INTERIOR</td>
    </tr>
    <tr>
      <td colspan="5" class="title-main">RELATÓRIO DO PARTICIPANTE</td>
    </tr>
  </table>

  <!-- Dados Pessoais -->
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 20px; border-collapse: collapse;">
    <tr>
      <td colspan="5" class="section-header">DADOS PESSOAIS</td>
    </tr>
    <tr>
      <td class="field-name">Nome Completo:</td>
      <td class="field-value"><?= htmlspecialchars($participante['nome_completo']) ?></td>
    </tr>
    <tr>
      <td class="field-name">Data de Nascimento:</td>
      <td class="field-value"><?= (new DateTime($participante['nascimento']))->format('d/m/Y') ?></td>
    </tr>
    <tr>
      <td class="field-name">Sexo:</td>
      <td class="field-value"><?= $participante['sexo'] === 'M' ? 'Masculino' : 'Feminino' ?></td>
    </tr>
    <tr>
      <td class="field-name">CPF:</td>
      <td class="field-value"><?= formatarCPF($participante['cpf']) ?></td>
    </tr>
    <?php if (!empty($participante['rg'])): ?>
      <tr>
        <td class="field-name">RG:</td>
        <td class="field-value"><?= htmlspecialchars($participante['rg']) ?></td>
      </tr>
    <?php endif; ?>
    <tr>
      <td class="field-name">Celular:</td>
      <td class="field-value"><?= formatarTelefone($participante['celular']) ?></td>
    </tr>
    <?php if (!empty($participante['email'])): ?>
      <tr>
        <td class="field-name">E-mail:</td>
        <td class="field-value"><?= htmlspecialchars($participante['email']) ?></td>
      </tr>
    <?php endif; ?>
  </table>

  <!-- Endereço -->
  <?php if (!empty($participante['endereco_rua'])): ?>
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 20px; border-collapse: collapse;">
      <tr>
        <td colspan="5" class="section-header">ENDEREÇO</td>
      </tr>
      <tr>
        <td class="field-name">CEP:</td>
        <td class="field-value"><?= htmlspecialchars($participante['cep']) ?></td>
      </tr>
      <tr>
        <td class="field-name">Endereço:</td>
        <td class="field-value"><?= htmlspecialchars($participante['endereco_rua']) ?>,
          <?= htmlspecialchars($participante['endereco_numero']) ?>
        </td>
      </tr>
      <?php if (!empty($participante['endereco_complemento'])): ?>
        <tr>
          <td class="field-name">Complemento:</td>
          <td class="field-value"><?= htmlspecialchars($participante['endereco_complemento']) ?></td>
        </tr>
      <?php endif; ?>
      <tr>
        <td class="field-name">Bairro:</td>
        <td class="field-value"><?= htmlspecialchars($participante['bairro']) ?></td>
      </tr>
      <tr>
        <td class="field-name">Cidade/UF:</td>
        <td class="field-value">
          <?= htmlspecialchars($participante['cidade']) ?>/<?= htmlspecialchars($participante['estado']) ?>
        </td>
      </tr>
    </table>
  <?php endif; ?>

  <!-- Rituais -->
  <?php if (!empty($rituais)): ?>
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
      <tr>
        <td colspan="5" class="section-header">RITUAIS PARTICIPADOS (<?= count($rituais) ?>)</td>
      </tr>
      <tr>
        <td class="table-header" width="30%">Nome do Ritual</td>
        <td class="table-header" width="15%">Data</td>
        <td class="table-header" width="20%">Padrinho/Madrinha</td>
        <td class="table-header" width="10%">Presente</td>
        <td class="table-header" width="25%">Observação</td>
      </tr>
      <?php foreach ($rituais as $ritual): ?>
        <tr>
          <td class="table-cell"><?= htmlspecialchars($ritual['nome']) ?></td>
          <td class="table-cell-center"><?= (new DateTime($ritual['data_ritual']))->format('d/m/Y') ?></td>
          <td class="table-cell-center"><?= htmlspecialchars($ritual['padrinho_madrinha']) ?></td>
          <td class="<?= $ritual['presente'] === 'Sim' ? 'presente-sim' : 'presente-nao' ?>">
            <?= $ritual['presente'] === 'Sim' ? 'SIM' : 'NÃO' ?>
          </td>
          <td class="table-cell-multiline">
            <?php
            $observacao = $ritual['observacao'] ?: 'Nenhuma observação';
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
        <td colspan="5" class="section-header">RITUAIS PARTICIPADOS</td>
      </tr>
      <tr>
        <td class="table-cell-center" style="padding: 20px; font-style: italic; color: #666;">
          Este participante ainda não participou de nenhum ritual.
        </td>
      </tr>
    </table>
  <?php endif; ?>

  <!-- Rodapé -->
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 30px;">
    <tr>
      <td colspan="5" class="footer-info">
        <strong>Relatório gerado em <?= date('d/m/Y H:i:s') ?> | Instituto Céu Interior - Gestão de
          Participantes</strong>
      </td>
    </tr>
  </table>

</body>

</html>