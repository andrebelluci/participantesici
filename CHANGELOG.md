# Plano de Melhorias - Sistema ICI

## 1. Migração CSS - visualizar.php (PRIORIDADE 1)

### Arquivos a serem modificados:
- `app/rituais/templates/visualizar.php`

### Tarefas:
- [ ] **1.1** - Migrar estrutura principal do layout para Tailwind CSS
- [ ] **1.2** - Converter sistema de cards dos participantes
- [ ] **1.3** - Atualizar modais (adicionar participante, detalhes, observação)
- [ ] **1.4** - Implementar responsividade mobile/desktop
- [ ] **1.5** - Ajustar sistema de filtros
- [ ] **1.6** - Melhorar botões de ação (presente/ausente, observação, etc.)

### Resultado esperado:
Interface consistente com o resto do sistema, melhor UX e responsividade.

---

## 2. Sistema de Upload de Rituais

### Análise:
✅ **APROVADO** - Manter sem crop já que as imagens já vêm quadradas. Foco apenas em:
- Validação de formato de arquivo
- Compressão automática se necessário
- Preview antes do upload

### Tarefas:
- [ ] **2.1** - Validar se upload está funcionando corretamente
- [ ] **2.2** - Adicionar feedback visual durante upload
- [ ] **2.3** - Implementar preview da imagem selecionada

---

## 3. Validações Frontend (AJAX)

### O que implementar:

#### 3.1 Validação em tempo real:
- **CPF**: Validar formato e dígitos verificadores
- **E-mail**: Validar formato
- **Telefone**: Validar formato brasileiro
- **Campos obrigatórios**: Highlight em vermelho quando vazios

#### 3.2 Feedback imediato:
- Ícones de ✅ ou ❌ ao lado dos campos
- Mensagens de erro específicas
- Prevenção de submit com dados inválidos

### Arquivos envolvidos:
- JavaScript de validação (criar novo arquivo)
- Formulários de participante e ritual
- CSS para estados de erro/sucesso

### Tarefas:
- [ ] **3.1** - Criar `assets/js/validacoes.js`
- [ ] **3.2** - Implementar validação de CPF em tempo real
- [ ] **3.3** - Implementar validação de e-mail
- [ ] **3.4** - Implementar validação de telefone
- [ ] **3.5** - Adicionar feedback visual nos formulários
- [ ] **3.6** - Integrar validações nos formulários existentes

---

## 4. Performance e SEO

### 4.1 Otimização de Imagens:
- **Compressão automática**: Reduzir tamanho sem perder qualidade
- **Lazy loading**: Carregar imagens conforme usuário scrolla
- **WebP**: Converter automaticamente para formato mais eficiente
- **Dimensões adequadas**: Redimensionar no servidor

### 4.2 Performance Geral:
- **Minificação**: CSS e JS menores
- **Cache de assets**: Configurar headers adequados
- **Sprites de ícones**: Reduzir requests HTTP

### 4.3 SEO Básico:
- **Meta tags dinâmicas**: Title e description por página
- **Open Graph**: Para compartilhamento em redes sociais
- **Schema.org**: Structured data para eventos/pessoas

### Tarefas:
- [ ] **4.1** - Implementar compressão de imagens no upload
- [ ] **4.2** - Adicionar lazy loading nas listagens
- [ ] **4.3** - Configurar meta tags dinâmicas
- [ ] **4.4** - Otimizar carregamento de CSS/JS
- [ ] **4.5** - Implementar cache de assets

---

## 5. Acessibilidade

### 5.1 Navegação por Teclado:
- **Tab order**: Ordem lógica de navegação
- **Focus visível**: Highlight claro no elemento focado
- **Skip links**: "Pular para conteúdo principal"
- **Escape**: Fechar modais com ESC

### 5.2 Screen Readers:
- **Alt text**: Todas as imagens com descrição
- **ARIA labels**: Labels descritivas para botões/links
- **Landmarks**: `<main>`, `<nav>`, `<section>` adequados
- **Headings**: Hierarquia H1, H2, H3 correta

### 5.3 Contraste e Legibilidade:
- **Contraste WCAG**: Mínimo 4.5:1 para texto normal
- **Tamanho de fonte**: Mínimo 16px
- **Área de toque**: Botões com mínimo 44px
- **Estados hover/focus**: Visualmente distintos

### Tarefas:
- [ ] **5.1** - Implementar navegação por teclado nos modais
- [ ] **5.2** - Adicionar ARIA labels nos botões de ação
- [ ] **5.3** - Melhorar alt text das imagens
- [ ] **5.4** - Corrigir hierarquia de headings
- [ ] **5.5** - Aumentar contraste em elementos com baixo contraste
- [ ] **5.6** - Implementar focus visível consistente
- [ ] **5.7** - Adicionar skip links

---

## 6. Cronograma Sugerido

### Semana 1:
- Migração CSS completa do visualizar.php
- Testes de responsividade

### Semana 2:
- Implementação das validações frontend
- Melhorias no sistema de upload

### Semana 3:
- Otimizações de performance
- Implementação de lazy loading

### Semana 4:
- Melhorias de acessibilidade
- Testes finais e ajustes

---

## 7. Como Solicitar as Implementações

### Formato sugerido:
```
"Implemente a tarefa [NÚMERO] do plano de melhorias:
[DESCRIÇÃO DA TAREFA]

Arquivos envolvidos: [LISTA DE ARQUIVOS]
Foco: [OBJETIVO ESPECÍFICO]"
```

### Exemplo:
```
"Implemente a tarefa 1.1 do plano de melhorias:
Migrar estrutura principal do layout para Tailwind CSS

Arquivos envolvidos: app/rituais/templates/visualizar.php
Foco: Converter CSS atual para classes Tailwind mantendo funcionalidades"
```

---

## 8. Observações Importantes

- Sempre mantenha backup dos arquivos originais
- Teste cada implementação em diferentes navegadores
- Valide responsividade mobile primeiro
- Mantenha consistência com o design system atual
- Documente mudanças significativas