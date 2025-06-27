<?php
// app/auth/templates/redefinir_senha.php
session_start();
require_once __DIR__ . '/../../config/database.php';

$token = $_GET['token'] ?? '';
$error = $_SESSION['reset_error'] ?? null;
$success = $_SESSION['reset_success'] ?? null;
unset($_SESSION['reset_error'], $_SESSION['reset_success']);

// Verifica se o token é válido
$tokenValido = false;
$usuario = null;

if ($token) {
    try {
        $stmt = $pdo->prepare("
            SELECT u.id, u.usuario, u.nome
            FROM password_recovery_tokens prt
            JOIN usuarios u ON prt.user_id = u.id
            WHERE prt.token = ? AND prt.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado) {
            $tokenValido = true;
            $usuario = $resultado;
        }
    } catch (Exception $e) {
        error_log("Erro ao verificar token: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Redefinir Senha - ICI</title>
    <link rel="icon" href="/participantesici/public_html/assets/images/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="/participantesici/public_html/assets/js/global-scripts.js?t=<?= time() ?>"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#00bfff',
                    }
                }
            }
        };
    </script>

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

<body class="relative h-screen flex items-center justify-center bg-black/70 text-white overflow-hidden">
    <div class="video-bg">
        <video autoplay muted loop>
            <source src="/participantesici/public_html/assets/videos/fogueira.mp4" type="video/mp4">
            Seu navegador não suporta vídeos.
        </video>
    </div>

    <div class="w-full max-w-md bg-white/5 rounded-lg p-6 shadow-lg mx-4">
        <div class="flex flex-col items-center mb-6">
            <img src="/participantesici/public_html/assets/images/logo.png" alt="Logo" class="w-40 h-auto object-contain" />
            <h2 class="mt-4 text-xl font-semibold text-center text-white">🔑 Nova Senha</h2>

            <?php if ($tokenValido): ?>
                <p class="text-sm text-gray-300 text-center mt-2">
                    Olá, <strong><?= htmlspecialchars($usuario['nome']) ?></strong>!<br>
                    Crie sua nova senha abaixo
                </p>
            <?php else: ?>
                <p class="text-sm text-red-400 text-center mt-2">
                    ⚠️ Link inválido ou expirado
                </p>
            <?php endif; ?>
        </div>

        <?php if ($tokenValido): ?>
            <div class="form-container mobile-compact">
                <form method="POST" action="/participantesici/public_html/salvar-nova-senha" class="space-y-4" novalidate>
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                    <div class="relative">
                        <label for="nova_senha" class="block text-sm font-medium text-white mb-2">
                            <i class="fa-solid fa-lock mr-2"></i>Nova Senha:
                        </label>
                        <input type="password" name="nova_senha" id="nova_senha"
                               placeholder="Digite sua nova senha" required minlength="6"
                               class="w-full p-3 pr-12 rounded border border-gray-300 text-black placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary transition" />
                        <button type="button" onclick="toggleSenha('nova_senha', 'icon1')"
                            class="absolute right-3 top-[38px] text-black hover:text-blue-600" title="Mostrar senha">
                            <i class="fa-solid fa-eye" id="icon1"></i>
                        </button>
                        <p class="text-sm text-red-400 mt-1 hidden">Mínimo 6 caracteres</p>
                    </div>

                    <div class="relative">
                        <label for="confirmar_senha" class="block text-sm font-medium text-white mb-2">
                            <i class="fa-solid fa-lock mr-2"></i>Confirmar Senha:
                        </label>
                        <input type="password" name="confirmar_senha" id="confirmar_senha"
                               placeholder="Digite novamente sua nova senha" required
                               class="w-full p-3 pr-12 rounded border border-gray-300 text-black placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary transition" />
                        <button type="button" onclick="toggleSenha('confirmar_senha', 'icon2')"
                            class="absolute right-3 top-[38px] text-black hover:text-blue-600" title="Mostrar senha">
                            <i class="fa-solid fa-eye" id="icon2"></i>
                        </button>
                        <p class="text-sm text-red-400 mt-1 hidden">As senhas devem coincidir</p>
                    </div>

                    <button type="submit"
                        class="w-full bg-primary font-bold text-white py-3 rounded hover:bg-blue-900 transition">
                        <i class="fa-solid fa-save mr-2"></i>Salvar Nova Senha
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="text-center space-y-4">
                <div class="text-6xl">⏰</div>
                <p class="text-gray-300">
                    Este link de recuperação é inválido ou já expirou.
                </p>
                <a href="/participantesici/public_html/esqueci-senha"
                   class="inline-block bg-primary text-white px-6 py-3 rounded hover:bg-blue-900 transition font-semibold">
                    <i class="fa-solid fa-redo mr-2"></i>Solicitar Novo Link
                </a>
            </div>
        <?php endif; ?>

        <div class="text-center space-y-3 mt-6">
            <hr class="border-gray-400">
            <a href="/participantesici/public_html/login"
               class="inline-flex items-center text-sm text-gray-300 hover:text-primary transition">
                <i class="fa-solid fa-arrow-left mr-2"></i>Voltar ao Login
            </a>
        </div>
    </div>

    <!-- Mensagens de Feedback -->
    <?php if ($success): ?>
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                showToast(<?= json_encode($success) ?>, 'success');
            });
        </script>
    <?php endif; ?>

    <?php if ($error): ?>
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                showToast(<?= json_encode($error) ?>, 'error');
            });
        </script>
    <?php endif; ?>

    <script>
        // Função para alternar visibilidade das senhas
        function toggleSenha(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Validação de senhas
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const novaSenha = document.getElementById('nova_senha');
            const confirmarSenha = document.getElementById('confirmar_senha');

            if (form) {
                form.addEventListener('submit', function(e) {
                    let valido = true;

                    // Valida tamanho mínimo
                    if (novaSenha.value.length < 6) {
                        novaSenha.classList.add('border-red-500');
                        novaSenha.nextElementSibling.nextElementSibling.classList.remove('hidden');
                        valido = false;
                    } else {
                        novaSenha.classList.remove('border-red-500');
                        novaSenha.nextElementSibling.nextElementSibling.classList.add('hidden');
                    }

                    // Valida se as senhas coincidem
                    if (novaSenha.value !== confirmarSenha.value) {
                        confirmarSenha.classList.add('border-red-500');
                        confirmarSenha.nextElementSibling.nextElementSibling.classList.remove('hidden');
                        valido = false;
                    } else {
                        confirmarSenha.classList.remove('border-red-500');
                        confirmarSenha.nextElementSibling.nextElementSibling.classList.add('hidden');
                    }

                    if (!valido) {
                        e.preventDefault();
                        showToast('Por favor, corrija os erros no formulário', 'error');
                    }
                });
            }
        });
    </script>
</body>

</html>