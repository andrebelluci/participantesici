<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../config/database.php';

// Configurar fuso horário para Brasil (-3)
date_default_timezone_set('America/Sao_Paulo');

// Verificar se a biblioteca TCPDF está disponível
if (!class_exists('TCPDF')) {
  require_once __DIR__ . '/../../vendor/tcpdf/tcpdf.php';
}

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

// Criar instância do TCPDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Configurações do documento
$pdf->SetCreator('Participantes ICI');
$pdf->SetAuthor('Instituto Céu Interior');
$pdf->SetTitle('Lista de Rituais');
$pdf->SetSubject('Relatório de Rituais');

// Remover header e footer padrão
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Configurar margens
$pdf->SetMargins(15, 20, 15);
$pdf->SetAutoPageBreak(TRUE, 25);

// Adicionar página
$pdf->AddPage('L', 'A4'); // Horizontal

// Caminho da logo
$logo_path = __DIR__ . '/../../../public_html/assets/images/logo.png';

// Verificar se a logo existe
if (file_exists($logo_path)) {
  // Adicionar logo (centralizada)
  $pdf->Image($logo_path, 125, 15, 40, 0, '', '', '', false, 300, '', false, false, 0);
  $pdf->SetY(45);
} else {
  $pdf->SetY(20);
}

// Cabeçalho da empresa
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(102, 102, 102);
$pdf->Cell(0, 6, 'Gestão de Participantes', 0, 1, 'C');
$pdf->Ln(5);

// Linha decorativa
$pdf->SetDrawColor(0, 102, 204);
$pdf->SetLineWidth(0.8);
$pdf->Line(15, $pdf->GetY(), 282, $pdf->GetY());
$pdf->Ln(8);

// Título do relatório
$pdf->SetFont('helvetica', 'B', 18);
$pdf->SetTextColor(0, 102, 204);
$pdf->Cell(0, 10, 'LISTA DE RITUAIS', 0, 1, 'C');
$pdf->Ln(8);

// Reset para cor preta
$pdf->SetTextColor(0, 0, 0);

// Filtros Aplicados
if (!empty($filtro_nome) || !empty($data_inicio) || !empty($data_fim)) {
  $pdf->SetFont('helvetica', 'B', 14);
  $pdf->SetFillColor(0, 102, 204);
  $pdf->SetTextColor(255, 255, 255);
  $pdf->Cell(0, 10, 'FILTROS APLICADOS', 0, 1, 'L', true);
  $pdf->SetTextColor(0, 0, 0);
  $pdf->Ln(3);

  $pdf->SetFont('helvetica', '', 10);
  $filtros_texto = "";
  if (!empty($filtro_nome)) $filtros_texto .= "Nome: $filtro_nome; ";
  if (!empty($data_inicio)) $filtros_texto .= "Início: " . (new DateTime($data_inicio))->format('d/m/Y') . "; ";
  if (!empty($data_fim)) $filtros_texto .= "Fim: " . (new DateTime($data_fim))->format('d/m/Y') . "; ";
  
  $pdf->MultiCell(0, 6, $filtros_texto, 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'T', false);
  $pdf->Ln(5);
}

// Tabela de Rituais
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetFillColor(0, 102, 204);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 10, 'LISTA DE RITUAIS (' . count($rituais) . ')', 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(3);

$pdf->SetFont('helvetica', '', 9);

$html = '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse;">
    <thead>
        <tr style="background-color: #0066cc; color: white;">
            <th width="40%" style="text-align: center;"><strong>Nome do Ritual</strong></th>
            <th width="15%" style="text-align: center;"><strong>Data</strong></th>
            <th width="30%" style="text-align: center;"><strong>Padrinho/Madrinha</strong></th>
            <th width="15%" style="text-align: center;"><strong>Inscritos</strong></th>
        </tr>
    </thead>
    <tbody>';

$row_color = true;
foreach ($rituais as $ritual) {
  $bg_color = $row_color ? '#f8f9fa' : '#ffffff';
  $data_formatada = (new DateTime($ritual['data_ritual']))->format('d/m/Y');
  $html .= '<tr style="background-color: ' . $bg_color . ';">
        <td width="40%" style="text-align: center;">' . htmlspecialchars($ritual['nome']) . '</td>
        <td width="15%" style="text-align: center;">' . $data_formatada . '</td>
        <td width="30%" style="text-align: center;">' . htmlspecialchars($ritual['padrinho_madrinha']) . '</td>
        <td width="15%" style="text-align: center;">' . $ritual['total_inscritos'] . '</td>
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
$pdf->Line(15, $pdf->GetY(), 282, $pdf->GetY());
$pdf->Ln(5);

$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(102, 102, 102);
$pdf->Cell(0, 5, 'Relatório gerado em ' . date('d/m/Y H:i:s') . ' | Instituto Céu Interior - Gestão de Participantes', 0, 1, 'C');

// Enviar PDF
$pdf->Output('lista_rituais_' . date('Ymd_His') . '.pdf', 'D');
exit;
