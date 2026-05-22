<?php
/**
 * Tag de status clicável (inativo / não pode participar).
 * Requer: $pessoa ou $status + $motivo_status opcional
 */
require_once __DIR__ . '/../../functions/participante_status.php';

$statusTag = participanteNormalizarStatus($pessoa['status'] ?? ($status ?? null));
$motivoTag = $pessoa['motivo_status'] ?? ($motivo_status ?? null);

if ($statusTag === PARTICIPANTE_STATUS_ATIVO) {
  return;
}

$label = participanteStatusLabel($statusTag);
$iconFa = $statusTag === PARTICIPANTE_STATUS_NAO_PODE_PARTICIPAR ? 'fa-ban' : 'fa-user-slash';
$motivoModal = participanteMotivoParaModal($motivoTag, $statusTag);
?>
<button type="button"
  class="js-abrir-motivo-status inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold <?= participanteStatusBadgeClass($statusTag) ?> transition-colors"
  data-status-titulo="<?= participanteEscaparDataAttr($label) ?>"
  data-status-motivo="<?= participanteEscaparDataAttr($motivoModal) ?>"
  title="Ver motivo — <?= htmlspecialchars($label) ?>">
  <i class="fa-solid <?= $iconFa ?>"></i>
  <span><?= htmlspecialchars($label) ?></span>
</button>
