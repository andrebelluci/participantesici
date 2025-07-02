let deferredPrompt;
let isInstalled = false;

// Verifica se já está instalado
window.addEventListener('appinstalled', () => {
  console.log('PWA foi instalado com sucesso!');
  isInstalled = true;
  hideInstallButton();
});

// Captura o evento de instalação
window.addEventListener('beforeinstallprompt', (e) => {
  console.log('PWA pode ser instalado');
  // Previne o prompt automático
  e.preventDefault();
  // Salva o evento para usar depois
  deferredPrompt = e;
  // Mostra botão de instalação customizado
  showInstallButton();
});

// Função para mostrar botão de instalação
function showInstallButton() {
  // Cria botão de instalação se não existir
  if (!document.getElementById('install-button')) {
    const installButton = document.createElement('button');
    installButton.id = 'install-button';
    installButton.innerHTML = `
      <i class="fa-solid fa-download mr-2"></i>
      Instalar App
    `;
    installButton.className = 'fixed bottom-4 right-4 bg-[#00bfff] hover:bg-yellow-400 text-black px-4 py-2 rounded-lg shadow-lg font-semibold z-50 transition-all duration-300 transform hover:scale-105';

    installButton.addEventListener('click', installPWA);
    document.body.appendChild(installButton);

    // Animação de entrada
    setTimeout(() => {
      installButton.style.transform = 'translateY(0)';
    }, 100);
  }
}

// Função para instalar PWA
async function installPWA() {
  if (!deferredPrompt) {
    console.log('Prompt de instalação não disponível');
    return;
  }

  // Mostra o prompt de instalação
  deferredPrompt.prompt();

  // Aguarda a escolha do usuário
  const { outcome } = await deferredPrompt.userChoice;
  console.log(`Usuário ${outcome} a instalação`);

  if (outcome === 'accepted') {
    hideInstallButton();
  }

  // Limpa a referência
  deferredPrompt = null;
}

// Função para esconder botão de instalação
function hideInstallButton() {
  const installButton = document.getElementById('install-button');
  if (installButton) {
    installButton.style.transform = 'translateY(100px)';
    setTimeout(() => {
      installButton.remove();
    }, 300);
  }
}

// Service Worker Registration
if ('serviceWorker' in navigator) {
  window.addEventListener('load', async () => {
    try {
      // Remove service workers antigos
      const registrations = await navigator.serviceWorker.getRegistrations();
      for (let registration of registrations) {
        await registration.unregister();
      }

      // Registra novo service worker
      const registration = await navigator.serviceWorker.register(
        '/participantesici/public_html/service-worker.js?v=' + Date.now(),
        { scope: '/participantesici/public_html/' }
      );

      console.log('Service Worker registrado:', registration.scope);

      // Verifica se há atualizações
      registration.addEventListener('updatefound', () => {
        const newWorker = registration.installing;
        newWorker.addEventListener('statechange', () => {
          if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
            // Nova versão disponível
            showUpdateAvailable();
          }
        });
      });

    } catch (error) {
      console.error('Erro ao registrar Service Worker:', error);
    }
  });
}

// Função para mostrar notificação de atualização
function showUpdateAvailable() {
  const updateBanner = document.createElement('div');
  updateBanner.id = 'update-banner';
  updateBanner.innerHTML = `
    <div class="bg-blue-600 text-white p-3 text-center">
      <span>Nova versão disponível!</span>
      <button onclick="reloadApp()" class="ml-3 bg-white text-blue-600 px-3 py-1 rounded text-sm font-semibold">
        Atualizar
      </button>
    </div>
  `;
  updateBanner.className = 'fixed top-0 left-0 right-0 z-50';
  document.body.insertBefore(updateBanner, document.body.firstChild);
}

// Função para recarregar app com nova versão
function reloadApp() {
  window.location.reload();
}

// Detecta se está rodando como PWA
function isPWA() {
  return window.matchMedia('(display-mode: standalone)').matches ||
    window.navigator.standalone === true;
}

// Aplica estilos específicos para PWA
if (isPWA()) {
  document.documentElement.classList.add('pwa-mode');
  console.log('Rodando como PWA instalado');
}
