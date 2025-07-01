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
    $_SESSION['error'] = 'ID do participante inválido.';
    header('Location: /participantesici/public_html/participantes');
    exit;
}

// Buscar dados do participante
$stmt = $pdo->prepare("SELECT * FROM participantes WHERE id = ?");
$stmt->execute([$id]);
$participante = $stmt->fetch();

if (!$participante) {
    $_SESSION['error'] = 'Participante não encontrado.';
    header('Location: /participantesici/public_html/participantes');
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

// Exportar como Excel
if ($formato === 'excel') {
    // Definir headers para Excel
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="participante_' . $participante['nome_completo'] . '_' . date('Y-m-d') . '.xls"');
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

        <div class="header">Relatório do Participante</div>

        <table class="info-table">
            <tr><th colspan="2" class="section-title">Dados Pessoais</th></tr>
            <tr><td><strong>Nome Completo:</strong></td><td><?= htmlspecialchars($participante['nome_completo']) ?></td></tr>
            <tr><td><strong>Data de Nascimento:</strong></td><td><?= (new DateTime($participante['nascimento']))->format('d/m/Y') ?></td></tr>
            <tr><td><strong>Sexo:</strong></td><td><?= $participante['sexo'] === 'M' ? 'Masculino' : 'Feminino' ?></td></tr>
            <tr><td><strong>CPF:</strong></td><td><?= formatarCPF($participante['cpf']) ?></td></tr>
            <?php if (!empty($participante['rg'])): ?>
            <tr><td><strong>RG:</strong></td><td><?= htmlspecialchars($participante['rg']) ?></td></tr>
            <?php endif; ?>
            <tr><td><strong>Celular:</strong></td><td><?= formatarTelefone($participante['celular']) ?></td></tr>
            <?php if (!empty($participante['email'])): ?>
            <tr><td><strong>E-mail:</strong></td><td><?= htmlspecialchars($participante['email']) ?></td></tr>
            <?php endif; ?>
        </table>

        <?php if (!empty($participante['endereco_rua'])): ?>
        <table class="info-table">
            <tr><th colspan="2" class="section-title">Endereço</th></tr>
            <tr><td><strong>CEP:</strong></td><td><?= htmlspecialchars($participante['cep']) ?></td></tr>
            <tr><td><strong>Endereço:</strong></td><td><?= htmlspecialchars($participante['endereco_rua']) ?>, <?= htmlspecialchars($participante['endereco_numero']) ?></td></tr>
            <?php if (!empty($participante['endereco_complemento'])): ?>
            <tr><td><strong>Complemento:</strong></td><td><?= htmlspecialchars($participante['endereco_complemento']) ?></td></tr>
            <?php endif; ?>
            <tr><td><strong>Bairro:</strong></td><td><?= htmlspecialchars($participante['bairro']) ?></td></tr>
            <tr><td><strong>Cidade/UF:</strong></td><td><?= htmlspecialchars($participante['cidade']) ?>/<?= htmlspecialchars($participante['estado']) ?></td></tr>
        </table>
        <?php endif; ?>

        <?php if (!empty($rituais)): ?>
        <table>
            <tr><th colspan="4" class="section-title">Rituais Participados (<?= count($rituais) ?>)</th></tr>
            <tr>
                <th>Nome do Ritual</th>
                <th>Data</th>
                <th>Presente</th>
                <th>Observação</th>
            </tr>
            <?php foreach ($rituais as $ritual): ?>
            <tr>
                <td><?= htmlspecialchars($ritual['nome']) ?></td>
                <td><?= (new DateTime($ritual['data_ritual']))->format('d/m/Y') ?></td>
                <td style="background-color: <?= $ritual['presente'] === 'Sim' ? '#d4edda' : '#f8d7da' ?>; color: <?= $ritual['presente'] === 'Sim' ? '#155724' : '#721c24' ?>;">
                    <?= $ritual['presente'] === 'Sim' ? 'Sim' : 'Não' ?>
                </td>
                <td><?= htmlspecialchars($ritual['observacao'] ?: '-') ?></td>
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
    $pdf->SetCreator('Participantes ICI');
    $pdf->SetAuthor('Instituto Céu Interior');
    $pdf->SetTitle('Relatório do Participante - ' . $participante['nome_completo']);
    $pdf->SetSubject('Relatório do Participante');

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
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(102, 102, 102); // Cinza
    $pdf->Cell(0, 6, 'Gestão de Participantes', 0, 1, 'C');
    $pdf->Ln(5);

    // Linha decorativa
    $pdf->SetDrawColor(0, 102, 204);
    $pdf->SetLineWidth(0.8);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(8);

    // Título do relatório
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->SetTextColor(0, 102, 204);
    $pdf->Cell(0, 10, 'RELATÓRIO DO PARTICIPANTE', 0, 1, 'C');
    $pdf->Ln(8);

    // Reset para cor preta
    $pdf->SetTextColor(0, 0, 0);

    // Dados pessoais com estilo profissional
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetFillColor(0, 102, 204);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 10, 'DADOS PESSOAIS', 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(3);

    $pdf->SetFont('helvetica', '', 10);

    $html = '
    <table border="0" cellpadding="8" cellspacing="0" style="border: 1px solid #ddd;">
        <tr style="background-color: #f8f9fa;">
            <td width="35%" style="border-bottom: 1px solid #ddd; border-right: 1px solid #ddd;"><strong style="color: #0066cc;">Nome Completo:</strong></td>
            <td width="65%" style="border-bottom: 1px solid #ddd;">' . htmlspecialchars($participante['nome_completo']) . '</td>
        </tr>
        <tr>
            <td style="border-bottom: 1px solid #ddd; border-right: 1px solid #ddd; background-color: #f8f9fa;"><strong style="color: #0066cc;">Data de Nascimento:</strong></td>
            <td style="border-bottom: 1px solid #ddd;">' . (new DateTime($participante['nascimento']))->format('d/m/Y') . '</td>
        </tr>
        <tr style="background-color: #f8f9fa;">
            <td style="border-bottom: 1px solid #ddd; border-right: 1px solid #ddd;"><strong style="color: #0066cc;">Sexo:</strong></td>
            <td style="border-bottom: 1px solid #ddd;">' . ($participante['sexo'] === 'M' ? 'Masculino' : 'Feminino') . '</td>
        </tr>
        <tr>
            <td style="border-bottom: 1px solid #ddd; border-right: 1px solid #ddd; background-color: #f8f9fa;"><strong style="color: #0066cc;">CPF:</strong></td>
            <td style="border-bottom: 1px solid #ddd;">' . formatarCPF($participante['cpf']) . '</td>
        </tr>';

    $html .= '
        <tr>
            <td style="border-bottom: 1px solid #ddd; border-right: 1px solid #ddd; background-color: #f8f9fa;"><strong style="color: #0066cc;">Celular:</strong></td>
            <td style="border-bottom: 1px solid #ddd;">' . formatarTelefone($participante['celular']) . '</td>
        </tr>';

    if (!empty($participante['email'])) {
        $html .= '
        <tr style="background-color: #f8f9fa;">
            <td style="border-right: 1px solid #ddd;"><strong style="color: #0066cc;">E-mail:</strong></td>
            <td>' . htmlspecialchars($participante['email']) . '</td>
        </tr>';
    } else {
        // Remove a borda inferior da última linha se não tem email
        $html = str_replace('border-bottom: 1px solid #ddd;">' . formatarTelefone($participante['celular']), '">' . formatarTelefone($participante['celular']), $html);
    }

    $html .= '</table>';

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Ln(10);

    // Endereço (se existir)
    if (!empty($participante['endereco_rua'])) {
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetFillColor(0, 102, 204);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(0, 10, 'ENDEREÇO', 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(3);

        $pdf->SetFont('helvetica', '', 10);

        $html_endereco = '
        <table border="0" cellpadding="8" cellspacing="0" style="border: 1px solid #ddd;">
            <tr style="background-color: #f8f9fa;">
                <td width="35%" style="border-bottom: 1px solid #ddd; border-right: 1px solid #ddd;"><strong style="color: #0066cc;">CEP:</strong></td>
                <td width="65%" style="border-bottom: 1px solid #ddd;">' . htmlspecialchars($participante['cep']) . '</td>
            </tr>
            <tr>
                <td style="border-bottom: 1px solid #ddd; border-right: 1px solid #ddd; background-color: #f8f9fa;"><strong style="color: #0066cc;">Endereço:</strong></td>
                <td style="border-bottom: 1px solid #ddd;">' . htmlspecialchars($participante['endereco_rua']) . ', ' . htmlspecialchars($participante['endereco_numero']) . '</td>
            </tr>';

        if (!empty($participante['endereco_complemento'])) {
            $html_endereco .= '
            <tr style="background-color: #f8f9fa;">
                <td style="border-bottom: 1px solid #ddd; border-right: 1px solid #ddd;"><strong style="color: #0066cc;">Complemento:</strong></td>
                <td style="border-bottom: 1px solid #ddd;">' . htmlspecialchars($participante['endereco_complemento']) . '</td>
            </tr>';
        }

        $html_endereco .= '
            <tr style="background-color: #f8f9fa;">
                <td style="border-bottom: 1px solid #ddd; border-right: 1px solid #ddd;"><strong style="color: #0066cc;">Bairro:</strong></td>
                <td style="border-bottom: 1px solid #ddd;">' . htmlspecialchars($participante['bairro']) . '</td>
            </tr>
            <tr>
                <td style="border-right: 1px solid #ddd; background-color: #f8f9fa;"><strong style="color: #0066cc;">Cidade/UF:</strong></td>
                <td>' . htmlspecialchars($participante['cidade']) . '/' . htmlspecialchars($participante['estado']) . '</td>
            </tr>
        </table>';

        $pdf->writeHTML($html_endereco, true, false, true, false, '');
        $pdf->Ln(10);
    }

    // Rituais
    if (!empty($rituais)) {
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetFillColor(0, 102, 204);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(0, 10, 'RITUAIS PARTICIPADOS (' . count($rituais) . ')', 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(3);

        $pdf->SetFont('helvetica', '', 9);

        $html_rituais = '
        <table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse;">
            <tr style="background-color: #0066cc; color: white;">
                <th width="35%" style="text-align: center;"><strong>Nome do Ritual</strong></th>
                <th width="15%" style="text-align: center;"><strong>Data</strong></th>
                <th width="15%" style="text-align: center;"><strong>Presente</strong></th>
                <th width="35%" style="text-align: center;"><strong>Observação</strong></th>
            </tr>';

        $row_color = true;
        foreach ($rituais as $ritual) {
            $bg_color = $row_color ? '#f8f9fa' : '#ffffff';
            $presente_color = $ritual['presente'] === 'Sim' ? '#d4edda' : '#f8d7da';
            $presente_text_color = $ritual['presente'] === 'Sim' ? '#155724' : '#721c24';

            $html_rituais .= '
            <tr style="background-color: ' . $bg_color . ';">
                <td>' . htmlspecialchars($ritual['nome']) . '</td>
                <td style="text-align: center;">' . (new DateTime($ritual['data_ritual']))->format('d/m/Y') . '</td>
                <td style="text-align: center; background-color: ' . $presente_color . '; color: ' . $presente_text_color . ';"><strong>' . ($ritual['presente'] === 'Sim' ? 'SIM' : 'NÃO') . '</strong></td>
                <td>' . htmlspecialchars(mb_substr($ritual['observacao'] ?: 'Nenhuma observação', 0, 80)) . '</td>
            </tr>';
            $row_color = !$row_color;
        }

        $html_rituais .= '</table>';

        $pdf->writeHTML($html_rituais, true, false, true, false, '');
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
    $pdf->Cell(0, 5, 'Instituto de Consciência Integral - Gestão de Participantes', 0, 1, 'C');

    // Enviar PDF
    $filename = 'participante_' . preg_replace('/[^a-zA-Z0-9]/', '_', $participante['nome_completo']) . '_' . date('Y-m-d') . '.pdf';
    $pdf->Output($filename, 'D');
    exit;
}

// Se chegou aqui, formato inválido
$_SESSION['error'] = 'Formato de exportação inválido.';
header('Location: /participantesici/public_html/participante/' . $id);
exit;
?>