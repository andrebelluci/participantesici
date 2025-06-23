<?php
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../includes/header.php';

if (!isset($pessoa)) {
  die("Pessoa não encontrado.");
}
?>

<div class="max-w-screen-xl mx-auto px-4 py-6">
  <!-- Cabeçalho com foto, nome, CPF e data de nascimento -->
  <div class="bg-white p-6 rounded-lg shadow border border-gray-200 mb-6">
    <div class="flex flex-col md:flex-row items-center md:items-start gap-4 mb-4">
      <img src="<?= htmlspecialchars($pessoa['foto']) ?>" alt="Foto do Participante"
        class="w-24 h-24 md:w-32 md:h-32 rounded-lg object-cover border border-gray-300 cursor-pointer"
        onclick="openImageModal(this.src)"
        onerror="this.src='/participantesici/public_html/assets/images/no-image.png'; this.onclick=null; this.classList.remove('cursor-pointer');">

      <div class="text-center md:text-left flex-1">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">
          <?= htmlspecialchars($pessoa['nome_completo']) ?>
        </h1>
        <div class="text-sm text-gray-600 space-y-1">
          <p><span class="font-semibold">CPF:</span> <?= formatarCPF(htmlspecialchars($pessoa['cpf'])) ?></p>
          <p><span class="font-semibold">Data de Nascimento:</span>
            <?php
            $nascimento = new DateTime($pessoa['nascimento']);
            echo $nascimento->format('d/m/Y');
            ?>
          </p>
          <!-- <p><span class="font-semibold">Inscrições:</span>
            <span class="bg-yellow-300 text-blue-900 px-2 py-1 rounded md:text-lg font-medium">
              <?= $total_rituais_participados + $total_rituais_nao_participados ?>
                      </span>
                    </p> -->
          <p><span class="font-semibold">Rituais participados:</span>
            <span class="bg-[#00bfff] text-black px-2 py-1 rounded md:text-lg font-medium">
              <?= $total_rituais_participados ?>
            </span>
          </p>
          <!-- <p><span class="font-semibold">Rituais não participados:</span>
                      <span class="bg-red-300 text-black px-2 py-1 rounded md:text-lg font-medium">
                        <?= $total_rituais_nao_participados ?>
                      </span>
                    </p> -->
        </div>
      </div>

      <button onclick="abrirModalCadastro()"
        class="bg-blue-100 hover:bg-blue-200 text-blue-700 px-4 py-2 rounded-lg flex items-center gap-2 transition">
        <i class="fa-solid fa-eye"></i>
        <span>Ver cadastro</span>
      </button>
    </div>

    <!-- Botões de ação -->
    <div class="flex items-center justify-between mb-6">
      <a href="/participantesici/public_html/participantes"
        class="flex items-center text-gray-600 hover:text-[#00bfff] transition text-sm">
        <i class="fa-solid fa-arrow-left mr-2"></i> Voltar
      </a>

      <button onclick="document.getElementById('modal-adicionar').style.display='flex'"
        class="bg-[#00bfff] text-black px-2 md:px-6 py-2 rounded hover:bg-yellow-400 transition font-semibold shadow">
        <i class="fa-solid fa-plus mr-2"></i> Adicionar ritual
      </button>
    </div>
  </div>

  <!-- Filtros (mesmo padrão do listar.php) -->
  <div class="md:hidden flex items-center justify-between">
    <button type="button" onclick="document.getElementById('filtros').classList.toggle('hidden')"
      class="bg-gray-200 text-gray-700 px-4 py-2 rounded mb-4 flex items-center gap-2 text-sm shadow hover:bg-gray-300">
      <i class="fa-solid fa-search"></i> Filtrar
    </button>
    <a href="/participantesici/public_html/participante/<?= $id ?>"
      class="<?= empty($filtro_nome) ? 'hidden' : '' ?> bg-red-600 text-white px-4 py-2 rounded mb-4 flex items-center gap-2 text-sm shadow hover:bg-red-300 transition">
      <i class="fa-solid fa-broom mr-1"></i> Limpar Filtro
    </a>
  </div>

  <form id="filtros" method="GET"
    class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-white p-3 rounded-lg shadow border border-gray-200 mb-6 <?= empty($filtro_nome) ? 'hidden md:grid' : '' ?>">
    <input type="hidden" name="id" value="<?= $id ?>">

    <div class="md:col-span-2">
      <label for="filtro_nome" class="block text-sm font-medium text-gray-700 mb-1">Nome do Ritual:</label>
      <input type="text" name="filtro_nome" id="filtro_nome" placeholder="Filtrar por nome do ritual"
        value="<?= htmlspecialchars($_GET['filtro_nome'] ?? '') ?>"
        class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#00bfff]">
    </div>

    <div class="flex items-end gap-2 md:col-span-2">
      <button type="submit"
        class="bg-[#00bfff] text-black px-4 py-2 rounded hover:bg-yellow-400 transition font-semibold shadow">
        <i class="fa-solid fa-search mr-1"></i> Buscar
      </button>
      <a href="/participantesici/public_html/participante/<?= $id ?>"
        class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 transition font-semibold shadow">
        <i class="fa-solid fa-broom mr-1"></i> Limpar
      </a>
    </div>
  </form>

  <!-- Título da seção -->
  <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
    🪵 Rituais do Participante
  </h2>

  <!-- Cards dos Rituais -->
  <div class="grid gap-4 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
    <?php foreach ($rituais as $ritual): ?>
      <div class="bg-white p-4 rounded-lg shadow border border-gray-200 flex flex-col gap-3">
        <!-- Header do card com foto e info básica -->
        <div class="flex items-start gap-4">
          <img src="<?= htmlspecialchars($ritual['foto']) ?>" alt="Foto do Ritual"
            class="w-16 h-16 rounded-lg object-cover border border-gray-300 cursor-pointer"
            onclick="openImageModal('<?= htmlspecialchars($ritual['foto']) ?>')"
            onerror="this.src='/participantesici/public_html/assets/images/no-image.png'; this.onclick=null; this.classList.remove('cursor-pointer');">

          <div class="flex-1 min-w-0">
            <h3 class="font-bold text-gray-800 text-lg mb-1 truncate">
              <?= htmlspecialchars($ritual['nome']) ?>
            </h3>
            <div class="text-sm text-gray-600 space-y-1">
              <p><span class="font-semibold">Data:</span>
                <?php
                $data_ritual = new DateTime($ritual['data_ritual']);
                echo $data_ritual->format('d/m/Y');
                ?>
              </p>
              <div class="flex items-center gap-2">
                <span class="font-semibold">Presente:</span>
                <button
                  class="presence-btn <?= $ritual['presente'] === 'Sim' ? 'active bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-700 hover:bg-red-200' ?> px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 min-w-[80px] flex items-center justify-center gap-2"
                  data-ritual-id="<?= $ritual['id'] ?>" data-current-status="<?= $ritual['presente'] ?>"
                  onclick="togglePresenca(this)">

                  <?php if ($ritual['presente'] === 'Sim'): ?>
                    <i class="fa-solid fa-check"></i>
                    <span>Sim</span>
                  <?php else: ?>
                    <i class="fa-solid fa-xmark"></i>
                    <span>Não</span>
                  <?php endif; ?>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ✅ SEÇÃO DE OBSERVAÇÃO NO CARD - Com 2 linhas de preview -->
        <div class="border-t pt-3">
          <p class="text-sm text-gray-600 mb-2 font-semibold">Observação:</p>

          <?php if (!empty($ritual['observacao'])): ?>
            <!-- Tem observação: mostra preview de 2 linhas + tag -->
            <div class="space-y-2">
              <!-- Preview da observação (2 linhas) -->
              <div class="text-sm text-gray-700 line-clamp-2 leading-relaxed">
                <?= htmlspecialchars($ritual['observacao']) ?>
              </div>

              <!-- Tag para abrir modal -->
              <div class="flex items-center justify-between">
                <button onclick="abrirModalObservacao(<?= $ritual['id'] ?>)"
                  class="inline-flex items-center gap-1 px-3 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-lg text-xs font-medium transition-colors">
                  <i class="fa-solid fa-eye text-xs"></i>
                  Ver observação completa
                </button>
                <span class="text-xs text-gray-500">
                  (<?= mb_strlen($ritual['observacao']) ?> caracteres)
                </span>
              </div>
            </div>
          <?php else: ?>
            <!-- Não tem observação: mostra mensagem e botão para adicionar -->
            <div class="flex items-center justify-between">
              <p class="text-sm text-gray-400 italic">Nenhuma observação registrada</p>
              <button onclick="abrirModalObservacao(<?= $ritual['id'] ?>)"
                class="inline-flex items-center gap-1 px-3 py-1 bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-lg text-xs font-medium transition-colors">
                <i class="fa-solid fa-plus text-xs"></i>
                Adicionar observação
              </button>
            </div>
          <?php endif; ?>
        </div>

        <!-- ✅ AÇÕES DO CARD COM NOTIFICAÇÕES -->
        <div class="flex justify-center gap-6 md:justify-end md:gap-2 mt-2 text-sm border-t pt-3">
          <!-- Botão Observação -->
          <button onclick="abrirModalObservacao(<?= $ritual['id'] ?>)"
            class="relative bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-1 rounded flex items-center gap-1"
            title="Observação do participante neste ritual">
            <div class="flex flex-col items-center sm:flex-row sm:gap-1">
              <i class="fa-solid fa-file-lines text-lg"></i>
              <span class="block sm:hidden text-xs mt-1">Observação</span>
            </div>

            <!-- ✅ BOLINHA VERMELHA se não tem observação -->
            <?php if (empty($ritual['observacao'])): ?>
              <span class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full border border-white"></span>
            <?php endif; ?>
          </button>

          <!-- Botão Detalhes -->
          <button onclick="abrirModalDetalhes(<?= $ritual['id'] ?>)"
            class="relative bg-green-100 hover:bg-green-200 text-green-700 px-3 py-1 rounded flex items-center gap-1"
            title="Detalhes da inscrição no ritual">
            <div class="flex flex-col items-center sm:flex-row sm:gap-1">
              <i class="fa-solid fa-pencil text-lg"></i>
              <span class="block sm:hidden text-xs mt-1">Detalhes</span>
            </div>

            <!-- ✅ BOLINHA VERMELHA se não tem detalhes preenchidos -->
            <?php
            $temDetalhes = !empty($ritual['primeira_vez_instituto']) ||
              !empty($ritual['primeira_vez_ayahuasca']) ||
              !empty($ritual['doenca_psiquiatrica']);
            ?>
            <?php if (!$temDetalhes): ?>
              <span class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full border border-white"></span>
            <?php endif; ?>
          </button>

          <!-- ✅ BOTÃO DESVINCULAR (novo ícone) -->
          <button
            onclick="if(confirm('Tem certeza que deseja desvincular este ritual do participante?')) { window.location.href='participante-excluir-ritual.php?id=<?= $ritual['id'] ?>' }"
            class="bg-orange-100 hover:bg-orange-200 text-orange-700 px-3 py-1 rounded flex items-center gap-1"
            title="Desvincular participante do ritual">
            <div class="flex flex-col items-center sm:flex-row sm:gap-1">
              <i class="fa-solid fa-link-slash text-lg"></i>
              <span class="block sm:hidden text-xs mt-1">Desvincular</span>
            </div>
          </button>
        </div>
      </div>
    <?php endforeach; ?>

    <!-- Mensagem quando não há rituais -->
    <?php if (empty($rituais)): ?>
      <div class="col-span-full text-center py-8">
        <div class="text-gray-400 mb-4">
          <i class="fa-solid fa-calendar-times text-4xl"></i>
        </div>
        <p class="text-gray-500 text-lg mb-2">Nenhum ritual encontrado</p>
        <p class="text-gray-400 text-sm">Este participante ainda não está inscrito em nenhum ritual.</p>
        <button onclick="document.getElementById('modal-adicionar').style.display='flex'"
          class="mt-4 bg-[#00bfff] text-black px-6 py-2 rounded hover:bg-yellow-400 transition font-semibold shadow">
          <i class="fa-solid fa-plus mr-2"></i> Adicionar primeiro ritual
        </button>
      </div>
    <?php endif; ?>
  </div>

  <!-- Paginação (mesmo padrão do listar.php) -->
  <div class="flex justify-center mt-6 flex-wrap gap-2">
    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
      <a href="?pagina=<?= $i ?>&id=<?= $id ?>&filtro_nome=<?= htmlspecialchars($filtro_nome) ?>&order_by=<?= $order_by ?>&order_dir=<?= $order_dir ?>"
        class="px-4 py-2 rounded border transition
           <?= $pagina == $i ? 'bg-[#00bfff] text-black font-semibold shadow' : 'bg-white text-gray-600 hover:bg-gray-100' ?>">
        <?= $i ?>
      </a>
    <?php endfor; ?>
  </div>
