<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../config/database.php';

// Verifica se o ID foi passado e é válido
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
  $_SESSION['error'] = 'ID do ritual inválido.';
  header('Location: /participantesici/public_html/rituais');
  exit;
}

// ✅ FUNÇÃO PARA EXCLUIR FOTO DO RITUAL
function excluirFotoRitual($nomeRitual)
{
  if (empty($nomeRitual))
    return;

  // Limpa o nome do ritual (mesmo processo do upload)
  $nomeRitualLimpo = preg_replace(
    '/[^a-zA-Z0-9]/',
    '',
    str_replace(
      [
        'á',
        'à',
        'ã',
        'â',
        'é',
        'è',
        'ê',
        'í',
        'ì',
        'î',
        'ó',
        'ò',
        'õ',
        'ô',
        'ú',
        'ù',
        'û',
        'ç',
        'ñ',
        'Á',
        'À',
        'Ã',
        'Â',
        'É',
        'È',
        'Ê',
        'Í',
        'Ì',
        'Î',
        'Ó',
        'Ò',
        'Õ',
        'Ô',
        'Ú',
        'Ù',
        'Û',
        'Ç',
        'Ñ'
      ],
      [
        'a',
        'a',
        'a',
        'a',
        'e',
        'e',
        'e',
        'i',
        'i',
        'i',
        'o',
        'o',
        'o',
        'o',
        'u',
        'u',
        'u',
        'c',
        'n',
        'A',
        'A',
        'A',
        'A',
        'E',
        'E',
        'E',
        'I',
        'I',
        'I',
        'O',
        'O',
        'O',
        'O',
        'U',
        'U',
        'U',
        'C',
        'N'
      ],
      $nomeRitual
    )
  );

  $diretorio = __DIR__ . '/../../../storage/uploads/rituais/';

  if (is_dir($diretorio)) {
    // Busca todos os arquivos que contenham o nome do ritual
    $arquivos = glob($diretorio . '*_' . substr($nomeRitualLimpo, 0, 20) . '.*');
    foreach ($arquivos as $arquivo) {
      if (file_exists($arquivo)) {
        unlink($arquivo);
      }
    }
  }
}

try {
  $pdo->beginTransaction();

  // ✅ BUSCAR DADOS DO RITUAL ANTES DE EXCLUIR (para pegar o nome)
  $stmt_select = $pdo->prepare("SELECT nome, foto FROM rituais WHERE id = ?");
  $stmt_select->execute([$id]);
  $ritual = $stmt_select->fetch();

  if (!$ritual) {
    $_SESSION['error'] = 'Ritual não encontrado.';
    header('Location: /participantesici/public_html/rituais');
    exit;
  }

  // 1. Remove as inscrições associadas
  $stmt = $pdo->prepare("DELETE FROM inscricoes WHERE ritual_id = ?");
  $stmt->execute([$id]);

  // 2. Remove o ritual
  $stmt = $pdo->prepare("DELETE FROM rituais WHERE id = ?");
  $stmt->execute([$id]);

  $pdo->commit();

  // ✅ EXCLUIR FOTO APÓS COMMIT BEM-SUCEDIDO
  if ($ritual['nome']) {
    excluirFotoRitual($ritual['nome']);
  }

  $_SESSION['success'] = 'Ritual excluído com sucesso!';
} catch (PDOException $e) {
  $pdo->rollBack();
  $_SESSION['error'] = 'Erro ao excluir ritual: ' . $e->getMessage();
}

header('Location: /participantesici/public_html/rituais');
exit;