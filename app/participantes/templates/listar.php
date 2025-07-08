<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-screen-xl mx-auto px-4 py-6">
  <!-- Cabeçalho -->
  <div class="flex items-center justify-between mb-6">
    <?php
    // Verifica se há um parâmetro 'redirect' na URL
    $redirect = isset($_GET['redirect']) ? htmlspecialchars($_GET['redirect']) : 'home';
    ?>
    <a href="<?= $redirect ?>" class="flex items-center text-gray-600 hover:text-[#00bfff] transition text-sm">
      <i class="fa-solid fa-arrow-left mr-2"></i> Voltar
    </a>
    <a href="/participante/novo"
      class="bg-[#00bfff] text-black px-6 py-2 rounded hover:bg-yellow-400 transition font-semibold shadow">
      <i class="fa-solid fa-plus"></i>
      Novo Participante
    </a>
  </div>

  <div class="flex flex-col sm:flex-row justify-between md:items-end gap-4">
    <h1 class="text-2xl font-bold text-gray-800 md:mb-4 flex items-center gap-2">
      <i class="fa-solid fa-users text-blue-500"></i>Participantes
    </h1>

    <div class="flex justify-end md:mb-4">
      <button type="button" id="view-toggle"
        class="hidden md:flex items-center justify-center bg-gray-100 text-gray-700 w-10 h-10 rounded hover:bg-gray-200 transition border border-gray-300"
        title="Alternar visualização">
        <i class="fa-solid fa-list text-lg"></i>
      </button>
    </div>
  </div>

  <div class="md:hidden flex items-center justify-between">
    <!-- Botão de abrir filtros no mobile -->
    <button type="button" onclick="document.getElementById('filtros').classList.toggle('hidden')" class="
      bg-gray-200 text-gray-700 px-4 py-2 rounded mb-4 flex items-center gap-2 text-sm shadow hover:bg-gray-300">
      <i class="fa-solid fa-search"></i> Filtrar
    </button>
    <!-- Botão limpar visível no mobile -->
    <a href="/participantes"
      class="<?= empty($filtro_nome) && empty($filtro_cpf) ? 'hidden' : '' ?> bg-red-600 text-white px-4 py-2 rounded mb-4 flex items-center gap-2 text-sm shadow hover:bg-red-300 transition">
      <i class="fa-solid fa-broom mr-1"></i> Limpar Filtro
    </a>
  </div>

  <!-- Filtros -->
  <div class="form-container mobile-compact">
    <form id="filtros"
      class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-white p-3 rounded-lg shadow border border-gray-200 mb-6 <?= empty($filtro_nome) && empty($filtro_cpf) ? 'hidden md:grid' : '' ?>"
      method="GET">
      <div>
        <label for="filtro_nome" class="block text-sm font-medium text-gray-700 mb-1">Nome:</label>
        <input type="search" inputmode="search" name="filtro_nome" id="filtro_nome" placeholder="Filtrar por nome"
          value="<?= htmlspecialchars($filtro_nome) ?>"
          class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#00bfff]">
      </div>
      <div>
        <label for="filtro_cpf" class="block text-sm font-medium text-gray-700 mb-1">CPF:</label>
        <input type="text" inputmode="numeric" pattern="[0-9]\s\-]*" name="filtro_cpf" id="filtro_cpf"
          placeholder="___.___.___-__" value="<?= htmlspecialchars($filtro_cpf) ?>" oninput="mascaraCPF(this)"
          class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#00bfff]">
      </div>
      <div class="flex items-end gap-2">
        <button type="submit"
          class="bg-[#00bfff] text-black px-4 py-2 rounded hover:bg-yellow-400 transition font-semibold shadow">
          <i class="fa-solid fa-search mr-1"></i> Buscar
        </button>
        <a href="/participantes"
          class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 transition font-semibold shadow">
          <i class="fa-solid fa-broom mr-1"></i> Limpar
        </a>
      </div>
    </form>
  </div>

  <!-- Lista de Cards -->
  <div id="cards-view" class="grid gap-4 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
    <?php foreach ($pessoas as $pessoa): ?>
      <div class="bg-white p-4 rounded-lg shadow border border-gray-200 flex flex-col gap-3">
        <div class="flex items-center gap-4">
          <img src="<?= htmlspecialchars($pessoa['foto']) ?>" alt="Foto"
            class="w-16 h-16 rounded-lg object-cover border border-gray-300 cursor-pointer"
            onclick="openImageModal(this.src)"
            onerror="this.src='/assets/images/no-image.png'; this.onclick=null; this.classList.remove('cursor-pointer');">
          <div class="text-sm text-gray-600 space-y-1">
            <h2 class="text-lg font-bold text-gray-800"><?= htmlspecialchars($pessoa['nome_completo']) ?></h2>
            <p><span class="font-semibold">Nascimento:</span>
              <?= (new DateTime($pessoa['nascimento']))->format('d/m/Y') ?></p>
            <p><span class="font-semibold">CPF:</span> <?= formatarCPF($pessoa['cpf']) ?></p>
            <p><span class="font-semibold">Celular:</span> <?= formatarTelefone($pessoa['celular']) ?></p>
            <p><span class="font-semibold">Rituais:</span> <?= htmlspecialchars($pessoa['rituais_participados']) ?></p>
          </div>
        </div>

        <!-- Ações: Desktop (topo) | Mobile (base do card) -->
        <div class="flex justify-center gap-6 md:justify-end md:gap-2 mt-2 text-sm">
          <a href="/participante/<?= $pessoa['id'] ?>"
            class="bg-green-100 hover:bg-green-200 text-green-700 px-3 py-1 rounded flex items-center gap-1"
            title="Gerenciar rituais">
            <div class="flex flex-col items-center sm:flex-row sm:gap-1">
              <i class="fa-solid fa-list-check text-lg"></i>
              <span class="block sm:hidden text-xs mt-1">Rituais</span>
            </div>
          </a>
          <a href="/participante/<?= $pessoa['id'] ?>/editar"
            class="bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-1 rounded flex items-center gap-1"
            title="Editar participante">
            <div class="flex flex-col items-center sm:flex-row sm:gap-1">
              <i class="fa-solid fa-pen-to-square text-lg"></i>
              <span class="block sm:hidden text-xs mt-1">Editar</span>
            </div>
          </a>
          <?php if ($is_admin): ?>
            <button
              onclick="abrirConfirmacaoExcluir('/participante/<?= $pessoa['id'] ?>/excluir')"
              class="bg-red-100 hover:bg-red-200 text-red-700 px-3 py-1 rounded flex items-center gap-1"
              title="Excluir participante">
              <div class="flex flex-col items-center sm:flex-row sm:gap-1">
                <i class="fa-solid fa-trash text-lg"></i>
                <span class="block sm:hidden text-xs mt-1">Excluir</span>
              </div>
            </button>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (empty($pessoas)): ?>
      <p class="text-center text-gray-500 col-span-full mt-4">
        Nenhum participante encontrado.
      </p>
    <?php endif; ?>
  </div>

  <!-- Table View para Participantes (adicionar APÓS os cards) -->
  <div id="table-view" class="hidden bg-white rounded-lg shadow overflow-hidden border border-gray-200">
    <div class="overflow-x-auto">
      <table class="w-full">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Foto</th>
            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">
              <a href="?<?= http_build_query(array_merge($_GET, ['order_by' => 'nome_completo', 'order_dir' => $order_by === 'nome_completo' && $order_dir === 'ASC' ? 'DESC' : 'ASC'])) ?>"
                class="hover:text-[#00bfff] flex items-center gap-1">
                Nome
                <?php if ($order_by === 'nome_completo'): ?>
                  <i class="fa-solid fa-sort-<?= $order_dir === 'ASC' ? 'up' : 'down' ?>"></i>
                <?php endif; ?>
              </a>
            </th>
            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">CPF</th>
            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Celular</th>
            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Rituais</th>
            <th class="px-4 py-3 text-center text-sm font-medium text-gray-700">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php if (empty($pessoas)): ?>
            <tr>
              <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                <i class="fa-solid fa-users text-3xl mb-2"></i>
                <p>Nenhum participante encontrado</p>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($pessoas as $pessoa): ?>
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                  <img src="<?= htmlspecialchars($pessoa['foto']) ?>" alt="Foto"
                    class="w-12 h-12 rounded-lg object-cover border border-gray-300 cursor-pointer"
                    onclick="openImageModal(this.src)"
                    onerror="this.src='/assets/images/no-image.png'; this.onclick=null; this.classList.remove('cursor-pointer');">
                </td>
                <td class="px-4 py-3 text-sm font-medium text-gray-900">
                  <?= htmlspecialchars($pessoa['nome_completo']) ?>
                  <div class="text-xs text-gray-500">
                    <?= (new DateTime($pessoa['nascimento']))->format('d/m/Y') ?>
                  </div>
                </td>
                <td class="px-4 py-3 text-sm text-gray-600">
                  <?= formatarCPF($pessoa['cpf']) ?>
                </td>
                <td class="px-4 py-3 text-sm text-gray-600">
                  <?= formatarTelefone($pessoa['celular']) ?>
                </td>
                <td class="px-4 py-3 text-sm text-gray-600">
                  <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                    <?= htmlspecialchars($pessoa['rituais_participados']) ?>
                  </span>
                </td>
                <td class="px-4 py-3 text-center">
                  <div class="flex justify-center gap-2">
                    <a href="/participante/<?= $pessoa['id'] ?>"
                      class="bg-green-100 hover:bg-green-200 text-green-700 px-3 py-1 rounded flex items-center gap-1"
                      title="Visualizar participante">
                      <i class="fa-solid fa-list-check"></i>
                    </a>
                    <a href="/participante/<?= $pessoa['id'] ?>/editar"
                      class="bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-1 rounded flex items-center gap-1"
                      title="Editar participante">
                      <i class="fa-solid fa-edit"></i>
                    </a>
                    <?php if ($is_admin): ?>
                    <button
                      onclick="abrirConfirmacaoExcluir('/participante/<?= $pessoa['id'] ?>/excluir')"
                      class="bg-red-100 hover:bg-red-200 text-red-700 px-3 py-1 rounded flex items-center gap-1"
                      title="Excluir participante">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Info da listagem -->
  <div class="mt-4 text-sm text-gray-600">
    <?php if (!empty($pessoas)): ?>
      <p>
        Mostrando <?= count($pessoas) ?> de <?= $total_registros ?> participante(s)
        <?php if (!empty($filtros)): ?>
          (filtrado)
        <?php endif; ?>
      </p>
    <?php endif; ?>
  </div>

  <!-- Paginação -->
  <div class="flex justify-center mt-6 flex-wrap gap-2">
    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
      <a href="?pagina=<?= $i ?>&filtro_nome=<?= htmlspecialchars($filtro_nome) ?>&filtro_cpf=<?= htmlspecialchars($filtro_cpf) ?>"
        class="px-4 py-2 rounded border transition
          <?= $pagina == $i ? 'bg-[#00bfff] text-black font-semibold shadow' : 'bg-white text-gray-600 hover:bg-gray-100' ?>">
        <?= $i ?>
      </a>
    <?php endfor; ?>
  </div>
