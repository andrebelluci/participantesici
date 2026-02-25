# 🌟 Sistema de Participantes - Instituto Céu Interior

Sistema web para gerenciamento de participantes e rituais do Instituto Céu Interior, desenvolvido em PHP com design responsivo e otimizado para dispositivos móveis.

[![🧪 Deploy Teste - Pasta Segura](https://github.com/andrebelluci/participantesici/actions/workflows/deploy.yml/badge.svg?branch=main)](https://github.com/andrebelluci/participantesici/actions/workflows/deploy.yml)
![Status](https://img.shields.io/badge/Status-Ativo-green)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
![TailwindCSS](https://img.shields.io/badge/TailwindCSS-v4-06B6D4)
![Licença](https://img.shields.io/badge/Licença-Proprietária-red)
![Uso Comercial](https://img.shields.io/badge/Uso_Comercial-Proibido-red)
![Contato](https://img.shields.io/badge/Licenciamento-andrebelluci@gmail.com-blue)

---

## ⚠️ AVISO LEGAL E PROPRIEDADE INTELECTUAL

### 🔒 **Propriedade Exclusiva**
Este sistema é **propriedade exclusiva** do **Instituto Céu Interior** e foi desenvolvido por **André Belluci**. O código está disponível publicamente apenas para fins de **demonstração profissional**, **portfólio** e **educacionais**.

### 📋 **Termos de Uso**
✅ **PERMITIDO**:
- Visualização e estudo do código fonte
- Uso como referência para aprendizado
- Fork para análise técnica pessoal
- Citação em trabalhos acadêmicos

❌ **ESTRITAMENTE PROIBIDO**:
- Uso comercial sem autorização expressa por escrito
- Modificação e redistribuição do código
- Criação de trabalhos derivados para fins comerciais
- Remoção de créditos ou marca do desenvolvedor
- Uso das marcas "Instituto Céu Interior" ou logotipos
- Implementação para outros clientes sem licenciamento

### ⚖️ **Consequências Legais**
O uso não autorizado deste software está sujeito a **medidas legais** incluindo, mas não limitado a, ações por violação de direitos autorais e propriedade intelectual.

### 💼 **Licenciamento Comercial Disponível**
Interessado em um sistema similar ou licenciamento? **Entre em contato!**

---

## 📋 Sobre o Projeto

O Sistema de Participantes ICI é uma aplicação web completa para gestão de pessoas e eventos espirituais, oferecendo:

- **Cadastro de Participantes** com informações completas e fotos
- **Gestão de Rituais** com controle de presença e observações
- **Sistema de Inscrições** com vinculação automática
- **Recuperação de Senha** via email
- **Interface Responsiva** otimizada para mobile e desktop
- **Compressão Automática** de imagens para melhor performance

## ✨ Funcionalidades Principais

### 👥 Gestão de Participantes
- ✅ Cadastro completo com dados pessoais
- ✅ Upload e crop de fotos de perfil
- ✅ Validação de CPF e email
- ✅ Histórico de participação em rituais
- ✅ Sistema de observações individuais
- ✅ Gestão completa de documentos
  - Upload de imagens e PDFs com compressão automática
  - Crop flexível de documentos (qualquer tamanho/orientação)
  - Visualização com PhotoSwipe e zoom dinâmico
  - Download e exclusão de documentos
- ✅ Bloqueio de vinculação a novos rituais
  - Controle de permissão para vincular a novos rituais
  - Campo obrigatório de motivo quando bloqueado
  - Validação automática ao tentar adicionar participante bloqueado

### 🔥 Gestão de Rituais
- ✅ Criação e edição de rituais
- ✅ Controle de presença (presente/ausente)
- ✅ Vinculação de participantes
- ✅ Upload de imagens dos rituais
- ✅ Relatórios de participação (PDF/Excel)
- ✅ Sistema inteligente de inscrições com cópia automática de dados
- ✅ Validação visual de campos obrigatórios

### 📊 Sistema de Relatórios e Exportação
- ✅ **Relatórios de Listagem (PDF/Excel)**
  - Geração de listas profissionais para Participantes e Rituais
  - Layout A4 Paisagem otimizado com cabeçalhos e logo centralizado
  - Suporte total a filtros de busca dinâmicos nas exportações
- ✅ **Relatórios Individuais (PDF/Excel)**
  - Detalhamento completo de participantes e rituais
  - Layout padronizado com bordas profissionais e alinhamento à direita
- ✅ **Interface Unificada**
  - Botão dropdown de exportação intuitivo em todas as listagens
  - Feedback visual (loading) durante o processamento do relatório
- ✅ **URLs Amigáveis**
  - Rotas curtas e limpas via `.htaccess` para acesso direto aos relatórios

### 📋 Sistema de Inscrições
- ✅ Lógica de "primeira vez" com campos condicionais
- ✅ Cópia automática de dados entre inscrições
- ✅ Notificação visual de campos obrigatórios não preenchidos
- ✅ Botão dinâmico Fechar/Salvar baseado em alterações
- ✅ Confirmação ao fechar modal com mudanças não salvas
- ✅ Campos condicionais (doença/medicação) sempre editáveis quando aplicável

### 🔐 Sistema de Autenticação
- ✅ Login seguro com captcha
- ✅ Recuperação de senha via email
- ✅ Perfis de usuário (Administrador/Usuário)
- ✅ Controle de sessão

### 📱 Otimizações Mobile
- ✅ Design responsivo com Tailwind CSS
- ✅ Prevenção de seleção acidental de texto
- ✅ Navegação otimizada para touch
- ✅ Suporte a PWA (Progressive Web App)
- ✅ Crop flexível de documentos para qualquer tamanho
- ✅ Zoom dinâmico em visualização de imagens

## 🛠️ Tecnologias Utilizadas

### Backend
- **PHP 7.4+** - Linguagem principal
- **MySQL/MariaDB** - Banco de dados
- **PDO** - Abstração de banco de dados
- **PHPMailer** - Envio de emails

### Frontend
- **HTML5 & CSS3** - Estrutura e estilo
- **Tailwind CSS v4** - Framework CSS
- **JavaScript (Vanilla)** - Interatividade
- **Font Awesome** - Ícones
- **Cropper.js** - Edição de imagens com crop flexível
- **PhotoSwipe** - Visualização de imagens com zoom dinâmico

### Ferramentas de Desenvolvimento
- **NPM** - Gerenciamento de dependências
- **Tailwind CLI** - Build do CSS
- **GitHub Actions** - Deploy automático

## 📁 Estrutura do Projeto

```
📦 participantes-ici/
├── 📂 app/                          # Código PHP principal
│   ├── 📂 auth/                     # Sistema de autenticação
│   │   ├── 📂 actions/              # Processamento de login/recuperação
│   │   └── 📂 templates/            # Templates de login
│   ├── 📂 config/                   # Configurações do sistema
│   ├── 📂 functions/                # Funções auxiliares
│   ├── 📂 includes/                 # Headers e componentes
│   ├── 📂 participantes/            # Módulo de participantes
│   ├── 📂 rituais/                  # Módulo de rituais
│   ├── 📂 services/                 # Serviços (Email, Captcha)
│   └── 📂 usuarios/                 # Gestão de usuários
├── 📂 public_html/                  # Arquivos públicos
│   ├── 📂 assets/                   # CSS, JS, imagens
│   │   ├── 📂 css/                  # Estilos compilados
│   │   ├── 📂 js/                   # JavaScript
│   │   └── 📂 images/               # Imagens do sistema
│   ├── 📂 storage/                  # Uploads e logs
│   │   └── 📂 uploads/              # Fotos dos participantes/rituais
│   └── 📄 index.php                 # Ponto de entrada
├── 📂 .github/                      # GitHub Actions
│   └── 📂 workflows/                # Deploy automatizado
├── 📄 .gitignore                    # Arquivos ignorados
├── 📄 .htaccess                     # Configurações Apache
├── 📄 LICENSE.md                    # Licença de software
└── 📄 README.md                     # Este arquivo
```

## 🚀 Instalação e Configuração

> **⚠️ ATENÇÃO**: Esta seção é apenas para fins educacionais. Para implementação comercial, entre em contato para licenciamento.

### Pré-requisitos
- PHP 7.4 ou superior
- MySQL/MariaDB 5.7+
- Servidor web (Apache/Nginx)
- Node.js 16+ (para desenvolvimento)

### 1. Clone o Repositório
```bash
git clone https://github.com/andrebelluci/participantesici.git
cd participantesici
```

### 2. Configuração do Banco de Dados
```sql
-- Crie o banco de dados
CREATE DATABASE ici-sistema;

-- Importe o arquivo SQL (se disponível)
mysql -u usuario -p ici-sistema < database.sql
```

### 3. Configuração do Ambiente
```bash
# Copie o arquivo de configuração
cp .env.example .env

# Configure as variáveis de ambiente
nano .env
```

### 4. Configuração de Desenvolvimento (Opcional)
```bash
# Instale dependências do Node.js
npm install

# Compile o CSS do Tailwind
npm run build

# Para desenvolvimento com watch
npm run dev
```

### 5. Configuração do Servidor
- Configure o DocumentRoot para `/public_html`
- Certifique-se que `mod_rewrite` está habilitado
- Configure permissões de escrita em `/storage`

## ⚙️ Configuração

### Variáveis de Ambiente (.env)
```env
# Banco de Dados
DB_HOST=localhost
DB_NAME=participantes_ici
DB_USER=usuario
DB_PASS=senha

# Email
MAIL_HOST=mail.seudominio.com
MAIL_USERNAME=sistema@seudominio.com
MAIL_PASSWORD=senha_email
MAIL_FROM_EMAIL=sistema@seudominio.com
MAIL_FROM_NAME=Instituto Céu Interior

# Captcha (Google reCAPTCHA)
RECAPTCHA_SITE_KEY=sua_site_key
RECAPTCHA_SECRET_KEY=sua_secret_key
```

### Estrutura do Banco de Dados
As principais tabelas incluem:
- `participantes` - Dados dos participantes
- `rituais` - Informações dos rituais
- `inscricoes` - Vínculos participante-ritual
- `usuarios` - Sistema de autenticação
- `perfis` - Níveis de acesso

## 🔧 Scripts NPM

```bash
# Desenvolvimento com watch
npm run dev

# Build para produção
npm run build

# Build sem minificação
npm run build-dev
```

## 🚀 Deploy

O projeto utiliza GitHub Actions para deploy automático:

1. **Push para `main`** - Deploy automático via FTP
2. **Estrutura no servidor**: `/home/participantesici/`
3. **Pastas atualizadas**: `app/` e `public_html/`
4. **Arquivos protegidos**: uploads, logs, configurações

### Configuração dos Secrets
```
FTP_HOST = seu-servidor.com
FTP_USERNAME = usuario-ftp
FTP_PASSWORD = senha-ftp
```

## 📱 Recursos Mobile

- **Design Responsivo** para todas as telas
- **Touch Optimizado** com prevenção de seleção acidental
- **Upload de Fotos** com compressão automática
- **Navegação Intuitiva** com menu hambúrguer
- **Performance Otimizada** com CSS minificado

## 🎨 Personalização

### Cores do Sistema
```css
--color-ici-blue: #00bfff    /* Azul principal */
--color-ici-yellow: #facc15  /* Amarelo de destaque */
--color-ici-green: #16a34a   /* Verde de sucesso */
--color-ici-red: #dc2626     /* Vermelho de erro */
--color-ici-orange: #ea580c  /* Laranja de alerta */
```

### Componentes CSS
```css
.btn-primary     /* Botão principal */
.btn-secondary   /* Botão secundário */
.card           /* Card padrão */
.form-input     /* Input de formulário */
.modal-overlay  /* Modal de sobreposição */
```

## 🤝 Contribuição

Este é um projeto privado do Instituto Céu Interior. Para contribuições ou melhorias:

1. Abra uma **Issue** descrevendo a sugestão
2. **Aguarde aprovação** antes de fazer alterações
3. Para implementações comerciais, **entre em contato** para licenciamento

**Nota**: Pull requests não autorizados podem ser rejeitados para proteger a propriedade intelectual.

## 📞 Contato para Licenciamento

### 👨‍💻 **André Belluci** - Desenvolvedor Principal
- 📧 **Email**: [andrebelluci@gmail.com](mailto:andrebelluci@gmail.com?subject=Licenciamento%20-%20Sistema%20Participantes%20ICI)
- 📱 **WhatsApp**: [+55 17 99144-6829](https://wa.me/5517991446829?text=Olá!%20Tenho%20interesse%20no%20licenciamento%20do%20Sistema%20Participantes%20ICI)
- 💼 **GitHub**: [github.com/andrebelluci](https://github.com/andrebelluci)
- 🔗 **LinkedIn**: [linkedin.com/in/andrebelluci](https://linkedin.com/in/andrebelluci)

### 🏛️ **Instituto Céu Interior**
- 🌐 **Site Oficial**: [www.institutoceuinterior.com.br](https://www.institutoceuinterior.com.br)


---

## 💡 Interessado em um Sistema Similar?

**Desenvolvemos soluções personalizadas!** Entre em contato para:
- 🏗️ Sistemas de gestão sob medida
- 🔧 Consultoria em desenvolvimento PHP
- 📱 Aplicações web responsivas
- 🚀 Implementação e deploy automatizado

> **Transforme sua ideia em realidade** com código limpo, seguro e escalável!

---

## 📄 Licença

Este projeto é **propriedade privada** do **Instituto Céu Interior**.
**Todos os direitos reservados**. O uso não autorizado é **estritamente proibido**.

Para uso comercial ou licenciamento, consulte os contatos acima.

## 🏛️ Instituto Céu Interior

<div align="center" >

**Desenvolvido com ❤️ para o Instituto Céu Interior**

<div style="width: 100px;">

![ICI Logo](public_html/assets/images/logo.png)
</div>

---

**© 2025 Instituto Céu Interior & André Belluci - Todos os direitos reservados**

</div>