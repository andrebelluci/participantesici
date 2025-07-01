/**
 * Sistema de Relatórios
 * Gerencia a exportação de relatórios em PDF e Excel
 */

// Função para mostrar/esconder o dropdown de exportação
function toggleExportDropdown(event) {
  event.stopPropagation();
  const dropdown = document.getElementById('export-dropdown');
  dropdown.classList.toggle('hidden');
}

// Função para exportar participante
function exportarParticipante(id, formato) {
  if (!id) {
    alert('ID do participante não encontrado');
    return;
  }

  // Mostrar loading
  showExportLoading();

  // Construir URL de exportação
  const url = `/participantesici/app/participantes/relatorios/visualizar.php?id=${id}&formato=${formato}`;

  // Criar link temporário para download
  const link = document.createElement('a');
  link.href = url;
  link.download = '';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);

  // Esconder dropdown e loading
  hideExportDropdown();
  setTimeout(hideExportLoading, 2000);
}

// Função para exportar ritual
function exportarRitual(id, formato) {
  if (!id) {
    alert('ID do ritual não encontrado');
    return;
  }

  // Mostrar loading
  showExportLoading();

  // Construir URL de exportação
  const url = `/participantesici/app/rituais/relatorios/visualizar.php?id=${id}&formato=${formato}`;

  // Criar link temporário para download
  const link = document.createElement('a');
  link.href = url;
  link.download = '';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);

  // Esconder dropdown e loading
  hideExportDropdown();
  setTimeout(hideExportLoading, 2000);
}

// Função para esconder dropdown
function hideExportDropdown() {
  const dropdown = document.getElementById('export-dropdown');
  if (dropdown) {
    dropdown.classList.add('hidden');
  }
}

// Função para mostrar loading
function showExportLoading() {
  const button = document.getElementById('export-button');
  const originalContent = button.innerHTML;

  button.innerHTML = `
      <i class="fa-solid fa-spinner fa-spin mr-2"></i>
  `;
  button.disabled = true;
  button.dataset.originalContent = originalContent;
}

// Função para esconder loading
function hideExportLoading() {
  const button = document.getElementById('export-button');
  if (button && button.dataset.originalContent) {
    button.innerHTML = button.dataset.originalContent;
    button.disabled = false;
    delete button.dataset.originalContent;
  }
}

// Event listener para fechar dropdown ao clicar fora
document.addEventListener('click', function (event) {
  const dropdown = document.getElementById('export-dropdown');
  const button = document.getElementById('export-button');

  if (dropdown && button && !button.contains(event.target) && !dropdown.contains(event.target)) {
    hideExportDropdown();
  }
});

// Event listener para tecla ESC
document.addEventListener('keydown', function (event) {
  if (event.key === 'Escape') {
    hideExportDropdown();
  }
});

// Função para inicializar os botões de exportação
function initializeExportButtons() {
  // Verificar se estamos em uma página de visualização
  const currentPath = window.location.pathname;

  if (currentPath.includes('/participante/') && /\/participante\/\d+\/?$/.test(currentPath)) {
    // Página de visualizar participante
    const participanteId = extractIdFromPath(currentPath, 'participante');
    setupExportButton('participante', participanteId);
  } else if (currentPath.includes('/ritual/') && /\/ritual\/\d+\/?$/.test(currentPath)) {
    // Página de visualizar ritual
    const ritualId = extractIdFromPath(currentPath, 'ritual');
    setupExportButton('ritual', ritualId);
  }
}

// Função para extrair ID da URL
function extractIdFromPath(path, type) {
  const regex = new RegExp(`/${type}/(\\d+)`);
  const match = path.match(regex);
  return match ? match[1] : null;
}

// Função para configurar o botão de exportação
function setupExportButton(type, id) {
  const viewToggle = document.getElementById('view-toggle');
  if (!viewToggle || !id) return;

  // Criar o botão de exportação
  const exportButton = createExportButton(type, id);

  // Inserir o botão antes do view-toggle
  viewToggle.parentNode.insertBefore(exportButton, viewToggle);
}

// Função para criar o botão de exportação
function createExportButton(type, id) {
  const container = document.createElement('div');
  container.className = 'relative inline-block mr-2';

  container.innerHTML = `
  <?php if ($is_admin): ?>
      <button type="button"
              id="export-button"
              onclick="toggleExportDropdown(event)"
              class="hidden md:block flex items-center justify-center bg-orange-100 text-orange-700 w-10 h-10 rounded hover:bg-orange-200 transition border border-orange-300"
              title="Exportar relatório">
          <i class="fa-solid fa-file-export text-lg"></i>
      </button>
      <?php endif; ?>

      <div id="export-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
          <div class="py-2">
              <div class="px-4 py-2 text-sm font-medium text-gray-700 border-b border-gray-100">
                  Exportar como:
              </div>
              <button onclick="${type === 'participante' ? 'exportarParticipante' : 'exportarRitual'}(${id}, 'pdf')"
                      class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                  <i class="fa-solid fa-file-pdf text-red-500"></i>
                  PDF
              </button>
              <button onclick="${type === 'participante' ? 'exportarParticipante' : 'exportarRitual'}(${id}, 'excel')"
                      class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                  <i class="fa-solid fa-file-excel text-green-500"></i>
                  Excel
              </button>
          </div>
      </div>
  `;

  return container;
}

// Inicializar quando a página carregar
document.addEventListener('DOMContentLoaded', function () {
  initializeExportButtons();
});