<!-- Modal única: motivo do status -->
<div id="modal-motivo-status" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative mx-4">
    <button type="button" onclick="fecharModalMotivoStatus()"
      class="absolute top-2 right-2 text-red-600 hover:text-red-800 text-lg z-10">
      <i class="fa-solid fa-window-close"></i>
    </button>

    <h2 id="modal-motivo-status-titulo" class="text-xl font-bold mb-4 text-gray-800 flex items-center gap-2">
      <i class="fa-solid fa-info-circle text-indigo-600"></i>
      <span>Status do participante</span>
    </h2>

    <div class="space-y-4">
      <p class="text-sm text-gray-600">Motivo / observação registrada:</p>
      <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
        <p id="modal-motivo-status-content" class="text-gray-800 whitespace-pre-wrap"></p>
      </div>
    </div>
  </div>
</div>
