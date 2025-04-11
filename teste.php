<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'includes/db.php';
require_once 'includes/header.php';
    
?>

<body>
    <label for="cpf">CPF:</label>
    <input type="text" id="cpf" placeholder="Digite o CPF">

    <label for="celular">Celular:</label>
    <input type="text" id="celular" placeholder="Digite o celular">

    <script>
        $(document).ready(function() {
            $('#cpf').inputmask('999.999.999-99');
            $('#celular').inputmask('(99) 99999-9999');
        });
    </script>
</body>

</html>