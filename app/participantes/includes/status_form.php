<?php
require_once __DIR__ . '/../../functions/participante_status.php';

$statusAtual = participanteNormalizarStatus($pessoa['status'] ?? null);
$motivoAtual = $pessoa['motivo_status'] ?? '';
$labels = participanteStatusLabels();
$exibirCampoMotivo = $statusAtual !== PARTICIPANTE_STATUS_ATIVO;
?>
<div class="border border-gray-200 rounded-lg p-4 bg-gray-50/50">
    <h2 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
        <i class="fa-solid fa-user-check text-indigo-600"></i>
        Status do participante
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
        <?php foreach ($labels as $valor => $label): ?>
            <?php
            $checked = $statusAtual === $valor;
            $borderClass = match ($valor) {
                PARTICIPANTE_STATUS_ATIVO => 'border-green-500',
                PARTICIPANTE_STATUS_INATIVO => 'border-orange-300',
                PARTICIPANTE_STATUS_NAO_PODE_PARTICIPAR => 'border-red-500',
                default => 'border-gray-300',
            };
            $icon = match ($valor) {
                PARTICIPANTE_STATUS_ATIVO => 'fa-circle-check text-green-600',
                PARTICIPANTE_STATUS_INATIVO => 'fa-user-slash text-orange-700',
                PARTICIPANTE_STATUS_NAO_PODE_PARTICIPAR => 'fa-ban text-red-600',
                default => 'fa-circle',
            };
            ?>
            <label
                class="status-radio-card relative flex flex-col items-center gap-2 p-4 border-2 rounded-lg cursor-pointer bg-white hover:bg-gray-50 transition <?= $checked ? $borderClass . ' ring-2' : 'border-gray-200' ?>">
                <input type="radio" name="status" value="<?= htmlspecialchars($valor) ?>"
                    class="sr-only peer" <?= $checked ? 'checked' : '' ?>>
                <i class="fa-solid <?= $icon ?> text-xl"></i>
                <span class="text-sm font-semibold text-center text-gray-800"><?= htmlspecialchars($label) ?></span>
            </label>
        <?php endforeach; ?>
    </div>

    <div id="campo-motivo-status" class="<?= $exibirCampoMotivo ? '' : 'hidden' ?>">
        <label for="motivo_status" class="block text-sm font-medium text-gray-700 mb-1">
            Motivo / observação
            <span id="motivo-obrigatorio-label" class="text-red-600 hidden">*</span>
        </label>
        <textarea name="motivo_status" id="motivo_status" rows="3"
            class="w-full border border-gray-300 rounded px-3 py-2 bg-white"
            placeholder="Descreva o motivo (obrigatório para &quot;Não pode participar&quot;)..."><?= htmlspecialchars($motivoAtual) ?></textarea>
        <p id="motivo-status-hint" class="text-xs text-gray-500 mt-1"></p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const radios = document.querySelectorAll('input[name="status"]');
        const campoMotivo = document.getElementById('campo-motivo-status');
        const motivo = document.getElementById('motivo_status');
        const hint = document.getElementById('motivo-status-hint');
        const obrigatorioLabel = document.getElementById('motivo-obrigatorio-label');
        const cards = document.querySelectorAll('.status-radio-card');

        if (!campoMotivo || !motivo || !radios.length) return;

        function statusSelecionado() {
            const r = document.querySelector('input[name="status"]:checked');
            return r ? r.value : 'ativo';
        }

        function atualizarCards() {
            const atual = statusSelecionado();
            cards.forEach(card => {
                const input = card.querySelector('input[name="status"]');
                if (!input) return;
                const on = input.value === atual;
                card.classList.toggle('ring-2', on);
                card.classList.toggle('border-green-500', on && input.value === 'ativo');
                card.classList.toggle('border-orange-300', on && input.value === 'inativo');
                card.classList.toggle('border-red-500', on && input.value === 'nao_pode_participar');
                card.classList.toggle('border-gray-200', !on);
            });
        }

        function atualizarMotivo() {
            const s = statusSelecionado();
            if (s === 'ativo') {
                campoMotivo.classList.add('hidden');
                motivo.disabled = true;
                motivo.removeAttribute('required');
                motivo.value = '';
                obrigatorioLabel.classList.add('hidden');
            } else {
                campoMotivo.classList.remove('hidden');
                motivo.disabled = false;
                motivo.classList.remove('bg-gray-100', 'cursor-not-allowed');
                if (s === 'inativo') {
                    motivo.removeAttribute('required');
                    hint.textContent = 'Motivo opcional (ex.: mudou de religião, cadastro obsoleto).';
                    obrigatorioLabel.classList.add('hidden');
                } else {
                    motivo.setAttribute('required', 'required');
                    hint.textContent = 'Motivo obrigatório para participantes que não podem participar.';
                    obrigatorioLabel.classList.remove('hidden');
                }
            }
            atualizarCards();
        }

        radios.forEach(r => r.addEventListener('change', atualizarMotivo));
        atualizarMotivo();
    });
</script>
