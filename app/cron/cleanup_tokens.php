<?php
// app/cron/cleanup_tokens.php
// Execute este script periodicamente (ex: a cada hora) via cron

require_once __DIR__ . '/../config/database.php';

try {
  // Remove tokens expirados ou usados há mais de 24 horas
  $stmt = $pdo->prepare("
        DELETE FROM password_recovery_tokens
        WHERE expires_at < NOW() OR (used = 1 AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR))
    ");
  $stmt->execute();

  $removidos = $stmt->rowCount();

  if ($removidos > 0) {
    error_log("Cleanup: {$removidos} tokens de recuperação removidos.");
  }

  echo "Cleanup concluído. {$removidos} tokens removidos.\n";

} catch (Exception $e) {
  error_log("Erro no cleanup de tokens: " . $e->getMessage());
  echo "Erro no cleanup: " . $e->getMessage() . "\n";
}
