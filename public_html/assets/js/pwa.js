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
  e.preventDefault();
  deferredPrompt = e;
  showInstallButton();
});

// Função para mostrar botão de instalação
function showInstallButton() {
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

  deferredPrompt.prompt();
  const { outcome } = await deferredPrompt.userChoice;
  console.log(`Usuário ${outcome} a instalação`);

  if (outcome === 'accepted') {
    hideInstallButton();
  }

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

// ✅ VERSÃO CONTROLADA DO SERVICE WORKER
if ('serviceWorker' in navigator) {
  window.addEventListener('load', async () => {
    try {
      // ✅ VERSÃO FIXA - só muda quando você realmente quiser
      const SW_VERSION = '2.2'; // 👈 ALTERE ESTE NÚMERO APENAS QUANDO QUISER FORÇAR UPDATE

      // Verifica se já existe um SW registrado
      const existingRegistration = await navigator.serviceWorker.getRegistration('/');

      if (existingRegistration) {
        console.log('Service Worker já registrado, verificando updates...');

        // Força verificação de update
        existingRegistration.update();

        // Só mostra notificação se realmente houver mudança
        existingRegistration.addEventListener('updatefound', () => {
          const newWorker = existingRegistration.installing;

          newWorker.addEventListener('statechange', () => {
            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
              // ✅ SÓ AQUI que mostra a notificação real
              console.log('Nova versão do Service Worker detectada!');
              showUpdateAvailable();
            }
          });
        });

      } else {
        // Primeiro registro do Service Worker
        console.log('Registrando Service Worker pela primeira vez...');

        const registration = await navigator.serviceWorker.register(
          `/service-worker.js?v=${SW_VERSION}`, // ✅ Versão controlada
          { scope: '/' }
        );

        console.log('Service Worker registrado:', registration.scope);
      }

    } catch (error) {
      console.error('Erro ao registrar Service Worker:', error);
    }
  });
}

// ✅ FUNÇÃO MELHORADA PARA MOSTRAR ATUALIZAÇÃO
function showUpdateAvailable() {
  // Evita mostrar múltiplas notificações
  if (document.getElementById('update-banner')) {
    return;
  }

  const updateBanner = document.createElement('div');
  updateBanner.id = 'update-banner';
  updateBanner.innerHTML = `
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4 text-center shadow-lg">
      <div class="flex items-center justify-center gap-3">
        <i class="fa-solid fa-download text-lg"></i>
        <span class="font-medium">Nova versão disponível!</span>
        <div class="flex gap-2">
          <button onclick="reloadApp()" class="bg-white text-blue-600 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-gray-100 transition-colors">
            Atualizar Agora
          </button>
          <button onclick="dismissUpdate()" class="text-blue-100 hover:text-white px-3 py-2 text-sm">
            Depois
          </button>
        </div>
      </div>
    </div>
  `;
  updateBanner.className = 'fixed top-0 left-0 right-0 z-[9999] animate-fade-in';

  // Adiciona animação CSS
  updateBanner.style.cssText = `
    animation: slideDown 0.3s ease-out;
    transform: translateY(0);
  `;

  document.body.insertBefore(updateBanner, document.body.firstChild);

  // Auto-dismiss após 30 segundos
  setTimeout(() => {
    dismissUpdate();
  }, 30000);
}

// ✅ FUNÇÃO PARA DISPENSAR UPDATE
function dismissUpdate() {
  const updateBanner = document.getElementById('update-banner');
  if (updateBanner) {
    updateBanner.style.transform = 'translateY(-100%)';
    updateBanner.style.opacity = '0';
    setTimeout(() => {
      updateBanner.remove();
    }, 300);
  }
}

// Função para recarregar app com nova versão
function reloadApp() {
  // Limpa caches antes de recarregar
  if ('caches' in window) {
    caches.keys().then(keyList => {
      keyList.forEach(key => {
        if (key.includes('dynamic')) {
          caches.delete(key);
        }
      });
    }).finally(() => {
      window.location.reload();
    });
  } else {
    window.location.reload();
  }
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

// ✅ ADICIONA CSS PARA ANIMAÇÕES
const style = document.createElement('style');
style.textContent = `
  @keyframes slideDown {
    from {
      transform: translateY(-100%);
      opacity: 0;
    }
    to {
      transform: translateY(0);
      opacity: 1;
    }
  }

  .animate-fade-in {
    animation: slideDown 0.3s ease-out;
  }
`;
document.head.appendChild(style);