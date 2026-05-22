<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../functions/listagem_retorno.php';
require_once __DIR__ . '/../../includes/header.php';

$urlRetornoRituais = listagemRetornoUrl('/rituais', LISTAGEM_RITUAIS_KEYS);
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
    <a href="/ritual/novo"
      class="bg-[#00bfff] text-black px-6 py-2 rounded hover:bg-yellow-400 transition font-semibold shadow">
      <i class="fa-solid fa-plus"></i>
      Novo Ritual
    </a>
  </div>

  <div class="flex flex-col sm:flex-row justify-between md:items-end gap-4">
    <h1 class="text-2xl font-bold text-gray-800 md:mb-4 flex items-center gap-2">
      <i class="fa-solid fa-fire text-orange-500"></i> Rituais
    </h1>

    <div class="flex justify-end md:mb-4 gap-2">

      <?php if ($is_admin): ?>
        <!-- Botão de Exportação (só para admins) -->
        <div class="relative inline-block">
          <button type="button" id="export-button" onclick="toggleExportDropdown(event)"
            class="flex items-center justify-center bg-orange-100 text-orange-700 w-10 h-10 rounded hover:bg-orange-200 transition border border-orange-300"
            title="Exportar lista de rituais">
            <i class="fa-solid fa-file-export text-lg"></i>
          </button>

          <div id="export-dropdown"
            class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
            <div class="py-2">
              <div class="px-4 py-2 text-sm font-medium text-gray-700 border-b border-gray-100">
                Exportar como:
              </div>
              <button onclick="exportarRituaisList('pdf')"
                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                <i class="fa-solid fa-file-pdf text-red-500"></i>
                PDF
              </button>
              <button onclick="exportarRituaisList('excel')"
                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                <i class="fa-solid fa-file-excel text-green-500"></i>
                Excel
              </button>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <button type="button" id="view-toggle"
        class="hidden md:flex items-center justify-center bg-gray-100 text-gray-700 w-10 h-10 rounded hover:bg-gray-200 transition border border-gray-300"
        title="Alternar visualização">
        <i class="fa-solid fa-list text-lg"></i>
      </button>
    </div>
  </div>

  <div class="md:hidden flex items-center justify-between">
    <button type="button" onclick="document.getElementById('filtros').classList.toggle('hidden')"
      class="bg-gray-200 text-gray-700 px-4 py-2 rounded mb-4 flex items-center gap-2 text-sm shadow hover:bg-gray-300">
      <i class="fa-solid fa-search"></i> Filtrar
    </button>
    <a href="/rituais"
      class="<?= empty($_GET['filtro_nome']) && empty($_GET['data_inicio']) && empty($_GET['data_fim']) ? 'hidden' : '' ?> bg-red-600 text-white px-4 py-2 rounded mb-4 flex items-center gap-2 text-sm shadow hover:bg-red-300">
      <i class="fa-solid fa-broom mr-1"></i> Limpar Filtro
    </a>
  </div>

  <!-- Indicador de Filtros Ativos -->
  <?php
  $tem_nome = !empty($_GET['filtro_nome'] ?? '');
  $tem_data_inicio = !empty($_GET['data_inicio'] ?? '');
  $tem_data_fim = !empty($_GET['data_fim'] ?? '');

  if ($tem_nome || $tem_data_inicio || $tem_data_fim):
    $data_inicio_formatada = $tem_data_inicio ? (new DateTime($_GET['data_inicio']))->format('d/m/Y') : '';
    $data_fim_formatada = $tem_data_fim ? (new DateTime($_GET['data_fim']))->format('d/m/Y') : '';

    // Construir mensagem baseada na combinação de filtros
    $mensagem = '';
    if ($tem_nome && $tem_data_inicio && $tem_data_fim) {
      // 3 filtros
      $mensagem = 'nome "' . htmlspecialchars($_GET['filtro_nome']) . '", data início ' . $data_inicio_formatada . ' e data fim ' . $data_fim_formatada;
    } elseif ($tem_nome && $tem_data_inicio) {
      // Nome + Data início
      $mensagem = 'nome "' . htmlspecialchars($_GET['filtro_nome']) . '" e data início ' . $data_inicio_formatada;
    } elseif ($tem_nome && $tem_data_fim) {
      // Nome + Data fim
      $mensagem = 'nome "' . htmlspecialchars($_GET['filtro_nome']) . '" e data fim ' . $data_fim_formatada;
    } elseif ($tem_data_inicio && $tem_data_fim) {
      // Data início + Data fim
      $mensagem = 'data início ' . $data_inicio_formatada . ' e data fim ' . $data_fim_formatada;
    } elseif ($tem_nome) {
      // Só nome
      $mensagem = 'nome "' . htmlspecialchars($_GET['filtro_nome']) . '"';
    } elseif ($tem_data_inicio) {
      // Só data início
      $mensagem = 'data início ' . $data_inicio_formatada;
    } elseif ($tem_data_fim) {
      // Só data fim
      $mensagem = 'data fim ' . $data_fim_formatada;
    }
    ?>
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4 rounded-r">
      <div class="flex items-start">
        <div class="flex-shrink-0">
          <i class="fa-solid fa-filter text-blue-500"></i>
        </div>
        <div class="ml-3 flex-1">
          <p class="text-sm text-blue-700">
            <strong>Filtro<?= ($tem_nome && $tem_data_inicio) || ($tem_nome && $tem_data_fim) || ($tem_data_inicio && $tem_data_fim) ? 's' : '' ?>
              ativo<?= ($tem_nome && $tem_data_inicio) || ($tem_nome && $tem_data_fim) || ($tem_data_inicio && $tem_data_fim) ? 's' : '' ?>:</strong>
            Exibindo rituais com <?= $mensagem ?>.
          </p>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Filtros -->
  <div class="form-container mobile-compact">
    <form id="filtros" method="GET"
      class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-white p-3 rounded-lg shadow border border-gray-200 mb-6 <?= empty($_GET['filtro_nome']) && empty($_GET['data_inicio']) && empty($_GET['data_fim']) ? 'hidden md:grid' : '' ?>">
      <div>
        <label for="filtro_nome" class="block text-sm font-medium text-gray-700 mb-1">Nome:</label>
        <input type="text" name="filtro_nome" id="filtro_nome" placeholder="Filtrar por nome"
          value="<?= htmlspecialchars($_GET['filtro_nome'] ?? '') ?>"
          class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#00bfff]">
      </div>
      <div>
        <label for="data_inicio" class="block text-sm font-medium text-gray-700 mb-1">Data início:</label>
        <input type="date" name="data_inicio" id="data_inicio"
          value="<?= htmlspecialchars($_GET['data_inicio'] ?? '') ?>"
          class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#00bfff]"
          onchange="validarDatas()">
        <p id="erro-data-inicio" class="text-xs text-red-600 mt-1 hidden"></p>
      </div>
      <div>
        <label for="data_fim" class="block text-sm font-medium text-gray-700 mb-1">Data fim:</label>
        <input type="date" name="data_fim" id="data_fim" value="<?= htmlspecialchars($_GET['data_fim'] ?? '') ?>"
          class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#00bfff]"
          onchange="validarDatas()">
        <p id="erro-data-fim" class="text-xs text-red-600 mt-1 hidden"></p>
      </div>
      <div class="flex items-end gap-2 ">
        <button type="submit" id="btn-buscar-rituais"
          class="bg-[#00bfff] text-black px-4 py-2 rounded hover:bg-yellow-400 transition font-semibold shadow">
          <i class="fa-solid fa-search mr-1"></i> Buscar
        </button>
        <a href="/rituais"
          class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 transition font-semibold shadow">
          <i class="fa-solid fa-broom mr-1"></i> Limpar
        </a>
      </div>
    </form>
  </div>

  <script>
    function validarDatas() {
      const dataInicio = document.getElementById('data_inicio').value;
      const dataFim = document.getElementById('data_fim').value;
      const erroDataInicio = document.getElementById('erro-data-inicio');
      const erroDataFim = document.getElementById('erro-data-fim');
      const btnBuscar = document.getElementById('btn-buscar-rituais');
      const inputDataInicio = document.getElementById('data_inicio');
      const inputDataFim = document.getElementById('data_fim');

      // Limpar erros anteriores
      erroDataInicio.classList.add('hidden');
      erroDataFim.classList.add('hidden');
      erroDataInicio.textContent = '';
      erroDataFim.textContent = '';
      inputDataInicio.classList.remove('border-red-500');
      inputDataFim.classList.remove('border-red-500');
      btnBuscar.disabled = false;
      btnBuscar.classList.remove('opacity-50', 'cursor-not-allowed');

      // Validar apenas se ambas as datas estiverem preenchidas
      if (dataInicio && dataFim) {
        const inicio = new Date(dataInicio);
        const fim = new Date(dataFim);

        if (fim < inicio) {
          erroDataFim.textContent = 'Data fim não pode ser menor que data início';
          erroDataFim.classList.remove('hidden');
          inputDataFim.classList.add('border-red-500');
          btnBuscar.disabled = true;
          btnBuscar.classList.add('opacity-50', 'cursor-not-allowed');
          return false;
        }
      }

      // Validar se data início está preenchida mas data fim não
      if (dataInicio && !dataFim) {
        // Permitir busca apenas com data início
        return true;
      }

      // Validar se data fim está preenchida mas data início não
      if (!dataInicio && dataFim) {
        // Permitir busca apenas com data fim
        return true;
      }

      return true;
    }

    // Validar ao carregar a página se já houver valores
    document.addEventListener('DOMContentLoaded', function () {
      validarDatas();
    });

    // Validar no submit do formulário também
    document.getElementById('filtros').addEventListener('submit', function (e) {
      if (!validarDatas()) {
        e.preventDefault();
        return false;
      }
    });
  </script>

  <!-- Lista de Rituais -->
  <div id="cards-view" class="grid gap-4 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
    <?php foreach ($rituais as $ritual): ?>
      <div class="bg-white p-4 rounded-lg shadow border border-gray-200 flex flex-col gap-3">
        <div class="flex items-center gap-4">
          <img src="<?= htmlspecialchars($ritual['foto']) ?>" alt="Foto do Ritual"
            class="w-16 h-16 rounded-lg object-cover border border-gray-300 cursor-pointer"
            onclick="openImageModal(this.src)"
            onerror="this.src='/assets/images/no-image.png'; this.onclick=null; this.classList.remove('cursor-pointer');">
          <div class="text-sm text-gray-600 space-y-1">
            <h2 class="text-lg font-bold text-gray-800"><?= htmlspecialchars($ritual['nome']) ?></h2>
            <p><span class="font-semibold">Data:</span> <?= (new DateTime($ritual['data_ritual']))->format('d/m/Y') ?></p>
            <p><span class="font-semibold">Padrinho/Madrinha:</span> <?= htmlspecialchars($ritual['padrinho_madrinha']) ?>
            </p>
            <p><span class="font-semibold">Inscritos:</span> <?= htmlspecialchars($ritual['inscritos']) ?></p>
          </div>
        </div>

        <!-- Ações -->
        <div class="flex justify-center gap-6 md:justify-end md:gap-2 mt-2 text-sm">
          <a href="<?= htmlspecialchars(listagemUrlComRetornoLista('/ritual/' . $ritual['id'], '/rituais', LISTAGEM_RITUAIS_KEYS)) ?>"
            class="bg-green-100 hover:bg-green-200 text-green-700 px-3 py-1 rounded flex items-center gap-1"
            title="Gerenciar participantes">
            <div class="flex flex-col items-center sm:flex-row sm:gap-1">
              <i class="fa-solid fa-user-group text-lg"></i>
              <span class="block sm:hidden text-xs mt-1">Participantes</span>
            </div>
          </a>
          <a href="/ritual/<?= $ritual['id'] ?>/editar?redirect=<?= rawurlencode($urlRetornoRituais) ?>"
            class="bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-1 rounded flex items-center gap-1"
            title="Editar ritual">
            <div class="flex flex-col items-center sm:flex-row sm:gap-1">
              <i class="fa-solid fa-pen-to-square text-lg"></i>
              <span class="block sm:hidden text-xs mt-1">Editar</span>
            </div>
          </a>
          <?php if ($is_admin): ?>
            <button onclick="abrirConfirmacaoExcluir('/ritual/<?= $ritual['id'] ?>/excluir')"
              class="bg-red-100 hover:bg-red-200 text-red-700 px-3 py-1 rounded flex items-center gap-1"
              title="Excluir ritual">
              <div class="flex flex-col items-center sm:flex-row sm:gap-1">
                <i class="fa-solid fa-trash text-lg"></i>
                <span class="block sm:hidden text-xs mt-1">Excluir</span>
              </div>
            </button>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (empty($rituais)): ?>
      <p class="text-center text-gray-500 col-span-full mt-4">
        Nenhum ritual encontrado.
      </p>
    <?php endif; ?>
  </div>

  <!-- Table View para Rituais (adicionar APÓS os cards) -->
  <div id="table-view" class="hidden bg-white rounded-lg shadow overflow-hidden border border-gray-200">
    <div class="overflow-x-auto">
      <table class="w-full">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Foto</th>
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
              <a href="?<?= http_build_query(array_merge($_GET, ['order_by' => 'data_ritual', 'order_dir' => $order_by === 'data_ritual' && $order_dir === 'ASC' ? 'DESC' : 'ASC'])) ?>"
                class="hover:text-[#00bfff] flex items-center gap-1">
                Data
                <?php if ($order_by === 'data_ritual'): ?>
                  <i class="fa-solid fa-sort-<?= $order_dir === 'ASC' ? 'up' : 'down' ?>"></i>
                <?php endif; ?>
              </a>
            </th>
            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Padrinho/Madrinha</th>
            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">
              <a href="?<?= http_build_query(array_merge($_GET, ['order_by' => 'inscritos', 'order_dir' => $order_by === 'inscritos' && $order_dir === 'ASC' ? 'DESC' : 'ASC'])) ?>"
                class="hover:text-[#00bfff] flex items-center gap-1">
                Inscritos
                <?php if ($order_by === 'inscritos'): ?>
                  <i class="fa-solid fa-sort-<?= $order_dir === 'ASC' ? 'up' : 'down' ?>"></i>
                <?php endif; ?>
              </a>
            </th>
            <th class="px-4 py-3 text-center text-sm font-medium text-gray-700">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php if (empty($rituais)): ?>
            <tr>
              <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                <i class="fa-solid fa-calendar text-3xl mb-2"></i>
                <p>Nenhum ritual encontrado</p>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($rituais as $ritual): ?>
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                  <img src="<?= htmlspecialchars($ritual['foto']) ?>" alt="Foto do Ritual"
                    class="w-12 h-12 rounded-lg object-cover border border-gray-300 cursor-pointer"
                    onclick="openImageModal(this.src)"
                    onerror="this.src='/assets/images/no-image.png'; this.onclick=null; this.classList.remove('cursor-pointer');">
                </td>
                <td class="px-4 py-3 text-sm font-medium text-gray-900">
                  <?= htmlspecialchars($ritual['nome']) ?>
                </td>
                <td class="px-4 py-3 text-sm text-gray-600">
                  <?= (new DateTime($ritual['data_ritual']))->format('d/m/Y') ?>
                </td>
                <td class="px-4 py-3 text-sm text-gray-600">
                  <?= htmlspecialchars($ritual['padrinho_madrinha']) ?>
                </td>
                <td class="px-4 py-3 text-sm text-gray-600">
                  <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                    <?= htmlspecialchars($ritual['inscritos']) ?>
                  </span>
                </td>
                <td class="px-4 py-3 text-center">
                  <div class="flex justify-center gap-2">
                    <a href="<?= htmlspecialchars(listagemUrlComRetornoLista('/ritual/' . $ritual['id'], '/rituais', LISTAGEM_RITUAIS_KEYS)) ?>"
                      class="bg-green-100 hover:bg-green-200 text-green-700 px-3 py-1 rounded flex items-center gap-1"
                      title="Gerenciar participantes">
                      <i class="fa-solid fa-user-group"></i>
                    </a>
                    <a href="/ritual/<?= $ritual['id'] ?>/editar?redirect=<?= rawurlencode($urlRetornoRituais) ?>"
                      class="bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-1 rounded flex items-center gap-1"
                      title="Editar ritual">
                      <i class="fa-solid fa-pen-to-square"></i>
                    </a>
                    <?php if ($is_admin): ?>
                      <button onclick="abrirConfirmacaoExcluir('/ritual/<?= $ritual['id'] ?>/excluir')"
                        class="bg-red-100 hover:bg-red-200 text-red-700 px-3 py-1 rounded flex items-center gap-1"
                        title="Excluir ritual">
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
    <?php if (!empty($rituais)): ?>
      <p>
        Mostrando <?= count($rituais) ?> de <?= $total_registros ?> rituais(s)
        <?php if (!empty($filtros)): ?>
          (filtrado)
        <?php endif; ?>
      </p>
    <?php endif; ?>
  </div>

  <?php
  $paginacao_atual = $pagina;
  $paginacao_total = $total_paginas;
  $paginacao_params = $_GET;
  unset($paginacao_params['pagina']);
  require __DIR__ . '/../../includes/paginacao.php';
  ?>
</div>

<!-- Botão Voltar ao Topo -->
<button id="scroll-to-top"
  class="fixed bottom-12 right-4 bg-[#00bfff] md:hover:bg-yellow-400 text-black p-3 rounded-full shadow-lg transform transition-all duration-300 ease-in-out opacity-0 invisible translate-y-4 z-50">
  <i class="fa-solid fa-chevron-up md:text-lg"></i>
</button>

<!-- Modal de Imagem -->
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

<?= asset_script('/assets/js/rituais.js') ?>
<?= asset_script('/assets/js/relatorios.js') ?>
<?= asset_script('/assets/js/modal.js') ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>