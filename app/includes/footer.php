</main>
<footer class="fixed bottom-0 left-0 right-0 bg-yellow-400 text-center text-black py-1 text-sm z-40">
    <p>&copy; Instituto Céu Interior <?= date('Y') ?> - Todos os direitos reservados</p>
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

<script src="/participantesici/public_html/assets/js/global-scripts.js?t=<?= time() ?>"></script>

</body>
</html>
