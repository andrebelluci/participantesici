<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../config/database.php';

// Configurar fuso horário para Brasil (-3)
date_default_timezone_set('America/Sao_Paulo');

// Verificar se a biblioteca TCPDF está disponível
if (!class_exists('TCPDF')) {
    require_once __DIR__ . '/../../vendor/tcpdf/tcpdf.php';
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$formato = filter_input(INPUT_GET, 'formato', FILTER_SANITIZE_STRING) ?: 'pdf';

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
function formatarCPF($cpf) {
    return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

// Função para formatar telefone
function formatarTelefone($telefone) {
    $telefone = preg_replace('/\D/', '', $telefone);
    if (strlen($telefone) == 11) {
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7, 4);
    }
    return $telefone;
}

// Contar estatísticas
$total_participantes = count($participantes);
$presentes = array_filter($participantes, function($p) { return $p['presente'] === 'Sim'; });
$ausentes = array_filter($participantes, function($p) { return $p['presente'] === 'Não'; });
$total_presentes = count($presentes);
$total_ausentes = count($ausentes);

// Exportar como Excel
if ($formato === 'excel') {
    // Definir headers para Excel
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="ritual_' . $ritual['nome'] . '_' . date('Y-m-d') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Começar saída HTML para Excel
    echo "\xEF\xBB\xBF"; // BOM para UTF-8
    ?>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
            th, td { border: 1px solid #333; padding: 12px; text-align: left; }
            th { background-color: #0066cc; color: white; font-weight: bold; }
            .header { font-size: 24px; font-weight: bold; margin-bottom: 30px; text-align: center; color: #0066cc; }
            .info-table th { background-color: #f0f8ff; color: #0066cc; }
            .section-title { background-color: #0066cc; color: white; font-size: 16px; text-align: center; }
            .stats { background-color: #e8f4fd; }
            .stats-presente { background-color: #d4edda; color: #155724; }
            .stats-ausente { background-color: #f8d7da; color: #721c24; }
            .logo-section { text-align: center; margin-bottom: 30px; }
            .company-info { text-align: center; margin-bottom: 20px; color: #666; }
        </style>
    </head>
    <body>
        <div class="logo-section">
            <div class="company-info">
                <h2 style="color: #0066cc; margin: 0;">Sistema ICI</h2>
                <p style="margin: 5px 0;">Instituto de Consciência Integral</p>
            </div>
        </div>

        <div class="header">Relatório do Ritual</div>

        <table class="info-table">
            <tr><th colspan="2" class="section-title">Dados do Ritual</th></tr>
            <tr><td><strong>Nome:</strong></td><td><?= htmlspecialchars($ritual['nome']) ?></td></tr>
            <tr><td><strong>Data:</strong></td><td><?= (new DateTime($ritual['data_ritual']))->format('d/m/Y') ?></td></tr>
            <tr><td><strong>Padrinho/Madrinha:</strong></td><td><?= htmlspecialchars($ritual['padrinho_madrinha']) ?></td></tr>
        </table>

        <table class="info-table">
            <tr><th colspan="2" class="section-title">Estatísticas de Participação</th></tr>
            <tr class="stats"><td><strong>Total de Participantes:</strong></td><td><?= $total_participantes ?></td></tr>
            <tr class="stats-presente"><td><strong>Presentes:</strong></td><td><?= $total_presentes ?></td></tr>
            <tr class="stats-ausente"><td><strong>Ausentes:</strong></td><td><?= $total_ausentes ?></td></tr>
            <tr class="stats">
                <td><strong>Taxa de Presença:</strong></td>
                <td><?= $total_participantes > 0 ? round(($total_presentes / $total_participantes) * 100, 1) : 0 ?>%</td>
            </tr>
        </table>

        <?php if (!empty($participantes)): ?>
        <table>
            <tr><th colspan="7" class="section-title">Lista de Participantes</th></tr>
            <tr>
                <th>Nome Completo</th>
                <th>CPF</th>
                <th>Celular</th>
                <th>E-mail</th>
                <th>Presente</th>
                <th>1ª Vez Instituto</th>
                <th>Observação</th>
            </tr>
            <?php foreach ($participantes as $participante): ?>
            <tr>
                <td><?= htmlspecialchars($participante['nome_completo']) ?></td>
                <td><?= formatarCPF($participante['cpf']) ?></td>
                <td><?= formatarTelefone($participante['celular']) ?></td>
                <td><?= htmlspecialchars($participante['email'] ?: '-') ?></td>
                <td style="background-color: <?= $participante['presente'] === 'Sim' ? '#d4edda' : '#f8d7da' ?>; color: <?= $participante['presente'] === 'Sim' ? '#155724' : '#721c24' ?>;">
                    <?= $participante['presente'] === 'Sim' ? 'SIM' : 'NÃO' ?>
                </td>
                <td><?= $participante['primeira_vez_instituto'] === 'Sim' ? 'SIM' : 'NÃO' ?></td>
                <td><?= htmlspecialchars($participante['observacao'] ?: '-') ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>

        <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #0066cc; text-align: center;">
            <p style="font-size: 12px; color: #666; margin: 0;">
                Relatório gerado em <?= date('d/m/Y H:i:s') ?> | Sistema ICI - Instituto de Consciência Integral
            </p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Exportar como PDF
if ($formato === 'pdf') {
    // Criar instância do TCPDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Configurações do documento
    $pdf->SetCreator('Sistema ICI');
    $pdf->SetAuthor('Instituto de Consciência Integral');
    $pdf->SetTitle('Relatório do Ritual - ' . $ritual['nome']);
    $pdf->SetSubject('Relatório do Ritual');

    // Remover header e footer padrão
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Configurar margens
    $pdf->SetMargins(15, 20, 15);
    $pdf->SetAutoPageBreak(TRUE, 25);

    // Adicionar página
    $pdf->AddPage();

    // Caminho da logo
    $logo_path = __DIR__ . '/../../../public_html/assets/images/logo.png';

    // Verificar se a logo existe
    if (file_exists($logo_path)) {
        // Adicionar logo (centralizada)
        $pdf->Image($logo_path, 85, 15, 40, 0, '', '', '', false, 300, '', false, false, 0);
        $pdf->SetY(45);
    } else {
        $pdf->SetY(20);
    }

    // Cabeçalho da empresa
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetTextColor(0, 102, 204); // Azul
    $pdf->Cell(0, 8, 'INSTITUTO DE CONSCIÊNCIA INTEGRAL', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(102, 102, 102); // Cinza
    $pdf->Cell(0, 6, 'Sistema de Gestão de Rituais', 0, 1, 'C');
    $pdf->Ln(5);

    // Linha decorativa
    $pdf->SetDrawColor(0, 102, 204);
    $pdf->SetLineWidth(0.8);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(8);

    // Título do relatório
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->SetTextColor(0, 102, 204);
    $pdf->Cell(0, 10, 'RELATÓRIO DO RITUAL', 0, 1, 'C');
    $pdf->Ln(8);

    // Reset para cor preta
    $pdf->SetTextColor(0, 0, 0);

    // Dados do ritual com estilo profissional
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetFillColor(0, 102, 204);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 10, 'DADOS DO RITUAL', 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(3);

    $pdf->SetFont('helvetica', '', 10);

    $html = '
    <table border="0" cellpadding="8" cellspacing="0" style="border: 1px solid #ddd;">
        <tr style="background-color: #f8f9fa;">
            <td width="35%" style="border-bottom: 1px solid #ddd; border-right: 1px solid #ddd;"><strong style="color: #0066cc;">Nome:</strong></td>
            <td width="65%" style="border-bottom: 1px solid #ddd;">' . htmlspecialchars($ritual['nome']) . '</td>
        </tr>
        <tr>
            <td style="border-bottom: 1px solid #ddd; border-right: 1px solid #ddd; background-color: #f8f9fa;"><strong style="color: #0066cc;">Data:</strong></td>
            <td style="border-bottom: 1px solid #ddd;">' . (new DateTime($ritual['data_ritual']))->format('d/m/Y') . '</td>
        </tr>
        <tr style="background-color: #f8f9fa;">
            <td style="border-right: 1px solid #ddd;"><strong style="color: #0066cc;">Padrinho/Madrinha:</strong></td>
            <td>' . htmlspecialchars($ritual['padrinho_madrinha']) . '</td>
        </tr>
    </table>';

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Ln(10);

    // Estatísticas com cores
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetFillColor(0, 102, 204);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 10, 'ESTATÍSTICAS DE PARTICIPAÇÃO', 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(3);

    $pdf->SetFont('helvetica', '', 10);

    $taxa_presenca = $total_participantes > 0 ? round(($total_presentes / $total_participantes) * 100, 1) : 0;

    $html_stats = '
    <table border="0" cellpadding="8" cellspacing="0" style="border: 1px solid #ddd;">
        <tr style="background-color: #e8f4fd;">
            <td width="35%" style="border-bottom: 1px solid #ddd; border-right: 1px solid #ddd;"><strong style="color: #0066cc;">Total de Participantes:</strong></td>
            <td width="65%" style="border-bottom: 1px solid #ddd; text-align: center; font-size: 14px;"><strong>' . $total_participantes . '</strong></td>
        </tr>
        <tr style="background-color: #d4edda;">
            <td style="border-bottom: 1px solid #ddd; border-right: 1px solid #ddd;"><strong style="color: #155724;">Presentes:</strong></td>
            <td style="border-bottom: 1px solid #ddd; text-align: center; font-size: 14px; color: #155724;"><strong>' . $total_presentes . '</strong></td>
        </tr>
        <tr style="background-color: #f8d7da;">
            <td style="border-bottom: 1px solid #ddd; border-right: 1px solid #ddd;"><strong style="color: #721c24;">Ausentes:</strong></td>
            <td style="border-bottom: 1px solid #ddd; text-align: center; font-size: 14px; color: #721c24;"><strong>' . $total_ausentes . '</strong></td>
        </tr>
        <tr style="background-color: #fff3cd;">
            <td style="border-right: 1px solid #ddd;"><strong style="color: #856404;">Taxa de Presença:</strong></td>
            <td style="text-align: center; font-size: 14px; color: #856404;"><strong>' . $taxa_presenca . '%</strong></td>
        </tr>
    </table>';

    $pdf->writeHTML($html_stats, true, false, true, false, '');
    $pdf->Ln(10);

    // Lista de participantes
    if (!empty($participantes)) {
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetFillColor(0, 102, 204);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(0, 10, 'LISTA DE PARTICIPANTES', 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(3);

        $pdf->SetFont('helvetica', '', 8);

        $html_participantes = '
        <table border="1" cellpadding="4" cellspacing="0" style="border-collapse: collapse;">
            <tr style="background-color: #0066cc; color: white;">
                <th width="25%" style="text-align: center;"><strong>Nome</strong></th>
                <th width="15%" style="text-align: center;"><strong>CPF</strong></th>
                <th width="15%" style="text-align: center;"><strong>Celular</strong></th>
                <th width="12%" style="text-align: center;"><strong>Presente</strong></th>
                <th width="8%" style="text-align: center;"><strong>1ª Vez</strong></th>
                <th width="25%" style="text-align: center;"><strong>Observação</strong></th>
            </tr>';

        $row_color = true;
        foreach ($participantes as $participante) {
            $bg_color = $row_color ? '#f8f9fa' : '#ffffff';
            $presente_color = $participante['presente'] === 'Sim' ? '#d4edda' : '#f8d7da';
            $presente_text_color = $participante['presente'] === 'Sim' ? '#155724' : '#721c24';
            $primeira_vez_color = $participante['primeira_vez_instituto'] === 'Sim' ? '#fff3cd' : '#e2e3e5';
            $primeira_vez_text_color = $participante['primeira_vez_instituto'] === 'Sim' ? '#856404' : '#6c757d';

            $html_participantes .= '
            <tr style="background-color: ' . $bg_color . ';">
                <td>' . htmlspecialchars($participante['nome_completo']) . '</td>
                <td style="text-align: center;">' . formatarCPF($participante['cpf']) . '</td>
                <td style="text-align: center;">' . formatarTelefone($participante['celular']) . '</td>
                <td style="text-align: center; background-color: ' . $presente_color . '; color: ' . $presente_text_color . ';"><strong>' . ($participante['presente'] === 'Sim' ? 'SIM' : 'NÃO') . '</strong></td>
                <td style="text-align: center; background-color: ' . $primeira_vez_color . '; color: ' . $primeira_vez_text_color . ';"><strong>' . ($participante['primeira_vez_instituto'] === 'Sim' ? 'SIM' : 'NÃO') . '</strong></td>
                <td style="font-size: 7px;">' . htmlspecialchars(mb_substr($participante['observacao'] ?: 'Nenhuma observação', 0, 60)) . '</td>
            </tr>';
            $row_color = !$row_color;
        }

        $html_participantes .= '</table>';

        $pdf->writeHTML($html_participantes, true, false, true, false, '');
    }

    // Rodapé profissional
    $pdf->SetY(-35);

    // Linha decorativa
    $pdf->SetDrawColor(0, 102, 204);
    $pdf->SetLineWidth(0.5);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(5);

    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(102, 102, 102);
    $pdf->Cell(0, 5, 'Relatório gerado em ' . date('d/m/Y H:i:s') . ' | Sistema ICI v1.0', 0, 1, 'C');
    $pdf->Cell(0, 5, 'Instituto de Consciência Integral - Gestão de Rituais', 0, 1, 'C');

    // Enviar PDF
    $filename = 'ritual_' . preg_replace('/[^a-zA-Z0-9]/', '_', $ritual['nome']) . '_' . date('Y-m-d') . '.pdf';
    $pdf->Output($filename, 'D');
    exit;
}

// Se chegou aqui, formato inválido
$_SESSION['error'] = 'Formato de exportação inválido.';
header('Location: /participantesici/public_html/ritual/' . $id);
exit;
?>