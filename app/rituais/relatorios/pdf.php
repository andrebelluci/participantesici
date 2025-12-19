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

if (!$id) {
    $_SESSION['error'] = 'ID do ritual inválido.';
    header('Location: /rituais');
    exit;
}

// Buscar dados do ritual
$stmt = $pdo->prepare("SELECT * FROM rituais WHERE id = ?");
$stmt->execute([$id]);
$ritual = $stmt->fetch();

if (!$ritual) {
    $_SESSION['error'] = 'Ritual não encontrado.';
    header('Location: /rituais');
    exit;
}

// Buscar todos os participantes do ritual
$sql_participantes = "
    SELECT p.*, i.id as inscricao_id, i.presente, i.observacao,
           i.primeira_vez_instituto, i.primeira_vez_ayahuasca,
           i.doenca_psiquiatrica, i.nome_doenca,
           i.uso_medicao, i.nome_medicao, i.mensagem,
           i.salvo_em, i.obs_salvo_em,
           i.assinatura, i.assinatura_data
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

// Função para processar assinatura e salvar arquivo temporário para TCPDF
function processarAssinaturaParaPDF($assinatura_base64, $inscricao_id, $pdf)
{
    if (empty($assinatura_base64)) {
        return null;
    }

    try {
        // Decodificar base64
        $image_data = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $assinatura_base64));
        if (!$image_data) {
            error_log("[PDF] Erro ao decodificar base64 da assinatura");
            return null;
        }

        // Criar imagem a partir dos dados
        $source = @imagecreatefromstring($image_data);
        if (!$source) {
            error_log("[PDF] Erro ao criar imagem a partir dos dados");
            return null;
        }

        // Redimensionar para tamanho maior para melhor qualidade
        $width = imagesx($source);
        $height = imagesy($source);
        $new_width = 70;
        $new_height = 30;

        // Criar imagem temporária maior para melhor qualidade
        $temp_thumb = imagecreatetruecolor($new_width, $new_height);
        imagealphablending($temp_thumb, false);
        imagesavealpha($temp_thumb, true);

        // Preencher com branco (fundo branco)
        $white = imagecolorallocate($temp_thumb, 255, 255, 255);
        imagefill($temp_thumb, 0, 0, $white);

        // Redimensionar com melhor qualidade
        imagecopyresampled($temp_thumb, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

        // Criar imagem final com fundo branco sólido
        $thumb = imagecreatetruecolor($new_width, $new_height);
        imagealphablending($thumb, false);
        imagesavealpha($thumb, false);

        // Preencher com branco
        $white_bg = imagecolorallocate($thumb, 255, 255, 255);
        imagefill($thumb, 0, 0, $white_bg);

        // Converter para preto usando threshold suave
        // Percorrer pixels e converter para preto se não for muito claro
        for ($x = 0; $x < $new_width; $x++) {
            for ($y = 0; $y < $new_height; $y++) {
                $rgb = imagecolorat($temp_thumb, $x, $y);
                $a = ($rgb >> 24) & 0xFF;

                // Se não for transparente
                if ($a < 127) {
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;

                    // Calcular luminosidade
                    $luminance = ($r * 0.299 + $g * 0.587 + $b * 0.114);

                    // Threshold mais suave: se for mais escuro que 240 (muito claro), vira preto
                    // Isso preserva a assinatura mas escurece ela
                    if ($luminance < 240) {
                        // Escurecer proporcionalmente: quanto mais escuro, mais preto
                        $intensity = 255 - (int) (($luminance / 240) * 255);
                        $intensity = max(0, min(255, $intensity));
                        $color = imagecolorallocate($thumb, $intensity, $intensity, $intensity);
                        imagesetpixel($thumb, $x, $y, $color);
                    }
                }
            }
        }

        imagedestroy($temp_thumb);

        // Salvar em arquivo temporário
        $temp_file = sys_get_temp_dir() . '/assinatura_' . $inscricao_id . '_' . uniqid() . '.png';
        if (!imagepng($thumb, $temp_file)) {
            error_log("[PDF] Erro ao salvar arquivo de assinatura: $temp_file");
            imagedestroy($source);
            imagedestroy($thumb);
            return null;
        }

        imagedestroy($source);
        imagedestroy($thumb);

        // Ler arquivo e converter para base64
        $thumb_data = file_get_contents($temp_file);
        @unlink($temp_file); // Limpar imediatamente

        return 'data:image/png;base64,' . base64_encode($thumb_data);
    } catch (Exception $e) {
        error_log("[PDF] Exceção ao processar assinatura: " . $e->getMessage());
        return null;
    }
}