</div>

<!-- Botão Voltar ao Topo -->
<button id="scroll-to-top"
  class="fixed bottom-12 right-4 bg-[#00bfff] hover:bg-yellow-400 text-black p-3 rounded-full shadow-lg transform transition-all duration-300 ease-in-out opacity-0 invisible translate-y-4 z-50">
  <i class="fa-solid fa-chevron-up text-lg"></i>
</button>

<!-- Modal Adicionar Ritual - Com Filtro Colapsável -->
<div id="modal-adicionar" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative mx-4 max-h-[90vh] overflow-y-auto">
    <button onclick="fecharModalAdicionar()"
      class="absolute top-2 right-2 text-red-600 hover:text-red-800 text-lg z-10">
      <i class="fa-solid fa-window-close"></i>
    </button>

    <h2 class="text-xl font-bold mb-4 text-gray-800">Adicionar ritual</h2>

    <!-- Área do filtro - inicialmente aberta -->
    <div id="area-filtro-ritual" class="mb-4">
      <!-- Botão toggle para mostrar/esconder filtro (inicialmente escondido) -->
      <div id="botao-toggle-filtro" class="hidden mb-4 flex items-center justify-between">
        <button type="button" onclick="toggleFiltroRitual()"
          class="bg-gray-200 text-gray-700 px-4 py-2 rounded flex items-center gap-2 text-sm shadow hover:bg-gray-300 transition">
          <i class="fa-solid fa-search"></i> Filtrar
        </button>
        <button type="button" id="limpar-filtro-btn" onclick="limparFiltroCompleto()" style="display: none;"
          class="bg-red-600 text-white px-4 py-2 rounded flex items-center gap-2 text-sm shadow hover:bg-red-700 transition">
          <i class="fa-solid fa-broom mr-1"></i> Limpar Filtro
        </button>
      </div>

      <!-- Formulário de pesquisa - inicialmente visível -->
      <form id="pesquisa-ritual-form" onsubmit="return false;" class="space-y-4">
        <input type="hidden" name="participante_id" value="<?= $id ?>">

        <div>
          <input type="text" id="nome_pesquisa" name="nome_pesquisa" placeholder="Digite o nome do ritual"
            class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>

        <div class="flex gap-2">
          <button type="button" id="pesquisar-btn" onclick="pesquisarRituaisComColapso()"
            class="bg-[#00bfff] text-black px-4 py-2 rounded hover:bg-yellow-400 transition font-semibold">
            Pesquisar
          </button>
          <button type="button" id="limpar-pesquisa-btn" onclick="limparPesquisa()" style="display: none;"
            class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 transition font-semibold">
            Limpar
          </button>
        </div>
      </form>
    </div>

    <!-- Área de resultados -->
    <div id="resultados-pesquisa" class="mt-4" style="display: none;">
      <h3 class="text-lg font-semibold text-gray-800 mb-3">Resultados</h3>
      <div class="max-h-60 overflow-y-auto border border-gray-200 rounded">
        <ul id="lista-rituais" class="divide-y divide-gray-200"></ul>
      </div>

      <!-- Seção "Não encontrou o ritual?" -->
      <div class="mt-4 pt-4 border-t border-gray-200 text-center">
        <h4 class="text-md font-medium text-gray-700 mb-3">Não encontrou o ritual?</h4>
        <button onclick="adicionarNovoRitual()"
          class="w-full bg-green-600 text-white py-2 px-4 rounded hover:bg-green-700 transition font-semibold flex items-center justify-center gap-2">
          <i class="fa-solid fa-plus"></i>
          Adicionar novo ritual
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Detalhes da Inscrição - Tailwind -->
<div id="modal-detalhes-inscricao"
  class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6 relative mx-4 max-h-[90vh] overflow-y-auto">
    <button onclick="fecharModalDetalhes()" class="absolute top-2 right-2 text-red-600 hover:text-red-800 text-lg z-10">
      <i class="fa-solid fa-window-close"></i>
    </button>

    <h2 class="text-xl font-bold mb-4 text-gray-800">Detalhes da inscrição</h2>

    <form id="form-detalhes-inscricao" method="POST" class="space-y-4">
      <input type="hidden" id="id" name="id" value="">

      <div>
        <label for="primeira_vez_instituto" class="block text-sm font-medium text-gray-700 mb-1">Primeira vez no
          Instituto?</label>
        <select name="primeira_vez_instituto" required
          class="w-full border border-gray-300 rounded px-3 py-2 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
          <option value="">Selecione...</option>
          <option value="Sim">Sim</option>
          <option value="Não">Não</option>
        </select>
      </div>

      <div>
        <label for="primeira_vez_ayahuasca" class="block text-sm font-medium text-gray-700 mb-1">Primeira vez
          consagrando Ayahuasca?</label>
        <select name="primeira_vez_ayahuasca" required
          class="w-full border border-gray-300 rounded px-3 py-2 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
          <option value="">Selecione...</option>
          <option value="Sim">Sim</option>
          <option value="Não">Não</option>
        </select>
      </div>

      <div>
        <label for="doenca_psiquiatrica" class="block text-sm font-medium text-gray-700 mb-1">Possui doença psiquiátrica
          diagnosticada?</label>
        <select name="doenca_psiquiatrica" id="doenca_psiquiatrica" required
          class="w-full border border-gray-300 rounded px-3 py-2 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
          <option value="">Selecione...</option>
          <option value="Sim">Sim</option>
          <option value="Não">Não</option>
        </select>
      </div>

      <div>
        <label for="nome_doenca" class="block text-sm font-medium text-gray-700 mb-1">Se sim, escreva o nome da
          doença:</label>
        <input type="text" name="nome_doenca" id="nome_doenca" value="" disabled
          class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100">
      </div>

      <div>
        <label for="uso_medicao" class="block text-sm font-medium text-gray-700 mb-1">Faz uso de alguma
          medicação?</label>
        <select name="uso_medicao" id="uso_medicao" required
          class="w-full border border-gray-300 rounded px-3 py-2 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
          <option value="">Selecione...</option>
          <option value="Sim">Sim</option>
          <option value="Não">Não</option>
        </select>
      </div>

      <div>
        <label for="nome_medicao" class="block text-sm font-medium text-gray-700 mb-1">Se sim, escreva o nome da
          medicação:</label>
        <input type="text" name="nome_medicao" id="nome_medicao" value="" disabled
          class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100">
      </div>

      <div>
        <label for="mensagem" class="block text-sm font-medium text-gray-700 mb-1">Mensagem do participante:</label>
        <textarea name="mensagem" rows="3"
          class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
      </div>

      <div>
        <label for="salvo_em" class="block text-sm font-medium text-gray-700 mb-1">Salvo em:</label>
        <input type="text" id="salvo_em" name="salvo_em" readonly value=""
          class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-100">
      </div>

      <button type="submit"
        class="w-full bg-[#00bfff] text-black py-2 rounded hover:bg-yellow-400 transition font-semibold">
        Salvar
      </button>
    </form>
  </div>
