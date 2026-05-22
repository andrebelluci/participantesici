<?php
/**
 * Botão Adicionar ritual (habilitado só para participante ativo).
 * Requer: $participante_pode_adicionar_ritual, $participante_status, $participante_motivo_status
 */
require_once __DIR__ . '/../../functions/participante_status.php';

$btnClass = $btnClass ?? 'bg-[#00bfff] text-black px-2 md:px-6 py-2 rounded font-semibold shadow';
$iconClass = $iconClass ?? 'fa-solid fa-plus mr-2';
$label = $label ?? 'Adicionar ritual';

if (!empty($participante_pode_adicionar_ritual)):
?>
  <button type="button" onclick="abrirModalAdicionar()"
    class="<?= htmlspecialchars($btnClass) ?> hover:bg-yellow-400 transition">
    <i class="<?= htmlspecialchars($iconClass) ?>"></i> <?= htmlspecialchars($label) ?>
  </button>
<?php else:
  $statusNorm = $participante_status ?? PARTICIPANTE_STATUS_ATIVO;
  $statusLabel = participanteStatusLabel($statusNorm);
  $motivo = participanteMotivoParaModal($participante_motivo_status ?? null, $statusNorm);
?>
  <div class="flex flex-wrap items-center gap-2 justify-end">
    <button type="button" disabled
      class="<?= htmlspecialchars($btnClass) ?> opacity-50 cursor-not-allowed"
      title="Participante <?= htmlspecialchars($statusLabel) ?> — não é possível adicionar novos rituais">
      <i class="<?= htmlspecialchars($iconClass) ?>"></i> <?= htmlspecialchars($label) ?>
    </button>
    <button type="button"
      class="<?= participanteStatusMotivoBtnClasses($participante_status ?? PARTICIPANTE_STATUS_ATIVO) ?>"
      data-status-titulo="<?= participanteEscaparDataAttr($statusLabel) ?>"
      data-status-motivo="<?= participanteEscaparDataAttr($motivo) ?>">
      <i class="fa-solid fa-circle-info"></i>
      <span>Ver motivo</span>
    </button>
  </div>
<?php endif; ?>
