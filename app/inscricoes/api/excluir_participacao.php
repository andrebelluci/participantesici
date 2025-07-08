<?php

require_once __DIR__ . '/../../functions/check_auth_api.php';
require_once __DIR__ . '/../../config/database.php';

// Verifica se os parâmetros foram fornecidos
$participante_id = $_GET['participante_id'] ?? null;
$ritual_id = $_GET['ritual_id'] ?? null;
$redirect = $_GET['redirect'] ?? null;

// Validação de parâmetros
if (!$participante_id || !$ritual_id || !is_numeric($participante_id) || !is_numeric($ritual_id)) {
    $_SESSION['error'] = "Parâmetros inválidos para desvinculação.";

    // Redirect inteligente baseado no referrer
    if ($redirect) {
        header("Location: $redirect");
    } else {
        $referrer = $_SERVER['HTTP_REFERER'] ?? '/';
        header("Location: $referrer");
    }
    exit;
}

try {
    // Verifica se a inscrição existe antes de tentar excluir
    $stmt_check = $pdo->prepare("
        SELECT id FROM inscricoes
        WHERE participante_id = ? AND ritual_id = ?
    ");
    $stmt_check->execute([$participante_id, $ritual_id]);
    $inscricao = $stmt_check->fetch();

    // ✅ DETECTA DE ONDE VEIO A REQUISIÇÃO PARA PERSONALIZAR MENSAGENS
    $referrer = $_SERVER['HTTP_REFERER'] ?? '';
    $vemDeParticipante = strpos($referrer, '/participante/') !== false;
    $vemDeRitual = strpos($referrer, '/ritual/') !== false;

    if (!$inscricao) {
        if ($vemDeParticipante) {
            $_SESSION['error'] = "Ritual não encontrado ou já foi desvinculado deste participante.";
        } elseif ($vemDeRitual) {
            $_SESSION['error'] = "Participante não encontrado ou já foi desvinculado deste ritual.";
        } else {
            $_SESSION['error'] = "Inscrição não encontrada. Pode já ter sido desvinculada.";
        }
    } else {
        // Remove a inscrição da tabela
        $stmt_delete = $pdo->prepare("
            DELETE FROM inscricoes
            WHERE participante_id = ? AND ritual_id = ?
        ");
        $stmt_delete->execute([$participante_id, $ritual_id]);

        if ($stmt_delete->rowCount() > 0) {
            if ($vemDeParticipante) {
                $_SESSION['success'] = "Ritual desvinculado do participante com sucesso!";
            } elseif ($vemDeRitual) {
                $_SESSION['success'] = "Participante desvinculado do ritual com sucesso!";
            } else {
                $_SESSION['success'] = "Desvinculação realizada com sucesso!";
            }
        } else {
            if ($vemDeParticipante) {
                $_SESSION['error'] = "Não foi possível desvincular o ritual do participante.";
            } elseif ($vemDeRitual) {
                $_SESSION['error'] = "Não foi possível desvincular o participante do ritual.";
            } else {
                $_SESSION['error'] = "Não foi possível realizar a desvinculação.";
            }
        }
    }

} catch (Exception $e) {
    error_log("Erro ao desvincular: " . $e->getMessage());

    if ($vemDeParticipante) {
        $_SESSION['error'] = "Erro interno ao desvincular ritual do participante.";
    } elseif ($vemDeRitual) {
        $_SESSION['error'] = "Erro interno ao desvincular participante do ritual.";
    } else {
        $_SESSION['error'] = "Erro interno ao processar desvinculação.";
    }
}

// ✅ REDIRECT INTELIGENTE
if ($redirect) {
    // Usa o redirect específico passado
    header("Location: $redirect");
} else {
    // Detecta automaticamente de onde veio
    $referrer = $_SERVER['HTTP_REFERER'] ?? '';

    if (strpos($referrer, '/participante/') !== false) {
        // Veio da página do participante - volta para lá
        preg_match('/\/participante\/(\d+)/', $referrer, $matches);
        if (isset($matches[1])) {
            header("Location: /participante/{$matches[1]}");
        } else {
            header("Location: /participantes");
        }
    } elseif (strpos($referrer, '/ritual/') !== false) {
        // Veio da página do ritual - volta para lá
        preg_match('/\/ritual\/(\d+)/', $referrer, $matches);
        if (isset($matches[1])) {
            header("Location: /ritual/{$matches[1]}");
        } else {
            header("Location: /rituais");
        }
    } else {
        // Fallback: volta para a página anterior
        header("Location: $referrer");
    }
}
exit;
