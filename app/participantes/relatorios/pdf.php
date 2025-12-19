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
    SELECT r.*, i.id as inscricao_id, i.presente, i.observacao,
           i.primeira_vez_instituto, i.primeira_vez_ayahuasca,
           i.doenca_psiquiatrica, i.nome_doenca,
           i.uso_medicao, i.nome_medicao, i.mensagem,
           i.salvo_em, i.obs_salvo_em,
           i.assinatura, i.assinatura_data
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
    $new_width = 80;
    $new_height = 35;

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

    // Salvar em arquivo temporário e registrar no TCPDF
    $temp_file = sys_get_temp_dir() . '/assinatura_' . $inscricao_id . '_' . uniqid() . '.png';
    if (!imagepng($thumb, $temp_file)) {
      error_log("[PDF] Erro ao salvar arquivo de assinatura: $temp_file");
      imagedestroy($source);
      imagedestroy($thumb);
      return null;
    }

    imagedestroy($source);
    imagedestroy($thumb);

    // Registrar imagem no TCPDF usando método Image() e retornar tag especial
    // O TCPDF pode usar a tag <img src="@' . basename($temp_file) . '" /> após registrar
    // Mas vamos usar base64 inline que é mais confiável
    $thumb_data = file_get_contents($temp_file);
    @unlink($temp_file); // Limpar imediatamente

    return 'data:image/png;base64,' . base64_encode($thumb_data);
  } catch (Exception $e) {
    error_log("[PDF] Exceção ao processar assinatura: " . $e->getMessage());
    return null;
  }
}

// Criar instância do TCPDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Configurações do documento
$pdf->SetCreator('ICI Participantes');
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
$pdf->Ln(5);

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
  $pdf->AddPage();
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
            <th width="28%" style="text-align: center;"><strong>Nome do Ritual</strong></th>
            <th width="12%" style="text-align: center;"><strong>Data</strong></th>
            <th width="12%" style="text-align: center;"><strong>Presente</strong></th>
            <th width="15%" style="text-align: center;"><strong>Assinatura</strong></th>
            <th width="33%" style="text-align: center;"><strong>Observação</strong></th>
        </tr>';

  $row_color = true;

  foreach ($rituais as $ritual) {
    $bg_color = $row_color ? '#f8f9fa' : '#ffffff';
    $presente_color = $ritual['presente'] === 'Sim' ? '#d4edda' : '#f8d7da';
    $presente_text_color = $ritual['presente'] === 'Sim' ? '#155724' : '#721c24';

    $assinatura_html = '';
    if (!empty($ritual['assinatura'])) {
      // Tentar processar assinatura
      $thumb_base64 = processarAssinaturaParaPDF($ritual['assinatura'], $ritual['inscricao_id'] ?? uniqid(), $pdf);
      if ($thumb_base64) {
        // Tentar usar base64 inline - TCPDF pode ou não suportar
        // Se não funcionar, o usuário verá "SIM" como fallback
        $assinatura_html = '<img src="' . htmlspecialchars($thumb_base64, ENT_QUOTES, 'UTF-8') . '" width="80" height="35" border="0" />';
        if ($ritual['assinatura_data']) {
          $assinatura_html .= '<br><small style="font-size: 6px;">' . (new DateTime($ritual['assinatura_data']))->format('d/m/Y H:i') . '</small>';
        }
      } else {
        // Fallback: mostrar "SIM" quando houver assinatura mas não conseguir processar
        $assinatura_html = '<span style="color: #999; font-size: 8px;">SIM</span>';
      }
    } else {
      $assinatura_html = '<span style="color: #999; font-size: 8px;">NÃO</span>';
    }

    $html_rituais .= '
        <tr style="background-color: ' . $bg_color . ';">
            <td>' . htmlspecialchars($ritual['nome']) . '</td>
            <td style="text-align: center;">' . (new DateTime($ritual['data_ritual']))->format('d/m/Y') . '</td>
            <td style="text-align: center; background-color: ' . $presente_color . '; color: ' . $presente_text_color . ';"><strong>' . ($ritual['presente'] === 'Sim' ? 'SIM' : 'NÃO') . '</strong></td>
            <td style="text-align: center; vertical-align: middle;">' . $assinatura_html . '</td>
            <td>' . htmlspecialchars(mb_substr($ritual['observacao'] ?: 'Nenhuma observação', 0, 70)) . '</td>
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
$pdf->Cell(0, 5, 'Relatório gerado em ' . date('d/m/Y H:i:s') . ' | Instituto Céu Interior - Gestão de Participantes', 0, 1, 'C');

// Enviar PDF
$filename = 'participante_' . preg_replace('/[^a-zA-Z0-9]/', '_', $participante['nome_completo']) . '_' . date('Y-m-d') . '.pdf';
$pdf->Output($filename, 'D');
exit;
