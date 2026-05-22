<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../functions/participante_status.php';
require_once __DIR__ . '/../../config/database.php';

// Configurar fuso horário para Brasil (-3)
date_default_timezone_set('America/Sao_Paulo');

// Verificar se a biblioteca TCPDF está disponível
if (!class_exists('TCPDF')) {
  require_once __DIR__ . '/../../vendor/tcpdf/tcpdf.php';
}

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

// Consulta para listar as pessoas (sem limite de paginação para o relatório)
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

// Criar instância do TCPDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Configurações do documento
$pdf->SetCreator('Participantes ICI');
$pdf->SetAuthor('Instituto Céu Interior');
$pdf->SetTitle('Lista de Participantes');
$pdf->SetSubject('Relatório de Participantes');

// Remover header e footer padrão
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Configurar margens
$pdf->SetMargins(15, 20, 15);
$pdf->SetAutoPageBreak(TRUE, 25);

// Adicionar página
$pdf->AddPage('L', 'A4'); // Horizontal para caber mais dados

// Caminho da logo
$logo_path = __DIR__ . '/../../../public_html/assets/images/logo.png';

// Verificar se a logo existe
if (file_exists($logo_path)) {
  // Adicionar logo (centralizada)
  $pdf->Image($logo_path, 125, 15, 40, 0, '', '', '', false, 300, '', false, false, 0); // Ajustado para A4 paisagem
  $pdf->SetY(45);
} else {
  $pdf->SetY(20);
}

// Cabeçalho da empresa
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(102, 102, 102); // Cinza
$pdf->Cell(0, 6, 'Gestão de Participantes', 0, 1, 'C');
$pdf->Ln(5);

// Linha decorativa
$pdf->SetDrawColor(0, 102, 204);
$pdf->SetLineWidth(0.8);
$pdf->Line(15, $pdf->GetY(), 282, $pdf->GetY()); // Ajustado para A4 paisagem
$pdf->Ln(8);

// Título do relatório
$pdf->SetFont('helvetica', 'B', 18);
$pdf->SetTextColor(0, 102, 204);
$pdf->Cell(0, 10, 'LISTA DE PARTICIPANTES', 0, 1, 'C');
$pdf->Ln(8);

// Reset para cor preta
$pdf->SetTextColor(0, 0, 0);

// Filtros Aplicados
if (!empty($filtro_nome) || !empty($filtro_cpf) || $filtro_mes_aniversario !== null) {
  $pdf->SetFont('helvetica', 'B', 14);
  $pdf->SetFillColor(0, 102, 204);
  $pdf->SetTextColor(255, 255, 255);
  $pdf->Cell(0, 10, 'FILTROS APLICADOS', 0, 1, 'L', true);
  $pdf->SetTextColor(0, 0, 0);
  $pdf->Ln(3);

  $pdf->SetFont('helvetica', '', 10);
  $filtros_texto = "";
  if (!empty($filtro_nome)) $filtros_texto .= "Nome: $filtro_nome; ";
  if (!empty($filtro_cpf)) $filtros_texto .= "CPF: " . formatarCPF($filtro_cpf) . "; ";
  if ($filtro_mes_aniversario !== null) {
    $meses = [1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'];
    $filtros_texto .= "Aniversariantes de " . $meses[$filtro_mes_aniversario] . "; ";
  }
  $pdf->MultiCell(0, 6, $filtros_texto, 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'T', false);
  $pdf->Ln(5);
}

// Tabela de Participantes
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetFillColor(0, 102, 204);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 10, 'LISTA DE PARTICIPANTES (' . count($pessoas) . ')', 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(3);

$pdf->SetFont('helvetica', '', 9);

$html = '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse;">
    <thead>
        <tr style="background-color: #0066cc; color: white;">
            <th width="22%" style="text-align: center;"><strong>Nome Completo</strong></th>
            <th width="12%" style="text-align: center;"><strong>Status</strong></th>
            <th width="14%" style="text-align: center;"><strong>CPF</strong></th>
            <th width="11%" style="text-align: center;"><strong>Nascimento</strong></th>
            <th width="14%" style="text-align: center;"><strong>Celular</strong></th>
            <th width="20%" style="text-align: center;"><strong>Cidade/UF</strong></th>
            <th width="7%" style="text-align: center;"><strong>Rituais</strong></th>
        </tr>
    </thead>
    <tbody>';

$row_color = true;
foreach ($pessoas as $pessoa) {
  $bg_color = $row_color ? '#f8f9fa' : '#ffffff';
  $nascimento = (new DateTime($pessoa['nascimento']))->format('d/m/Y');
  $st = participanteNormalizarStatus($pessoa['status'] ?? null);
  $html .= '<tr style="background-color: ' . $bg_color . ';">
        <td width="22%" style="text-align: center;">' . htmlspecialchars($pessoa['nome_completo']) . '</td>
        <td width="12%" style="text-align: center;">' . htmlspecialchars(participanteStatusLabel($st)) . '</td>
        <td width="14%" style="text-align: center;">' . formatarCPF($pessoa['cpf']) . '</td>
        <td width="11%" style="text-align: center;">' . $nascimento . '</td>
        <td width="14%" style="text-align: center;">' . formatarTelefone($pessoa['celular']) . '</td>
        <td width="20%" style="text-align: center;">' . htmlspecialchars($pessoa['cidade']) . '/' . htmlspecialchars($pessoa['estado']) . '</td>
        <td width="7%" style="text-align: center;">' . $pessoa['rituais_participados'] . '</td>
    </tr>';
  $row_color = !$row_color;
}

$html .= '</tbody></table>';

$pdf->writeHTML($html, true, false, true, false, '');

// Rodapé profissional
$pdf->SetY(-35);

// Linha decorativa
$pdf->SetDrawColor(0, 102, 204);
$pdf->SetLineWidth(0.5);
$pdf->Line(15, $pdf->GetY(), 282, $pdf->GetY()); // Ajustado para A4 paisagem
$pdf->Ln(5);

$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(102, 102, 102);
$pdf->Cell(0, 5, 'Relatório gerado em ' . date('d/m/Y H:i:s') . ' | Instituto Céu Interior - Gestão de Participantes', 0, 1, 'C');

// Enviar PDF
$pdf->Output('lista_participantes_' . date('Ymd_His') . '.pdf', 'D');
exit;