</div>

<!-- ✅ MODAL OBSERVAÇÃO MELHORADA -->
<div id="modal-observacao" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative mx-4">
    <button onclick="fecharModalObservacao()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700">
      <i class="fas fa-times text-xl"></i>
    </button>

    <!-- ✅ TÍTULO DINÂMICO -->
    <h2 class="text-xl font-bold mb-4 text-gray-800">Adicionar observação</h2>

    <form id="form-observacao" method="POST" class="space-y-4">
      <input type="hidden" id="inscricao_id_observacao" name="inscricao_id" value="">

      <div>
        <label for="observacao" class="block text-sm font-medium text-gray-700 mb-1">Observação:</label>
        <textarea name="observacao" required rows="6" placeholder="Digite sua observação sobre este ritual..."
          class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"></textarea>
        <p class="text-xs text-gray-500 mt-1">
          Descreva qualquer informação relevante sobre a participação neste ritual
        </p>
      </div>

      <div>
        <label for="obs_salvo_em" class="block text-sm font-medium text-gray-700 mb-1">Salvo em:</label>
        <input type="text" id="obs_salvo_em" name="obs_salvo_em" readonly value=""
          class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-100 text-sm">
      </div>

      <!-- ✅ BOTÃO COM ÍCONE DINÂMICO -->
      <button type="submit"
        class="w-full bg-[#00bfff] text-black py-2 rounded hover:bg-yellow-400 transition font-semibold flex items-center justify-center gap-2">
        <i class="fa-solid fa-plus"></i>
        Salvar observação
      </button>
    </form>
  </div>
