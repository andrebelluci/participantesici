</main>
<footer class="bg-yellow-400 text-center text-black py-1 text-sm">
    <p>&copy; Instituto Céu Interior <?= date('Y') ?> - Todos os direitos reservados</p>
</footer>

<?php if (isset($_SESSION['success'])): ?>
    <script>
        Toastify({
            text: "<?= $_SESSION['success'] ?>",
            duration: 4000,
            close: true,
            gravity: "top",
            position: "right",
            backgroundColor: "#16a34a", // verde do Tailwind (green-600)
            stopOnFocus: true,
        }).showToast();
    </script>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <script>
        Toastify({
            text: "<?= $_SESSION['error'] ?>",
            duration: 5000,
            close: true,
            gravity: "top",
            position: "right",
            backgroundColor: "#dc2626", // vermelho do Tailwind (red-600)
            stopOnFocus: true,
        }).showToast();
    </script>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['mensagem'])): ?>
  <script>
    Toastify({
      text: "<?= $_SESSION['mensagem']['texto'] ?>",
      duration: 4000,
      close: true,
      gravity: "top",
      position: "right",
      backgroundColor: "<?= $_SESSION['mensagem']['tipo'] === 'success' ? '#16a34a' : '#dc2626' ?>",
      stopOnFocus: true,
    }).showToast();
  </script>
  <?php unset($_SESSION['mensagem']); ?>
<?php endif; ?>
<script src="/participantesici/public_html/assets/js/global-scripts.js?t=<?= time() ?>"></script>

</body>
</html>
