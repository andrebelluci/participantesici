// Função para a máscara de CPF
function mascaraCPF(input) {
    let valor = input.value.replace(/\D/g, '');
    if (valor.length > 11) valor = valor.slice(0, 11);
    valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
    valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
    valor = valor.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    input.value = valor;
}

// Função para a máscara de Celular
function mascaraCelular(input) {
    let valor = input.value.replace(/\D/g, '');
    if (valor.length > 11) valor = valor.slice(0, 11);
    valor = valor.replace(/^(\d{2})(\d)/g, '($1) $2');
    valor = valor.replace(/(\d{5})(\d)/, '$1-$2');
    input.value = valor;
}

// Função para a máscara de CEP
function mascaraCEP(input) {
    let valor = input.value.replace(/\D/g, '');
    if (valor.length > 8) valor = valor.slice(0, 8);
    valor = valor.replace(/(\d{5})(\d)/, '$1-$2');
    input.value = valor;
}

// Função para validar e-mail
function validarEmail(email) {
    const regex = /^[a-zA-Z0-9._%+-]{3,}@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    return regex.test(email);
}

// Função para verificar o CPF
function verificarCPF(cpfInput) {
    const cpf = cpfInput.replace(/\D/g, ''); // Remover máscara
    const cpfOriginal = "<?= htmlspecialchars($pessoa['cpf']) ?>"; // CPF original no PHP

    // Ignora se o CPF estiver vazio ou for igual ao original
    if (cpf.length !== 11 || cpf === cpfOriginal) return;

    fetch('verificar-cpf.php?cpf=' + cpf)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                cpfInput.value = '';
                cpfInput.focus();
            } else if (data.exists) {
                alert('Este CPF já está cadastrado.');
                cpfInput.value = '';
                cpfInput.focus();
            }
        })
        .catch(error => console.error('Erro ao verificar CPF:', error));
}

// Função para compressão de imagem
function compressImage(file, maxWidth = 800, quality = 0.8) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const img = new Image();
            img.onload = function () {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                const scale = maxWidth / img.width;
                canvas.width = maxWidth;
                canvas.height = img.height * scale;

                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                canvas.toBlob((blob) => {
                    const compressedUrl = URL.createObjectURL(blob);
                    resolve({ blob, compressedUrl });
                }, 'image/jpeg', quality);
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });
}
