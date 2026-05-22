# Changelog

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/lang/pt-BR/).

## [Unreleased]

### Adicionado
- Novo sistema de relatórios e exportação (PDF e Excel)
  - Relatórios de listagem profissional para Participantes e Rituais
  - Layout A4 Paisagem para PDFs com cabeçalhos azuis e logo centralizado
  - Exportação Excel com bordas cinza claro (1.0pt) e alinhamento à direita
  - Botão dropdown de exportação unificado em ambas as listagens principais
  - Suporte a filtros dinâmicos (busca, data, etc.) nas exportações de lista
  - Rodapé informativo com data e hora da geração do relatório
- Novas rotas amigáveis para relatórios no .htaccess
  - `/participantes/relatorio/pdf` e `/participantes/relatorio/excel`
  - `/rituais/relatorio/pdf` e `/rituais/relatorio/excel`
- Integração de `relatorios.js` para gerenciamento global de exportações

### Modificado
- Relatórios individuais (Excel) de Participantes e Rituais
  - Seções de dados agora ocupam a largura total da tabela (colspan)
  - Alinhamento de todos os valores à direita para melhor legibilidade
  - Padronização de bordas e estilos com os relatórios de listagem

- Sistema de cópia automática de dados entre inscrições
  - Ao adicionar ritual/participante, copia dados da última inscrição salva
  - Ao salvar uma inscrição, copia dados para outras inscrições não salvas do mesmo participante
  - Mensagem informativa indicando origem dos dados copiados
- Lógica de "primeira vez" para campos de inscrição
  - Campos editáveis na primeira inscrição com mensagem informativa
  - Campos bloqueados com "Não" em inscrições subsequentes quando já houve "Sim"
  - Mensagens contextuais explicando o comportamento dos campos
- Notificação visual de campos obrigatórios
  - Bolinha vermelha desaparece apenas quando todos os campos obrigatórios estão preenchidos
  - Validação inclui campos condicionais (nome_doenca, nome_medicao)
- Botão dinâmico Fechar/Salvar na modal de detalhes
  - Botão "Fechar" (vermelho) quando dados foram copiados sem alterações
  - Botão "Salvar" (azul) quando há alterações ou dados não foram copiados
  - Mudança automática ao detectar alterações nos campos
- Modal de confirmação para mudanças não salvas
  - Alerta ao tentar fechar modal com alterações não salvas
  - Integração com sistema de detecção de mudanças não salvas
- Sistema completo de gestão de documentos
  - Upload de imagens e PDFs por participante
  - Compressão automática de imagens
  - Crop flexível de documentos (sem proporção fixa)
  - Suporte para orientação horizontal e vertical
  - Nome personalizado para arquivos (Ficha de inscrição ou outro)
  - Visualização de documentos com PhotoSwipe
  - Download e exclusão de documentos com confirmação
  - Contador de documentos no card do participante
- Status do participante (ativo / inativo / não pode participar)
  - Substitui o controle "Permite vincular a novos rituais"
  - Motivo opcional para inativo; obrigatório para não pode participar
  - Novos cadastros sempre entram como ativo
  - Filtro por status na listagem (padrão: somente ativos)
  - Tags clicáveis na listagem e na visualização com modal de motivo
  - Participantes não ativos não aparecem na busca ao adicionar ao ritual
  - Validação no servidor ao vincular participante ↔ ritual
  - Migration 4 com conversão de registros legados (`Não` → não pode participar)
  - Status e motivo nos relatórios individual e de lista (PDF/Excel)
- Zoom dinâmico no PhotoSwipe
  - Zoom máximo calculado dinamicamente baseado no tamanho da imagem
  - Permite zoom até 5x para imagens menores que a viewport
  - Zoom até 2x para imagens maiores
- Nova API `buscar-ultima-inscricao-salva.php`
  - Busca a última inscrição salva de um participante
  - Exclui a inscrição atual da busca
  - Retorna dados copiáveis e informações do ritual de origem
- Novas APIs de documentos
  - `listar_documentos.php` - Lista documentos do participante
  - `baixar_documentos.php` - Download de documentos

### Modificado
- Processamento de assinaturas em PDFs
  - Assinaturas agora são processadas em preto e mais definidas
  - Aplicado resize, grayscale e threshold para melhor qualidade visual
- Função `temDetalhesCompletos()` em visualizar.php
  - Validação completa incluindo campos condicionais
  - Verifica nome_doenca quando doenca_psiquiatrica = "Sim"
  - Verifica nome_medicao quando uso_medicao = "Sim"
- Campos condicionais (nome_doenca, nome_medicao)
  - Sempre editáveis quando doenca_psiquiatrica/uso_medicao = "Sim"
  - Atualização automática do estado após cópia de dados
  - Preservação de valores quando campos estão disabled

### Corrigido
- Campos disabled não sendo enviados no formulário
  - Inclusão manual de valores de campos disabled no FormData
  - Campos nome_doenca e nome_medicao preservados quando disabled
- Cópia de dados para múltiplas inscrições
  - Ao salvar uma inscrição, dados são copiados para outras não salvas
  - Campos primeira_vez preenchidos corretamente baseado em lógica de negócio
  - Notificação desaparece corretamente após recarregar página
- Validação de "primeira vez" considerando apenas inscrições salvas
  - Query ajustada para considerar apenas inscrições com salvo_em IS NOT NULL
  - Evita bloqueio incorreto de campos em inscrições não salvas
- Tratamento de erros PHP em APIs JSON
  - Suppressão de warnings que corrompiam respostas JSON
  - Conversão de strings vazias para NULL em campos ENUM
  - Validação de valores ENUM antes de inserção
- Campos condicionais sempre editáveis quando aplicável
  - nome_doenca editável quando doenca_psiquiatrica = "Sim"
  - nome_medicao editável quando uso_medicao = "Sim"
  - Atualização automática do estado após cópia de dados

### Melhorias Técnicas
- Gerenciamento de estado assíncrono com Promises
- Detecção de mudanças não salvas integrada ao sistema existente
- Atualização condicional de campos baseada em valores atuais
- Melhor tratamento de erros e validações

---

## [Versões Anteriores]

### Histórico de Versões
- As versões anteriores não possuem changelog estruturado
- Para detalhes de versões anteriores, consulte o histórico de commits do Git

