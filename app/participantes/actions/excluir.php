<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../config/database.php';

$id = $_GET['id'] ?? null;
if (!$id) {
  $_SESSION['error'] = "ID do participante não especificado.";
  header("Location: /participantes");
  exit;
}

// ✅ FUNÇÃO PARA EXCLUIR FOTO DO PARTICIPANTE
function excluirFotoParticipante($cpf)
{
  if (empty($cpf))
    return;

  $cpfLimpo = preg_replace('/\D/', '', $cpf); // Remove tudo que não é número
  $diretorio = __DIR__ . '/../../../public_html/storage/uploads/participantes/';

  if (is_dir($diretorio)) {
    // Busca todos os arquivos que contenham o CPF
    $arquivos = glob($diretorio . '*_' . $cpfLimpo . '.*');
    foreach ($arquivos as $arquivo) {
      if (file_exists($arquivo)) {
        unlink($arquivo);
      }
    }
  }
}

try {
  $pdo->beginTransaction();

  // ✅ BUSCAR DADOS DO PARTICIPANTE ANTES DE EXCLUIR (para pegar o CPF)
  $stmt_select = $pdo->prepare("SELECT cpf, foto FROM participantes WHERE id = ?");
  $stmt_select->execute([$id]);
  $participante = $stmt_select->fetch();

  if (!$participante) {
    $_SESSION['error'] = 'Participante não encontrado.';
    header('Location: /participantes');
    exit;
  }

  // 1. Remove as inscrições associadas
  $stmt_delete_inscricoes = $pdo->prepare("DELETE FROM inscricoes WHERE participante_id = ?");
  $stmt_delete_inscricoes->execute([$id]);

  // 2. Remove o participante
  $stmt_delete_participante = $pdo->prepare("DELETE FROM participantes WHERE id = ?");
  $stmt_delete_participante->execute([$id]);

  $pdo->commit();

  // ✅ EXCLUIR FOTO APÓS COMMIT BEM-SUCEDIDO
  if ($participante['cpf']) {
    excluirFotoParticipante($participante['cpf']);
  }

  $_SESSION['success'] = "Participante excluído com sucesso!";
} catch (Exception $e) {
  $pdo->rollBack();
  $_SESSION['error'] = "Erro ao excluir participante: " . $e->getMessage();
}

header("Location: /participantes");
exit;