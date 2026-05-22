<?php
if (empty($participante_status) || $participante_status === PARTICIPANTE_STATUS_ATIVO) {
  return;
}
require_once __DIR__ . '/../../functions/participante_status.php';
$label = participanteStatusLabel($participante_status);
$motivoModal = participanteMotivoParaModal($participante_motivo_status ?? null, $participante_status);
?>
<div class="<?= participanteStatusBannerClasses($participante_status) ?> w-full p-4 mb-6 rounded-r flex flex-col md:flex-row items-stretch md:items-center gap-3 md:gap-4 md:justify-between">
  <div class="flex items-start gap-3 flex-1 min-w-0">
    <i class="fa-solid <?= participanteStatusBannerIconClass($participante_status) ?> text-xl mt-0.5 flex-shrink-0"></i>
    <div class="min-w-0">
      <p class="font-semibold">Participante <?= htmlspecialchars($label) ?></p>
      <p class="text-sm mt-1 opacity-90">Não é possível vincular a novos rituais. O histórico e os rituais já cadastrados permanecem disponíveis.</p>
    </div>
  </div>
  <button type="button"
    class="<?= participanteStatusMotivoBtnClasses($participante_status) ?> flex-shrink-0 justify-center"
    data-status-titulo="<?= participanteEscaparDataAttr($label) ?>"
    data-status-motivo="<?= participanteEscaparDataAttr($motivoModal) ?>">
    <i class="fa-solid fa-circle-info"></i>
    <span class="whitespace-nowrap">Ver motivo completo</span>
  </button>
</div>
