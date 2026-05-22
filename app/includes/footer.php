</main>
<footer class="fixed bottom-0 left-0 right-0 bg-yellow-400 text-center text-black py-1 text-sm z-40">
    <p>
        &copy; Instituto Céu Interior <?= date('Y') ?>
        <span class="text-xs text-black/75">
            - Desenvolvido com <i class="fa-solid fa-heart fa-beat text-blue-600" style="--fa-animation-duration: 1.2s;" aria-hidden="true"></i> por
            <a href="https://dharmalabs.com.br" target="_blank" rel="noopener noreferrer" class="underline hover:text-black">DharmaLabs</a>
        </span>
    </p>
</footer>

<?php if (isset($_SESSION['success'])): ?>
    <script>
        // ✅ USA A NOVA FUNÇÃO showToast
        showToast("<?= $_SESSION['success'] ?>", 'success');
    </script>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <script>
        // ✅ USA A NOVA FUNÇÃO showToast
        showToast("<?= $_SESSION['error'] ?>", 'error');
    </script>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['mensagem'])): ?>
    <script>
        // ✅ USA A NOVA FUNÇÃO showToast
        showToast("<?= $_SESSION['mensagem']['texto'] ?>", "<?= $_SESSION['mensagem']['tipo'] === 'success' ? 'success' : 'error' ?>");
    </script>
    <?php unset($_SESSION['mensagem']); ?>
<?php endif; ?>

</body>
</html>