</div>

<!-- Botão Voltar ao Topo -->
<button id="scroll-to-top"
  class="fixed bottom-12 right-4 bg-[#00bfff] md:hover:bg-yellow-400 text-black p-3 rounded-full shadow-lg transform transition-all duration-300 ease-in-out opacity-0 invisible translate-y-4 z-50">
  <i class="fa-solid fa-chevron-up md:text-lg"></i>
</button>

<!-- Modal de Ampliação de Imagem -->
<div id="modal-image" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-lg overflow-hidden shadow-lg relative max-w-sm w-full mx-4">
    <button onclick="closeImageModal()" class="absolute top-2 right-2 text-red-600 hover:text-red-800 text-lg">
      <i class="fa-solid fa-window-close"></i>
    </button>
    <img id="modal-image-content" class="w-full h-auto object-contain max-h-[80vh]" alt="Imagem Ampliada">
  </div>
</div>

<!-- Modal de Confirmação Genérico -->
<div id="confirmModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative mx-4">
    <h2 class="text-xl font-bold mb-4 text-red-600" id="confirmModalTitle"><i
        class="fa-solid fa-exclamation-triangle mr-2"></i>ATENÇÃO!</h2>
    <p class="text-gray-700 mb-6" id="confirmModalText">Tem certeza que deseja continuar?</p>
    <div class="flex justify-end gap-3">
      <button id="confirmModalBtn"
        class="px-4 py-2 bg-[#00bfff] text-black rounded hover:bg-yellow-400 transition font-semibold">
        Confirmar
      </button>
      <button onclick="closeConfirmModal()"
        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-900 transition font-semibold">
        Cancelar
      </button>
    </div>
  </div>
</div>

<script src="/assets/js/participantes.js"></script>
<script src="/assets/js/modal.js"></script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>