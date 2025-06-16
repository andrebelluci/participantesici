<?php
session_start();
require_once __DIR__ . '/../../functions/check_auth.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<section class="px-4 py-8 max-w-5xl mx-auto">
    <h1 class="text-2xl font-bold text-black mb-6 text-center">Bem-vindo, <?php echo htmlspecialchars($_SESSION['nome']); ?>!</h1>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <a href="/participantesici/public_html/participantes"
           class="bg-[#00bfff] text-black text-lg font-semibold py-6 rounded-lg text-center shadow hover:bg-yellow-400 transition">
            Participantes
        </a>
        <a href="/participantesici/public_html/rituais"
           class="bg-[#00bfff] text-black text-lg font-semibold py-6 rounded-lg text-center shadow hover:bg-yellow-400 transition">
            Rituais
        </a>
    </div>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>