// Contar estatísticas
$total_participantes = count($participantes);
<<<<<<< HEAD
$masculinos = array_filter($participantes, function ($p) {
    return $p['sexo'] === 'M';
});
$femininos = array_filter($participantes, function ($p) {
    return $p['sexo'] === 'F';
});
$total_masculinos = count($masculinos);
$total_femininos = count($femininos);
=======
>>>>>>> 17c2916 (feat: Melhorias no sistema de inscrições e visualização de documentos)
$presentes = array_filter($participantes, function ($p) {
    return $p['presente'] === 'Sim';
});
$ausentes = array_filter($participantes, function ($p) {
    return $p['presente'] === 'Não';
});
$total_presentes = count($presentes);
$total_ausentes = count($ausentes);

// Criar instância do TCPDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Configurações do documento
$pdf->SetCreator('Participantes ICI');
$pdf->SetAuthor('Instituto Céu Interior');
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
$pdf->Ln(5);

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
    <tr style="background-color: #e1f5fe;">
        <td style="border-bottom: 1px solid #ddd; border-right: 1px solid #ddd;"><strong style="color: #0277bd;">Masculino:</strong></td>
        <td style="border-bottom: 1px solid #ddd; text-align: center; font-size: 14px; color: #0277bd;"><strong>' . $total_masculinos . '</strong></td>
    </tr>
    <tr style="background-color: #fce4ec;">
        <td style="border-bottom: 1px solid #ddd; border-right: 1px solid #ddd;"><strong style="color: #c2185b;">Feminino:</strong></td>
        <td style="border-bottom: 1px solid #ddd; text-align: center; font-size: 14px; color: #c2185b;"><strong>' . $total_femininos . '</strong></td>
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
$pdf->AddPage();

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
            <th width="22%" style="text-align: center;"><strong>Nome</strong></th>
            <th width="13%" style="text-align: center;"><strong>CPF</strong></th>
            <th width="14%" style="text-align: center;"><strong>Celular</strong></th>
            <th width="9%" style="text-align: center;"><strong>Presente</strong></th>
            <th width="7%" style="text-align: center;"><strong>1ª Vez</strong></th>
            <th width="10%" style="text-align: center;"><strong>Assinatura</strong></th>
            <th width="24%" style="text-align: center;"><strong>Observação</strong></th>
        </tr>';

    $row_color = true;

    foreach ($participantes as $participante) {
        $bg_color = $row_color ? '#f8f9fa' : '#ffffff';
        $presente_color = $participante['presente'] === 'Sim' ? '#d4edda' : '#f8d7da';
        $presente_text_color = $participante['presente'] === 'Sim' ? '#155724' : '#721c24';
        $primeira_vez_color = $participante['primeira_vez_instituto'] === 'Sim' ? '#fff3cd' : '#e2e3e5';
        $primeira_vez_text_color = $participante['primeira_vez_instituto'] === 'Sim' ? '#856404' : '#6c757d';

        $assinatura_html = '';
        if (!empty($participante['assinatura'])) {
            // Tentar processar assinatura
            $thumb_base64 = processarAssinaturaParaPDF($participante['assinatura'], $participante['inscricao_id'] ?? uniqid(), $pdf);
            if ($thumb_base64) {
                // Tentar usar base64 inline - TCPDF pode ou não suportar
                // Se não funcionar, o usuário verá "SIM" como fallback
                $assinatura_html = '<img src="' . htmlspecialchars($thumb_base64, ENT_QUOTES, 'UTF-8') . '" width="70" height="30" border="0" />';
            } else {
                // Fallback: mostrar "SIM" quando houver assinatura mas não conseguir processar
                $assinatura_html = '<span style="color: #999; font-size: 8px;">SIM</span>';
            }
        } else {
            $assinatura_html = '<span style="font-size: 8px;">NÃO</span>';
        }

        $html_participantes .= '
        <tr style="background-color: ' . $bg_color . ';">
            <td>' . htmlspecialchars($participante['nome_completo']) . '</td>
            <td style="text-align: center;">' . formatarCPF($participante['cpf']) . '</td>
            <td style="text-align: center;">' . formatarTelefone($participante['celular']) . '</td>
            <td style="text-align: center; background-color: ' . $presente_color . '; color: ' . $presente_text_color . ';"><strong>' . ($participante['presente'] === 'Sim' ? 'SIM' : 'NÃO') . '</strong></td>
            <td style="text-align: center; background-color: ' . $primeira_vez_color . '; color: ' . $primeira_vez_text_color . ';"><strong>' . ($participante['primeira_vez_instituto'] === 'Sim' ? 'SIM' : 'NÃO') . '</strong></td>
            <td style="text-align: center; vertical-align: middle;">' . $assinatura_html . '</td>
            <td style="font-size: 7px;">' . htmlspecialchars(mb_substr($participante['observacao'] ?: 'Nenhuma observação', 0, 50)) . '</td>
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
$pdf->Cell(0, 5, 'Relatório gerado em ' . date('d/m/Y H:i:s') . ' | Instituto Céu Interior - Gestão de Participantes', 0, 1, 'C');

// Enviar PDF
$filename = 'ritual_' . preg_replace('/[^a-zA-Z0-9]/', '_', $ritual['nome']) . '_' . date('Y-m-d') . '.pdf';
$pdf->Output($filename, 'D');
exit;
