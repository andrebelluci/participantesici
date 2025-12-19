<?php
// Configura tratamento de erros ANTES de qualquer include
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Inicia buffer de saída para capturar qualquer output indesejado
ob_start();

require_once __DIR__ . '/../../functions/check_auth_api.php';
require_once __DIR__ . '/../../config/database.php';

// Limpa qualquer output que possa ter sido gerado antes
ob_end_clean();

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inscricao_id = $_POST['id'] ?? null;

    // Converte strings vazias para NULL para campos ENUM
    $primeira_vez_instituto = !empty($_POST['primeira_vez_instituto']) ? $_POST['primeira_vez_instituto'] : null;
    $primeira_vez_ayahuasca = !empty($_POST['primeira_vez_ayahuasca']) ? $_POST['primeira_vez_ayahuasca'] : null;
    $doenca_psiquiatrica = !empty($_POST['doenca_psiquiatrica']) ? $_POST['doenca_psiquiatrica'] : null;
    $uso_medicao = !empty($_POST['uso_medicao']) ? $_POST['uso_medicao'] : null;

    // Campos de texto podem ser vazios
    $nome_doenca = $_POST['nome_doenca'] ?? '';
    $nome_medicao = $_POST['nome_medicao'] ?? '';
    $mensagem = $_POST['mensagem'] ?? '';

    // Validação básica
    if (!$inscricao_id) {
        echo json_encode(['success' => false, 'error' => 'ID da inscrição não fornecido.']);
        exit;
    }

    // Valida valores ENUM
    $valoresEnumValidos = ['Sim', 'Não'];
    if ($primeira_vez_instituto !== null && !in_array($primeira_vez_instituto, $valoresEnumValidos)) {
        echo json_encode(['success' => false, 'error' => 'Valor inválido para primeira_vez_instituto.']);
        exit;
    }
    if ($primeira_vez_ayahuasca !== null && !in_array($primeira_vez_ayahuasca, $valoresEnumValidos)) {
        echo json_encode(['success' => false, 'error' => 'Valor inválido para primeira_vez_ayahuasca.']);
        exit;
    }
    if ($doenca_psiquiatrica !== null && !in_array($doenca_psiquiatrica, $valoresEnumValidos)) {
        echo json_encode(['success' => false, 'error' => 'Valor inválido para doenca_psiquiatrica.']);
        exit;
    }
    if ($uso_medicao !== null && !in_array($uso_medicao, $valoresEnumValidos)) {
        echo json_encode(['success' => false, 'error' => 'Valor inválido para uso_medicao.']);
        exit;
    }

    try {
        // Primeiro, busca o participante_id da inscrição atual
        $stmt = $pdo->prepare("SELECT participante_id FROM inscricoes WHERE id = ?");
        $stmt->execute([$inscricao_id]);
        $inscricao = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$inscricao) {
            echo json_encode(['success' => false, 'error' => 'Inscrição não encontrada.']);
            exit;
        }

        $participante_id = $inscricao['participante_id'];

        // Atualiza os detalhes da inscrição e registra a data/hora de salvamento
        $stmt = $pdo->prepare("
            UPDATE inscricoes
            SET
                primeira_vez_instituto = ?,
                primeira_vez_ayahuasca = ?,
                doenca_psiquiatrica = ?,
                nome_doenca = ?,
                uso_medicao = ?,
                nome_medicao = ?,
                mensagem = ?,
                salvo_em = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $primeira_vez_instituto,
            $primeira_vez_ayahuasca,
            $doenca_psiquiatrica,
            $nome_doenca,
            $uso_medicao,
            $nome_medicao,
            $mensagem,
            $inscricao_id
        ]);

        // Para as outras inscrições não salvas, aplica a lógica de primeira vez
        // Verifica se já existe alguma inscrição salva anteriormente para este participante
        $stmt = $pdo->prepare("
            SELECT primeira_vez_instituto, primeira_vez_ayahuasca
            FROM inscricoes
            WHERE participante_id = ?
            AND primeira_vez_instituto IS NOT NULL
            AND primeira_vez_ayahuasca IS NOT NULL
            AND id != ?
            ORDER BY id ASC
            LIMIT 1
        ");
        $stmt->execute([$participante_id, $inscricao_id]);
        $inscricao_anterior = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($inscricao_anterior) {
            // Se já tem inscrição anterior, sempre será "Não" nas outras
            $primeira_vez_instituto_outros = 'Não';
            $primeira_vez_ayahuasca_outros = 'Não';
        } else {
            // Se esta é a primeira inscrição salva, usa os valores salvos
            // Se foi "Sim", nas outras será "Não" (já não é mais a primeira vez)
            // Se foi "Não", nas outras também será "Não"
            // Se foi NULL, mantém NULL para ser preenchido depois
            if ($primeira_vez_instituto === 'Sim') {
                $primeira_vez_instituto_outros = 'Não';
            } elseif ($primeira_vez_instituto === 'Não') {
                $primeira_vez_instituto_outros = 'Não';
            } else {
                $primeira_vez_instituto_outros = null;
            }

            if ($primeira_vez_ayahuasca === 'Sim') {
                $primeira_vez_ayahuasca_outros = 'Não';
            } elseif ($primeira_vez_ayahuasca === 'Não') {
                $primeira_vez_ayahuasca_outros = 'Não';
            } else {
                $primeira_vez_ayahuasca_outros = null;
            }
        }

        // Copia os dados copiáveis (doenca_psiquiatrica, nome_doenca, uso_medicao, nome_medicao, mensagem)
        // e preenche primeira_vez_instituto e primeira_vez_ayahuasca
        // para todas as outras inscrições do mesmo participante que ainda não foram salvas
        $stmt = $pdo->prepare("
            UPDATE inscricoes
            SET
                primeira_vez_instituto = ?,
                primeira_vez_ayahuasca = ?,
                doenca_psiquiatrica = ?,
                nome_doenca = ?,
                uso_medicao = ?,
                nome_medicao = ?,
                mensagem = ?
            WHERE participante_id = ?
            AND id != ?
            AND salvo_em IS NULL
        ");
        $stmt->execute([
            $primeira_vez_instituto_outros,
            $primeira_vez_ayahuasca_outros,
            $doenca_psiquiatrica,
            $nome_doenca,
            $uso_medicao,
            $nome_medicao,
            $mensagem,
            $participante_id,
            $inscricao_id
        ]);

        echo json_encode(['success' => true]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar detalhes da inscrição: ' . $e->getMessage()]);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método de requisição inválido.']);
    exit;
}
