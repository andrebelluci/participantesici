<?php
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-screen-xl mx-auto px-4 py-6">
  <div class="flex items-center justify-between mb-6">
    <a href="/home"
      class="flex items-center text-gray-600 hover:text-[#00bfff] transition text-sm">
      <i class="fa-solid fa-arrow-left mr-2"></i> Voltar
    </a>

    <a href="/usuario/novo"
      class="bg-[#00bfff] text-black px-6 py-2 rounded hover:bg-yellow-400 transition font-semibold shadow">
      <i class="fa-solid fa-plus mr-2"></i>Novo Usuário
    </a>
  </div>


  <div class="flex flex-col sm:flex-row justify-between md:items-end gap-4">
    <h1 class="text-2xl font-bold text-gray-800 md:mb-4 flex items-center gap-2">
    <i class="fa-solid fa-users-gear text-blue-500"></i>Usuários do Sistema
    </h1>

    <div class=" flex justify-end md:mb-4">
      <button type="button" id="view-toggle"
        class="hidden md:flex items-center justify-center bg-gray-100 text-gray-700 w-10 h-10 rounded hover:bg-gray-200 transition border border-gray-300"
        title="Alternar visualização">
        <i class="fa-solid fa-list text-lg"></i>
      </button>
    </div>
  </div>

  <!-- Filtros -->
  <div class="md:hidden flex items-center justify-between">
    <!-- Botão de abrir filtros no mobile -->
    <button type="button" onclick="document.getElementById('filtro_nome').classList.toggle('hidden')" class="
      bg-gray-200 text-gray-700 px-4 py-2 rounded mb-4 flex items-center gap-2 text-sm shadow hover:bg-gray-300">
      <i class="fa-solid fa-search"></i> Filtrar
    </button>
    <!-- Botão limpar visível no mobile -->
    <a href="/usuarios"
      class="<?= empty($filtro_nome) && empty($filtro_cpf) ? 'hidden' : '' ?> bg-red-600 text-white px-4 py-2 rounded mb-4 flex items-center gap-2 text-sm shadow hover:bg-red-300 transition">
      <i class="fa-solid fa-broom mr-1"></i> Limpar Filtro
    </a>
  </div>


  <div class="form-container mobile-compact">
    <form id="filtro_nome"
      class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-white p-3 rounded-lg shadow border border-gray-200 mb-6 <?= empty($_GET['filtro_nome'] ?? '') ? 'hidden md:grid' : '' ?>"
      method="GET">
      <div>
        <label for="filtro_nome" class="block text-sm font-medium text-gray-700 mb-1">Nome ou Usuário:</label>
        <input type="text" name="filtro_nome" id="filtro_nome"
          value="<?= htmlspecialchars($_GET['filtro_nome'] ?? '') ?>" placeholder="Digite o nome ou usuário..."
          class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#00bfff]">
      </div>
      <div class="flex items-end gap-2">
        <button type="submit"
          class="bg-[#00bfff] text-black px-4 py-2 rounded hover:bg-yellow-400 transition font-semibold shadow">
          <i class="fa-solid fa-search mr-1"></i> Filtrar
        </button>
        <a href="/usuarios"
          class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 transition font-semibold shadow">
          <i class="fa-solid fa-times mr-1"></i> Limpar
        </a>
      </div>
    </form>
  </div>

  <!-- Cards View para Usuários (adicionar ANTES da tabela) -->
  <div id="cards-view" class="grid gap-4 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
    <?php if (empty($usuarios)): ?>
      <div class="col-span-full text-center py-8">
        <i class="fa-solid fa-users text-4xl text-gray-400 mb-4"></i>
        <p class="text-gray-500 text-lg mb-2">Nenhum usuário encontrado</p>
      </div>
    <?php else: ?>
      <?php foreach ($usuarios as $usuario): ?>
        <div class="bg-white p-4 rounded-lg shadow border border-gray-200 flex flex-col gap-3">
          <div class="flex items-center gap-4">
            <!-- Avatar com inicial do nome -->
            <div class="w-16 h-16 rounded-lg bg-[#00bfff] text-black flex items-center justify-center text-xl font-bold">
                <?php
                $nome = trim($usuario['nome']);
                $partes = preg_split('/\s+/', $nome);
                $inicial_nome = strtoupper(mb_substr($partes[0], 0, 1));
                $inicial_sobrenome = isset($partes[1]) ? strtoupper(mb_substr($partes[count($partes) - 1], 0, 1)) : '';
                echo $inicial_nome . $inicial_sobrenome;
                ?>
            </div>

            <div class="flex-1 min-w-0">
              <h3 class="font-bold text-gray-800 text-lg mb-1 truncate">
                <?= htmlspecialchars($usuario['nome']) ?>
              </h3>
              <div class="text-sm text-gray-600 space-y-1">
                <p><span class="font-semibold">Usuário:</span> <?= htmlspecialchars($usuario['usuario']) ?></p>
                <p><span class="font-semibold">E-mail:</span> <?= htmlspecialchars($usuario['email']) ?></p>
                <div class="flex items-center gap-2">
                  <span class="font-semibold">Perfil:</span>
                  <span
                    class="inline-flex px-2 py-1 text-xs font-medium rounded-full <?= $usuario['perfil_nome'] === 'Administrador' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' ?>">
                    <?= htmlspecialchars($usuario['perfil_nome']) ?>
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Ações -->
          <div class="flex justify-center gap-6 md:justify-end md:gap-2 mt-2 text-sm">
            <a href="/usuario/<?= $usuario['id'] ?>/editar"
              class="bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-1 rounded flex items-center gap-1"
              title="Editar usuário">
              <div class="flex flex-col items-center sm:flex-row sm:gap-1">
                <i class="fa-solid fa-edit text-lg"></i>
                <span class="block sm:hidden text-xs mt-1">Editar</span>
              </div>
            </a>
            <?php if ($usuario['id'] != $_SESSION['user_id']): ?>
                    <button onclick="abrirConfirmacaoExcluir('/usuario/<?= $usuario['id'] ?>/excluir')"
                class="bg-red-100 hover:bg-red-200 text-red-700 px-3 py-1 rounded flex items-center gap-1"
                title="Excluir usuário">
                <div class="flex flex-col items-center sm:flex-row sm:gap-1">
                  <i class="fa-solid fa-trash text-lg"></i>
                  <span class="block sm:hidden text-xs mt-1">Excluir</span>
                </div>
              </button>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Tabela -->
  <div id="table-view" class="hidden bg-white rounded-lg shadow overflow-hidden border border-gray-200">
    <div class="overflow-x-auto">
      <table class="w-full">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">
              <a href="?<?= http_build_query(array_merge($_GET, ['order_by' => 'nome', 'order_dir' => $order_by === 'nome' && $order_dir === 'ASC' ? 'DESC' : 'ASC'])) ?>"
                class="hover:text-[#00bfff] flex items-center gap-1">
                Nome
                <?php if ($order_by === 'nome'): ?>
                  <i class="fa-solid fa-sort-<?= $order_dir === 'ASC' ? 'up' : 'down' ?>"></i>
                <?php endif; ?>
              </a>
            </th>
            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">
              <a href="?<?= http_build_query(array_merge($_GET, ['order_by' => 'usuario', 'order_dir' => $order_by === 'usuario' && $order_dir === 'ASC' ? 'DESC' : 'ASC'])) ?>"
                class="hover:text-[#00bfff] flex items-center gap-1">
                Usuário
                <?php if ($order_by === 'usuario'): ?>
                  <i class="fa-solid fa-sort-<?= $order_dir === 'ASC' ? 'up' : 'down' ?>"></i>
                <?php endif; ?>
              </a>
            </th>
            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">
              <a href="?<?= http_build_query(array_merge($_GET, ['order_by' => 'email', 'order_dir' => $order_by === 'email' && $order_dir === 'ASC' ? 'DESC' : 'ASC'])) ?>"
                class="hover:text-[#00bfff] flex items-center gap-1">
                E-mail
                <?php if ($order_by === 'email'): ?>
                  <i class="fa-solid fa-sort-<?= $order_dir === 'ASC' ? 'up' : 'down' ?>"></i>
                <?php endif; ?>
              </a>
            </th>
            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">
              <a href="?<?= http_build_query(array_merge($_GET, ['order_by' => 'perfil_nome', 'order_dir' => $order_by === 'perfil_nome' && $order_dir === 'ASC' ? 'DESC' : 'ASC'])) ?>"
                class="hover:text-[#00bfff] flex items-center gap-1">
                Perfil
                <?php if ($order_by === 'perfil_nome'): ?>
                  <i class="fa-solid fa-sort-<?= $order_dir === 'ASC' ? 'up' : 'down' ?>"></i>
                <?php endif; ?>
              </a>
            </th>
            <th class="px-4 py-3 text-center text-sm font-medium text-gray-700">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php if (empty($usuarios)): ?>
            <tr>
              <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                <i class="fa-solid fa-users text-3xl mb-2"></i>
                <p>Nenhum usuário encontrado</p>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($usuarios as $usuario): ?>
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm font-medium text-gray-900">
                  <?= htmlspecialchars($usuario['nome']) ?>
                </td>
                <td class="px-4 py-3 text-sm text-gray-600">
                  <?= htmlspecialchars($usuario['usuario']) ?>
                </td>
                <td class="px-4 py-3 text-sm text-gray-600">
                  <?= htmlspecialchars($usuario['email']) ?>
                </td>
                <td class="px-4 py-3 text-sm">
                  <span
                    class="inline-flex px-2 py-1 text-xs font-medium rounded-full <?= $usuario['perfil_nome'] === 'Administrador' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' ?>">
                    <?= htmlspecialchars($usuario['perfil_nome']) ?>
                  </span>
                </td>
                <td class="px-4 py-3 text-center">
                  <div class="flex justify-center gap-2">
                    <a href="/usuario/<?= $usuario['id'] ?>/editar"
                      class="bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-1 rounded flex items-center gap-1"
                      title="Editar usuário">
                      <i class="fa-solid fa-edit"></i>
                    </a>
                    <?php if ($usuario['id'] != $_SESSION['user_id']): ?>
                      <button
                        onclick="abrirConfirmacaoExcluir('/usuario/<?= $usuario['id'] ?>/excluir')"
                        class="bg-red-100 hover:bg-red-200 text-red-700 px-3 py-1 rounded flex items-center gap-1"
                        title="Excluir usuário">
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
    <?php if (!empty($usuarios)): ?>
      <p>
        Mostrando <?= count($usuarios) ?> de <?= $total_registros ?> usuário(s)
        <?php if (!empty($filtros)): ?>
          (filtrado)
        <?php endif; ?>
      </p>
    <?php endif; ?>
  </div>

  <!-- Paginação -->
  <?php if ($total_paginas > 1): ?>
    <div class="flex justify-center mt-6 flex-wrap gap-2">
      <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>"
          class="px-4 py-2 rounded border transition
                      <?= $pagina == $i ? 'bg-[#00bfff] text-black font-semibold shadow' : 'bg-white text-gray-600 hover:bg-gray-100' ?>">
          <?= $i ?>
        </a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Botão Voltar ao Topo -->
<button id="scroll-to-top"
  class="fixed bottom-12 right-4 bg-[#00bfff] md:hover:bg-yellow-400 text-black p-3 rounded-full shadow-lg transform transition-all duration-300 ease-in-out opacity-0 invisible translate-y-4 z-50">
  <i class="fa-solid fa-chevron-up md:text-lg"></i>
</button>

<!-- Modal de Confirmação Genérico -->
<div id="confirmModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative mx-4">
    <h2 class="text-xl font-bold mb-4 text-red-600" id="confirmModalTitle">
      <i class="fa-solid fa-exclamation-triangle mr-2"></i>ATENÇÃO!
    </h2>
    <p class="text-gray-700 mb-6" id="confirmModalText">Tem certeza que deseja excluir este usuário?</p>
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

<script src="/assets/js/usuarios.js"></script>
<script src="/assets/js/modal.js"></script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>