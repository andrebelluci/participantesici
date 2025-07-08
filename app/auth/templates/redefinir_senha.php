<?php
// app/auth/templates/redefinir_senha.php
session_start();
require_once __DIR__ . '/../../config/database.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    $_SESSION['error'] = 'Token de recuperação inválido.';
    header('Location: /login');
    exit;
}

// Verifica se o token é válido e não expirou usando a tabela password_recovery_tokens
$stmt = $pdo->prepare("
    SELECT u.id, u.nome, u.usuario
    FROM password_recovery_tokens prt
    JOIN usuarios u ON prt.user_id = u.id
    WHERE prt.token = ? AND prt.expires_at > NOW()
");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = 'Token de recuperação inválido ou expirado. Envie novamente.';
    header('Location: /esqueci-senha');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#000000">
    <title>Redefinir Senha - ICI</title>
    <link rel="icon" href="/assets/images/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="/assets/js/global-scripts.js?t=<?= time() ?>"></script>
    <link rel="stylesheet" href="/assets/css/tailwind.css?v=1.0">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <style>
        .video-bg video {
            position: fixed;
            top: 0;
            left: 0;
            min-width: 100%;
            min-height: 100%;
            object-fit: cover;
            z-index: -1;
        }
    </style>
</head>

<body class="relative h-screen flex items-center justify-center bg-black/50 text-white overflow-hidden">
    <div class="video-bg">
        <video autoplay muted loop>
            <source src="/assets/videos/fogueira.mp4" type="video/mp4">
            Seu navegador não suporta vídeos.
        </video>
    </div>

    <div class="w-full max-w-md bg-black/50 rounded-lg p-6 shadow-lg mx-4 backdrop-blur-sm">
        <div class="flex flex-col items-center mb-6">
            <img src="/assets/images/logo.png" alt="Logo"
                class="w-40 h-auto object-contain" />
            <h2 class="mt-4 text-xl font-semibold text-center text-white">Redefinir Senha</h2>
            <p class="mt-2 text-sm text-gray-300 text-center">Olá, <?= htmlspecialchars($user['nome']) ?></p>
        </div>

        <div class="form-container mobile-compact">
            <form method="POST" action="/salvar-nova-senha" class="space-y-4" novalidate id="redefinir-senha-form">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div class="relative group">
                    <input type="password" name="nova_senha" id="nova_senha" placeholder="Nova Senha" required
                        class="bg-white w-full p-3 rounded border border-gray-300 text-black placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#00bfff] transition" />

                    <!-- Indicadores de validação da senha -->
                    <div id="senha-validacao" class="mt-2 space-y-1 text-xs">
                        <div class="flex items-center gap-2" id="tamanho-check">
                            <i class="fa-solid fa-circle-xmark text-red-500"></i>
                            <span class="text-gray-300">Pelo menos 8 caracteres</span>
                        </div>
                        <div class="flex items-center gap-2" id="maiuscula-check">
                            <i class="fa-solid fa-circle-xmark text-red-500"></i>
                            <span class="text-gray-300">1 letra maiúscula</span>
                        </div>
                        <div class="flex items-center gap-2" id="numero-check">
                            <i class="fa-solid fa-circle-xmark text-red-500"></i>
                            <span class="text-gray-300">1 número</span>
                        </div>
                        <div class="flex items-center gap-2" id="especial-check">
                            <i class="fa-solid fa-circle-xmark text-red-500"></i>
                            <span class="text-gray-300">1 caractere especial</span>
                        </div>
                    </div>
                </div>

                <div class="relative group">
                    <input type="password" name="confirmar_senha" id="confirmar_senha"
                        placeholder="Confirmar Nova Senha" required
                        class="w-full p-3 rounded border border-gray-300 text-black placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#00bfff] transition" />

                    <!-- Indicador de correspondência das senhas -->
                    <div id="senha-match" class="mt-2 hidden">
                        <div class="flex items-center gap-2">
                            <i id="match-icon" class="fa-solid fa-circle-xmark text-red-500"></i>
                            <span id="match-text" class="text-xs text-gray-300">As senhas não coincidem</span>
                        </div>
                    </div>
                </div>

                <button type="button" onclick="toggleTodasSenhas(this, this.querySelector('i'))"
                    class="text-yellow-400 text-sm hover:text-[#00bfff] flex items-center gap-2 transition">
                    <i class="fa-solid fa-eye"></i> Mostrar Senhas
                </button>

                <button type="submit" id="submit-btn" disabled
                    class="w-full bg-gray-500 font-bold text-black py-3 rounded transition cursor-not-allowed opacity-50">
                    <i class="fa-solid fa-save mr-2"></i>
                    Salvar Nova Senha
                </button>

                <div class="text-center space-y-3 mt-6">
                    <hr class="border-yellow-400">
                    <a href="/login"
                        class="inline-flex items-center text-yellow-400 hover:text-[#00bfff] text-sm transition font-semibold">
                        <i class="fa-solid fa-arrow-left mr-1"></i>
                        Voltar ao Login
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php
    // Exibe mensagens de sessão
    if (isset($_SESSION['success'])) {
        echo "<script>document.addEventListener('DOMContentLoaded', () => { showToast('" . addslashes($_SESSION['success']) . "', 'success'); });</script>";
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        echo "<script>document.addEventListener('DOMContentLoaded', () => { showToast('" . addslashes($_SESSION['error']) . "', 'error'); });</script>";
        unset($_SESSION['error']);
    }
    ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Adiciona classe senha-input para funcionar com toggleTodasSenhas
            document.getElementById('nova_senha').classList.add('senha-input');
            document.getElementById('confirmar_senha').classList.add('senha-input');

            const novaSenhaInput = document.getElementById('nova_senha');
            const confirmarSenhaInput = document.getElementById('confirmar_senha');
            const submitBtn = document.getElementById('submit-btn');
            const form = document.getElementById('redefinir-senha-form');

            // Elementos de validação
            const tamanhoCheck = document.getElementById('tamanho-check');
            const maiusculaCheck = document.getElementById('maiuscula-check');
            const numeroCheck = document.getElementById('numero-check');
            const especialCheck = document.getElementById('especial-check');
            const senhaMatch = document.getElementById('senha-match');
            const matchIcon = document.getElementById('match-icon');
            const matchText = document.getElementById('match-text');

            // Função para validar senha
            function validarSenha(senha) {
                const validacoes = {
                    tamanho: senha.length >= 8,
                    maiuscula: /[A-Z]/.test(senha),
                    numero: /[0-9]/.test(senha),
                    especial: /[^a-zA-Z0-9]/.test(senha)
                };

                // Atualiza indicadores visuais
                updateCheckIcon(tamanhoCheck, validacoes.tamanho);
                updateCheckIcon(maiusculaCheck, validacoes.maiuscula);
                updateCheckIcon(numeroCheck, validacoes.numero);
                updateCheckIcon(especialCheck, validacoes.especial);

                return Object.values(validacoes).every(v => v);
            }

            // Função para atualizar ícone de check
            function updateCheckIcon(element, isValid) {
                const icon = element.querySelector('i');
                const text = element.querySelector('span');

                if (isValid) {
                    icon.className = 'fa-solid fa-circle-check text-green-500';
                    text.className = 'text-green-400';
                } else {
                    icon.className = 'fa-solid fa-circle-xmark text-red-500';
                    text.className = 'text-gray-300';
                }
            }

            // Função para verificar se senhas coincidem
            function verificarCorrespondencia() {
                const novaSenha = novaSenhaInput.value;
                const confirmarSenha = confirmarSenhaInput.value;

                if (confirmarSenha.length > 0) {
                    senhaMatch.classList.remove('hidden');

                    if (novaSenha === confirmarSenha) {
                        matchIcon.className = 'fa-solid fa-circle-check text-green-500';
                        matchText.textContent = 'As senhas coincidem';
                        matchText.className = 'text-xs text-green-400';
                        return true;
                    } else {
                        matchIcon.className = 'fa-solid fa-circle-xmark text-red-500';
                        matchText.textContent = 'As senhas não coincidem';
                        matchText.className = 'text-xs text-red-400';
                        return false;
                    }
                } else {
                    senhaMatch.classList.add('hidden');
                    return false;
                }
            }

            // Função para habilitar/desabilitar botão
            function atualizarBotao() {
                const senhaValida = validarSenha(novaSenhaInput.value);
                const senhasCorrespondem = verificarCorrespondencia();
                const podeSubmeter = senhaValida && senhasCorrespondem && novaSenhaInput.value.length > 0;

                if (podeSubmeter) {
                    submitBtn.disabled = false;
                    submitBtn.className = 'w-full bg-[#00bfff] font-bold text-black py-3 rounded hover:bg-yellow-400 transition cursor-pointer';
                } else {
                    submitBtn.disabled = true;
                    submitBtn.className = 'w-full bg-gray-500 font-bold text-black py-3 rounded transition cursor-not-allowed opacity-50';
                }
            }

            // Event listeners
            novaSenhaInput.addEventListener('input', function() {
                validarSenha(this.value);
                verificarCorrespondencia();
                atualizarBotao();
            });

            confirmarSenhaInput.addEventListener('input', function() {
                verificarCorrespondencia();
                atualizarBotao();
            });

            // Validação no submit do formulário
            form.addEventListener('submit', function(e) {
                const senhaValida = validarSenha(novaSenhaInput.value);
                const senhasCorrespondem = novaSenhaInput.value === confirmarSenhaInput.value;

                if (!senhaValida) {
                    e.preventDefault();
                    showToast('A senha deve atender todos os critérios de segurança', 'error');
                    return;
                }

                if (!senhasCorrespondem) {
                    e.preventDefault();
                    showToast('As senhas não coincidem', 'error');
                    return;
                }

                // Se chegou até aqui, está tudo válido
                showToast('Salvando nova senha...', 'info');
            });
        });
    </script>
</body>

</html>