</div>

<!-- Modal de Visualização de Imagem - Tailwind -->
<div id="modal-image" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-lg overflow-hidden shadow-lg relative max-w-sm w-full mx-4">
    <button onclick="closeImageModal()" class="absolute top-2 right-2 text-red-600 hover:text-red-800 text-lg z-10">
      <i class="fa-solid fa-window-close"></i>
    </button>
    <img id="modal-image-content" class="w-full h-auto object-contain max-h-[80vh]" alt="Imagem Ampliada">
  </div>
</div>

<!-- Modal Ver Cadastro - Tailwind -->
<div id="modal-cadastro" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl p-6 relative mx-4 max-h-[90vh] overflow-y-auto">
    <button onclick="fecharModalCadastro()" class="absolute top-2 right-2 text-red-600 hover:text-red-800 text-lg z-10">
      <i class="fa-solid fa-window-close"></i>
    </button>

    <h2 class="text-xl font-bold mb-4 text-gray-800">
      <?= htmlspecialchars($pessoa['nome_completo']) ?>
    </h2>

    <div class="space-y-6">
      <!-- Dados Pessoais -->
      <div>
        <h3 class="text-lg font-semibold text-gray-700 mb-3 flex items-center gap-2">
          <i class="fa-solid fa-user text-blue-600"></i> Dados Pessoais
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
          <div><strong>Nome Completo:</strong> <?= htmlspecialchars($pessoa['nome_completo']) ?></div>
          <div><strong>CPF:</strong> <?= formatarCPF(htmlspecialchars($pessoa['cpf'])) ?></div>
          <div><strong>Data de Nascimento:</strong>
            <?php
            $nascimento = new DateTime($pessoa['nascimento']);
            echo $nascimento->format('d/m/Y');
            ?>
          </div>
          <div><strong>Sexo:</strong> <?= htmlspecialchars($pessoa['sexo'] === 'M' ? 'Masculino' : 'Feminino') ?></div>
          <div><strong>RG:</strong> <?= htmlspecialchars($pessoa['rg']) ?></div>
          <div><strong>Passaporte:</strong> <?= htmlspecialchars($pessoa['passaporte']) ?></div>
          <div><strong>Celular:</strong> <?= formatarCelular(htmlspecialchars($pessoa['celular'])) ?></div>
          <div><strong>E-mail:</strong> <?= htmlspecialchars($pessoa['email']) ?></div>
        </div>
      </div>

      <!-- Endereço -->
      <div>
        <h3 class="text-lg font-semibold text-gray-700 mb-3 flex items-center gap-2">
          <i class="fa-solid fa-map-marker-alt text-green-600"></i> Endereço
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
          <div><strong>CEP:</strong> <?= formatarCEP(htmlspecialchars($pessoa['cep'])) ?></div>
          <div><strong>Rua:</strong> <?= htmlspecialchars($pessoa['endereco_rua']) ?></div>
          <div><strong>Número:</strong> <?= htmlspecialchars($pessoa['endereco_numero']) ?></div>
          <div><strong>Complemento:</strong> <?= htmlspecialchars($pessoa['endereco_complemento']) ?></div>
          <div><strong>Bairro:</strong> <?= htmlspecialchars($pessoa['bairro']) ?></div>
          <div><strong>Cidade:</strong> <?= htmlspecialchars($pessoa['cidade']) ?></div>
          <div class="md:col-span-2"><strong>Estado:</strong> <?= htmlspecialchars($pessoa['estado']) ?></div>
        </div>
      </div>

      <!-- Informações Adicionais -->
      <div>
        <h3 class="text-lg font-semibold text-gray-700 mb-3 flex items-center gap-2">
          <i class="fa-solid fa-info-circle text-purple-600"></i> Informações Adicionais
        </h3>
        <div class="space-y-3 text-sm">
          <div><strong>Como soube do Instituto:</strong> <?= htmlspecialchars($pessoa['como_soube']) ?></div>
          <div><strong>Sobre o Participante:</strong>
            <p class="mt-1 text-gray-600"><?= htmlspecialchars($pessoa['sobre_participante']) ?></p>
          </div>
        </div>
      </div>

      <div class="pt-4 border-t">
        <a href="/participantesici/public_html/participante/<?= $pessoa['id'] ?>/editar?redirect=/participantesici/public_html/participante/<?= $pessoa['id'] ?>"
          class="inline-flex items-center gap-2 bg-blue-100 hover:bg-blue-200 text-blue-700 text-md px-4 py-2 rounded transition">
          <i class="fa-solid fa-pen-to-square"></i>
          Editar dados do participante
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Modal de Confirmação Genérico -->
<div id="confirmModal" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative mx-4">
    <h2 class="text-xl font-bold mb-4 text-red-600" id="confirmModalTitle">ATENÇÃO!</h2>
    <p class="text-gray-700 mb-6" id="confirmModalText">Tem certeza que deseja remover este ritual do participante?</p>
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

<script>
  // Função para expandir/contrair observação
  function toggleObservacao(button) {
    const textoTruncado = button.parentNode.querySelector('.observacao-texto');
    const textoCompleto = button.parentNode.querySelector('.observacao-completa');

    if (textoCompleto.classList.contains('hidden')) {
      // Expandir
      textoTruncado.classList.add('hidden');
      textoCompleto.classList.remove('hidden');
      button.textContent = 'Ver menos';
    } else {
      // Contrair
      textoTruncado.classList.remove('hidden');
      textoCompleto.classList.add('hidden');
      button.textContent = 'Ver mais';
    }
  }

  const pessoaId = <?= json_encode($id) ?>;
</script>

<style>
  .line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
</style>

<script src="/participantesici/public_html/assets/js/participante-visualizar.js"></script>
<script src="/participantesici/public_html/assets/js/modal.js"></script>



<?php require_once __DIR__ . '/../../includes/footer.php'; ?>