# Spec — Frontend Prontus v1

**Data:** 2026-05-10
**Baseado em:** docs/prd.md

---

## Resumo

Implementação completa do frontend do Prontus em HTML, CSS e JavaScript puro, organizado em 4 módulos independentes (totem, kds, display, admin) que se comunicam com uma API PHP via fetch. Enquanto o backend não existe, todos os dados são servidos por arquivos JSON mockados em `src/shared/mock/`. A autenticação de KDS e Admin é feita via sessão PHP server-side (o frontend só envia o formulário POST e redireciona conforme resposta).

---

## Arquivos a criar

| Arquivo | Ação | O que faz |
|---|---|---|
| `src/shared/css/variables.css` | CRIAR | Tokens de design: cores, tipografia, espaçamento |
| `src/shared/css/reset.css` | CRIAR | Normalização cross-browser |
| `src/shared/css/components.css` | CRIAR | Componentes reutilizáveis: botão, card, badge, modal, spinner |
| `src/shared/js/api.js` | CRIAR | Módulo de comunicação com a API (fetch + fallback mock) |
| `src/shared/js/utils.js` | CRIAR | Formatadores de moeda, data e hora |
| `src/shared/mock/cardapio.json` | CRIAR | Mock: categorias e produtos com extras e restrições |
| `src/shared/mock/pedidos.json` | CRIAR | Mock: pedidos com status e itens |
| `src/shared/mock/senhas.json` | CRIAR | Mock: senhas em preparo e prontas |
| `src/shared/mock/metricas.json` | CRIAR | Mock: totais e tempo médio do dia |
| `src/totem/pages/index.html` | CRIAR | Cardápio: listagem de categorias e produtos |
| `src/totem/pages/produto.html` | CRIAR | Detalhe do produto + personalização |
| `src/totem/pages/carrinho.html` | CRIAR | Resumo do pedido + botão finalizar |
| `src/totem/pages/pagamento.html` | CRIAR | Seleção do método de pagamento |
| `src/totem/pages/senha.html` | CRIAR | Confirmação com número da senha gerada |
| `src/totem/assets/css/totem.css` | CRIAR | Estilos específicos do totem (botões extra-grandes) |
| `src/totem/assets/js/cardapio.js` | CRIAR | Renderização do cardápio e filtros |
| `src/totem/assets/js/carrinho.js` | CRIAR | Lógica do carrinho (estado em sessionStorage) |
| `src/totem/assets/js/pagamento.js` | CRIAR | Fluxo de pagamento e geração de senha |
| `src/kds/pages/login.html` | CRIAR | Formulário de login (cozinha ou admin) |
| `src/kds/pages/index.html` | CRIAR | Painel de pedidos em tempo real |
| `src/kds/assets/css/kds.css` | CRIAR | Estilos do KDS: cards de pedido, badges de status |
| `src/kds/assets/js/painel.js` | CRIAR | Renderização e atualização dos pedidos |
| `src/kds/assets/js/polling.js` | CRIAR | Polling a cada 2s na API de pedidos |
| `src/display/pages/index.html` | CRIAR | Tela pública de senhas para TV |
| `src/display/assets/css/display.css` | CRIAR | Layout fullscreen para TV, fontes grandes |
| `src/display/assets/js/display.js` | CRIAR | Polling a cada 2s e atualização da tela |
| `src/admin/pages/login.html` | CRIAR | Login admin com form POST |
| `src/admin/pages/dashboard.html` | CRIAR | Métricas do dia |
| `src/admin/pages/produtos.html` | CRIAR | Listagem de produtos com ações |
| `src/admin/pages/produto-form.html` | CRIAR | Formulário criar/editar produto |
| `src/admin/pages/categorias.html` | CRIAR | Listagem de categorias |
| `src/admin/pages/categoria-form.html` | CRIAR | Formulário criar/editar categoria |
| `src/admin/pages/adicionais.html` | CRIAR | Adicionais e opções de personalização |
| `src/admin/pages/restricoes.html` | CRIAR | Configuração de restrições alimentares |
| `src/admin/assets/css/admin.css` | CRIAR | Estilos do painel admin: sidebar, tabelas, formulários |
| `src/admin/assets/js/admin.js` | CRIAR | Lógica geral: sidebar, logout, proteção de rota |
| `src/admin/assets/js/dashboard.js` | CRIAR | Carrega e exibe métricas |
| `src/admin/assets/js/produtos.js` | CRIAR | CRUD de produtos via API |
| `src/admin/assets/js/categorias.js` | CRIAR | CRUD de categorias via API |
| `src/admin/assets/js/adicionais.js` | CRIAR | CRUD de adicionais e personalizações |

---

## Fase 1 — Design System (shared)

### [arquivo: src/shared/css/variables.css]
**Ação:** CRIAR

**Mudanças:**
- [ ] Definir variáveis de cor primária: `--color-primary: #FF6B35` (laranja)
- [ ] Definir cor secundária: `--color-secondary: #2D3047` (azul-escuro)
- [ ] Definir fundo: `--color-bg: #F8F4F0` (off-white quente)
- [ ] Definir cores de status: `--color-success: #27AE60`, `--color-warning: #F39C12`, `--color-danger: #E74C3C`, `--color-info: #3498DB`
- [ ] Definir cor de texto: `--color-text: #1A1A2E`, `--color-text-muted: #6C757D`
- [ ] Definir fonte: `--font-family: 'Inter', system-ui, sans-serif`
- [ ] Definir escala de tamanhos: `--text-sm: 0.875rem`, `--text-base: 1rem`, `--text-lg: 1.25rem`, `--text-xl: 1.5rem`, `--text-2xl: 2rem`, `--text-3xl: 3rem`
- [ ] Definir espaçamento: `--space-1` a `--space-8` (4px a 32px em múltiplos de 4)
- [ ] Definir raio de borda: `--radius-sm: 6px`, `--radius-md: 12px`, `--radius-lg: 20px`
- [ ] Definir sombras: `--shadow-sm`, `--shadow-md`, `--shadow-lg`
- [ ] Definir transição padrão: `--transition: 0.2s ease`
- [ ] Definir alturas de botão: `--btn-height-sm: 40px`, `--btn-height-md: 56px`, `--btn-height-lg: 80px` (totem)

**Trecho de referência:**
```css
:root {
  --color-primary: #FF6B35;
  --color-secondary: #2D3047;
  --color-bg: #F8F4F0;
  --color-success: #27AE60;
  --color-warning: #F39C12;
  --color-danger: #E74C3C;
  --color-text: #1A1A2E;
  --color-text-muted: #6C757D;
  --font-family: 'Inter', system-ui, sans-serif;
  --space-1: 4px; --space-2: 8px; --space-3: 12px; --space-4: 16px;
  --space-5: 20px; --space-6: 24px; --space-7: 28px; --space-8: 32px;
  --radius-sm: 6px; --radius-md: 12px; --radius-lg: 20px;
  --btn-height-md: 56px; --btn-height-lg: 80px;
  --transition: 0.2s ease;
}
```

---

### [arquivo: src/shared/css/reset.css]
**Ação:** CRIAR

**Mudanças:**
- [ ] Box-sizing border-box global
- [ ] Margin/padding zerados
- [ ] `font-family` herdada de `:root`
- [ ] Imagens responsivas por padrão (`max-width: 100%`)
- [ ] Remover estilo padrão de listas e botões

---

### [arquivo: src/shared/css/components.css]
**Ação:** CRIAR

**Mudanças:**
- [ ] Classe `.btn` base: height = `var(--btn-height-md)`, padding horizontal, border-radius, cursor pointer, transition
- [ ] Variantes: `.btn-primary` (fundo `--color-primary`, texto branco), `.btn-secondary`, `.btn-danger`, `.btn-ghost`
- [ ] Variante de tamanho: `.btn-lg` usa `--btn-height-lg` (para totem)
- [ ] Classe `.card`: background branco, `--radius-md`, `--shadow-sm`, padding `--space-6`
- [ ] Classe `.badge`: inline-block, bordas arredondadas, padding pequeno, variantes `.badge-success`, `.badge-warning`, `.badge-danger`, `.badge-info`
- [ ] Classe `.spinner`: animação de rotação CSS pura, tamanho 24px e 48px
- [ ] Classe `.modal-overlay`: posição fixed, fundo semi-transparente, flex center
- [ ] Classe `.modal`: card centralizado, max-width 480px, animação fade-in
- [ ] Classe `.alert`: banner de feedback com ícone e variantes de cor
- [ ] Classe `.tag-restricao`: pill colorido para restrições alimentares (glúten, vegetariano, etc.)

---

### [arquivo: src/shared/js/api.js]
**Ação:** CRIAR

**Mudanças:**
- [ ] Exportar constante `API_BASE` lendo de `window.PRONTUS_API_URL` ou default `'/api'`
- [ ] Criar função `fetchAPI(endpoint, options)` que faz fetch com headers JSON e trata erros HTTP
- [ ] Criar função `getMock(path)` que faz fetch de `src/shared/mock/${path}.json`
- [ ] Criar flag `USE_MOCK = true` (trocar para false quando backend estiver pronto)
- [ ] Exportar funções: `getCardapio()`, `getPedidos()`, `getSenhas()`, `getMetricas()`
- [ ] Exportar funções de mutação: `criarPedido(dados)`, `atualizarStatusPedido(id, status)`
- [ ] Exportar CRUD: `getProdutos()`, `salvarProduto(dados)`, `deletarProduto(id)`, `getCategorias()`, `salvarCategoria(dados)`, `deletarCategoria(id)`

**Trecho de referência:**
```js
const USE_MOCK = true;
const API_BASE = window.PRONTUS_API_URL || '/api';

async function fetchAPI(endpoint, options = {}) {
  const res = await fetch(`${API_BASE}${endpoint}`, {
    headers: { 'Content-Type': 'application/json', ...options.headers },
    ...options,
  });
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  return res.json();
}

async function getMock(name) {
  const res = await fetch(`/src/shared/mock/${name}.json`);
  return res.json();
}

export async function getCardapio() {
  return USE_MOCK ? getMock('cardapio') : fetchAPI('/cardapio');
}
```

---

### [arquivo: src/shared/js/utils.js]
**Ação:** CRIAR

**Mudanças:**
- [ ] `formatCurrency(value)` — formata número para `R$ 00,00`
- [ ] `formatTime(isoString)` — formata ISO para `HH:MM`
- [ ] `minutesAgo(isoString)` — retorna quantos minutos atrás
- [ ] `generateSenha()` — retorna número aleatório 3 dígitos (ex: `#042`) para uso no mock

---

### [arquivo: src/shared/mock/cardapio.json]
**Ação:** CRIAR

**Estrutura:**
```json
{
  "categorias": [
    { "id": 1, "nome": "Lanches", "icone": "🍔" },
    { "id": 2, "nome": "Bebidas", "icone": "🥤" },
    { "id": 3, "nome": "Sobremesas", "icone": "🍨" }
  ],
  "produtos": [
    {
      "id": 1,
      "categoriaId": 1,
      "nome": "X-Burguer",
      "descricao": "Hambúrguer artesanal com queijo e alface",
      "preco": 24.90,
      "imagem": null,
      "restricoes": [],
      "extras": [
        { "id": 1, "nome": "Bacon extra", "preco": 4.00 },
        { "id": 2, "nome": "Queijo duplo", "preco": 3.00 }
      ],
      "remocoes": ["Alface", "Tomate", "Cebola"]
    }
  ]
}
```
- [ ] Criar ao menos 3 categorias
- [ ] Criar ao menos 8 produtos distribuídos nas categorias
- [ ] Incluir ao menos 2 produtos com restrições alimentares (`"restricoes": ["sem-gluten", "vegetariano"]`)
- [ ] Cada produto com 2-3 extras e 2-3 opções de remoção

---

### [arquivo: src/shared/mock/pedidos.json]
**Ação:** CRIAR

**Estrutura:**
```json
[
  {
    "id": 1,
    "senha": "#001",
    "status": "em-preparo",
    "horario": "2026-05-10T12:05:00",
    "itens": [
      {
        "produto": "X-Burguer",
        "quantidade": 1,
        "extras": ["Bacon extra"],
        "remocoes": ["Cebola"],
        "restricoes": []
      }
    ]
  }
]
```
- [ ] Criar ao menos 5 pedidos com status variados: `recebido`, `em-preparo`, `pronto`, `finalizado`
- [ ] Incluir ao menos 1 pedido com restrição alimentar

---

### [arquivo: src/shared/mock/senhas.json]
**Ação:** CRIAR

**Estrutura:**
```json
{
  "em_preparo": ["#001", "#003", "#005"],
  "prontas": ["#002", "#004"]
}
```

---

### [arquivo: src/shared/mock/metricas.json]
**Ação:** CRIAR

**Estrutura:**
```json
{
  "total_pedidos": 47,
  "tempo_medio_minutos": 8,
  "volume_vendas": 1243.70,
  "pedidos_por_hora": [3, 5, 8, 12, 9, 6, 4]
}
```

---

## Fase 2 — Admin

### [arquivo: src/admin/pages/login.html]
**Ação:** CRIAR

**Mudanças:**
- [ ] Layout centralizado vertical e horizontal, fundo `--color-secondary`
- [ ] Logo + nome "Prontus Admin" no topo
- [ ] Formulário com campos: `email` e `senha` (input type password)
- [ ] Botão `.btn.btn-primary` "Entrar" com largura total
- [ ] `action="/api/auth/login"` `method="POST"`, `name="redirect"` hidden com valor `/admin/pages/dashboard.html`
- [ ] Mensagem de erro renderizada via query param `?erro=1`

---

### [arquivo: src/admin/pages/dashboard.html]
**Ação:** CRIAR

**Mudanças:**
- [ ] Sidebar à esquerda com links para todas as seções (usar componente sidebar do `admin.js`)
- [ ] Grid de 4 cards no topo: Total de pedidos, Tempo médio, Volume de vendas, Pedidos em aberto
- [ ] Gráfico simples de barras de pedidos por hora usando `<canvas>` e JS puro (sem biblioteca)
- [ ] Tabela dos 5 últimos pedidos com colunas: Senha, Itens, Status, Horário

---

### [arquivo: src/admin/pages/produtos.html]
**Ação:** CRIAR

**Mudanças:**
- [ ] Header com título "Produtos" e botão "+ Novo Produto" (link para `produto-form.html`)
- [ ] Campo de busca por nome
- [ ] Tabela com colunas: Nome, Categoria, Preço, Restrições, Ações (Editar / Excluir)
- [ ] Ação Excluir abre modal de confirmação antes de chamar `deletarProduto(id)`
- [ ] Ação Editar navega para `produto-form.html?id={id}`

---

### [arquivo: src/admin/pages/produto-form.html]
**Ação:** CRIAR

**Mudanças:**
- [ ] Título dinâmico: "Novo Produto" ou "Editar Produto" conforme query param `?id`
- [ ] Campos: Nome (text), Descrição (textarea), Preço (number step 0.01), Categoria (select populado via API)
- [ ] Seção "Restrições Alimentares": checkboxes (Sem Glúten, Vegetariano, Vegano, Sem Lactose)
- [ ] Seção "Extras": lista dinâmica — cada item tem Nome e Preço, botão "+ Adicionar extra"
- [ ] Seção "Remoções possíveis": lista dinâmica de ingredientes removíveis
- [ ] Botões: "Salvar" (chama `salvarProduto`) e "Cancelar" (volta para `produtos.html`)
- [ ] Se `?id` existe, pré-preenche formulário chamando `getProdutoById(id)`

---

### [arquivo: src/admin/pages/categorias.html]
**Ação:** CRIAR

**Mudanças:**
- [ ] Mesmo padrão de `produtos.html`: header, busca, tabela com Editar/Excluir
- [ ] Colunas: Nome, Ícone (emoji), Qtd de produtos, Ações

---

### [arquivo: src/admin/pages/categoria-form.html]
**Ação:** CRIAR

**Mudanças:**
- [ ] Campos: Nome (text), Ícone (text, input emoji)
- [ ] Botões Salvar e Cancelar

---

### [arquivo: src/admin/pages/adicionais.html]
**Ação:** CRIAR

**Mudanças:**
- [ ] Listagem de todos os adicionais cadastrados com produto associado
- [ ] Tabela: Nome, Preço, Produto, Ações
- [ ] Botão "+ Novo Adicional" abre modal inline com campos Nome, Preço, Produto (select)

---

### [arquivo: src/admin/pages/restricoes.html]
**Ação:** CRIAR

**Mudanças:**
- [ ] Lista das restrições disponíveis no sistema com toggle ativo/inativo
- [ ] Restrições padrão: Sem Glúten, Vegetariano, Vegano, Sem Lactose, Sem Amendoim
- [ ] Botão "+ Nova Restrição" com campo nome e cor do badge

---

### [arquivo: src/admin/assets/js/admin.js]
**Ação:** CRIAR

**Mudanças:**
- [ ] Renderizar sidebar com links ativos (destacar item atual via `window.location.pathname`)
- [ ] Função `protegerRota()`: chama `GET /api/auth/me`; se 401, redireciona para `login.html`
- [ ] Chamar `protegerRota()` no topo de cada página admin (via `<script>` inline ou DOMContentLoaded)
- [ ] Botão de logout chama `POST /api/auth/logout` e redireciona para `login.html`
- [ ] No modo mock, `protegerRota()` sempre passa (sem redirecionamento)

---

### [arquivo: src/admin/assets/css/admin.css]
**Ação:** CRIAR

**Mudanças:**
- [ ] Layout de duas colunas: sidebar fixa 240px + área de conteúdo flex-grow
- [ ] Sidebar com fundo `--color-secondary`, links em branco, item ativo com destaque laranja
- [ ] Tabelas: linhas alternadas, hover, header escuro
- [ ] Formulários: labels acima dos inputs, inputs full-width, foco com borda `--color-primary`
- [ ] Responsivo: sidebar colapsável em telas < 768px

---

## Fase 3 — Totem

### [arquivo: src/totem/pages/index.html]
**Ação:** CRIAR

**Mudanças:**
- [ ] Header com logo Prontus + ícone do carrinho com contador de itens
- [ ] Barra de filtros: botões `.btn-lg` para cada categoria (carregados dinamicamente)
- [ ] Botão especial "Filtrar por restrição" que abre dropdown com as restrições disponíveis
- [ ] Grid de cards de produtos: imagem (ou placeholder), nome, preço, botão "+ Adicionar"
- [ ] Clicar em "+ Adicionar" redireciona para `produto.html?id={id}`
- [ ] Se carrinho tem itens, mostrar barra flutuante no rodapé: "X itens — Ver pedido" → `carrinho.html`

---

### [arquivo: src/totem/pages/produto.html]
**Ação:** CRIAR

**Mudanças:**
- [ ] Botão voltar para `index.html`
- [ ] Nome, descrição e preço base do produto
- [ ] Seção "Remover ingredientes": chips clicáveis (toggle selecionado/removido)
- [ ] Seção "Adicionar extras": lista com nome, preço e botão + / - para quantidade
- [ ] Contador de quantidade do produto (mínimo 1)
- [ ] Preço total calculado em tempo real = (preço base + extras) × quantidade
- [ ] Botão "Adicionar ao pedido" salva no carrinho (sessionStorage) e volta para `index.html`
- [ ] Badges de restrição alimentar exibidos abaixo do nome

---

### [arquivo: src/totem/pages/carrinho.html]
**Ação:** CRIAR

**Mudanças:**
- [ ] Listar cada item do carrinho: nome, extras, remoções, quantidade, subtotal
- [ ] Botão editar item (volta para `produto.html?id={id}&editar=1`)
- [ ] Botão remover item (remove do sessionStorage)
- [ ] Total geral fixado no rodapé
- [ ] Botão "Finalizar pedido" → `pagamento.html`
- [ ] Botão "Continuar comprando" → `index.html`
- [ ] Carrinho vazio: mensagem + botão para voltar ao cardápio

---

### [arquivo: src/totem/pages/pagamento.html]
**Ação:** CRIAR

**Mudanças:**
- [ ] Resumo do valor total a pagar
- [ ] 3 opções grandes (`.btn-lg`) com ícone: PIX, Cartão, Dinheiro no caixa
- [ ] Ao selecionar PIX: mostrar QR code placeholder + instrução
- [ ] Ao selecionar Cartão: mostrar instrução "Aproxime ou insira o cartão"
- [ ] Ao selecionar Dinheiro: mostrar instrução "Dirija-se ao caixa com sua senha"
- [ ] Botão "Confirmar pagamento" chama `criarPedido()` e redireciona para `senha.html?senha={numero}`
- [ ] No mock, `criarPedido()` retorna senha gerada por `generateSenha()`

---

### [arquivo: src/totem/pages/senha.html]
**Ação:** CRIAR

**Mudanças:**
- [ ] Exibir número da senha em destaque: fonte `--text-3xl`, cor `--color-primary`
- [ ] Mensagem: "Seu pedido foi recebido! Aguarde ser chamado."
- [ ] Itens do pedido listados em resumo
- [ ] Botão "Novo pedido" limpa sessionStorage e volta para `index.html`
- [ ] Auto-redirect para `index.html` após 30 segundos (com contagem regressiva)

---

### [arquivo: src/totem/assets/css/totem.css]
**Ação:** CRIAR

**Mudanças:**
- [ ] Fundo escuro semitransparente para header
- [ ] Cards de produto com imagem 200px, hover elevado, bordas arredondadas
- [ ] Botões `.btn-lg` com mínimo 80px de altura e fonte `--text-lg`
- [ ] Barra de carrinho flutuante: fixed bottom, fundo `--color-primary`, texto branco
- [ ] Layout otimizado para touch: sem seleção de texto, cursor pointer em tudo clicável
- [ ] Fonte base de `18px` (maior que padrão para uso em totem)

---

### [arquivo: src/totem/assets/js/cardapio.js]
**Ação:** CRIAR

**Mudanças:**
- [ ] No DOMContentLoaded: chamar `getCardapio()` e renderizar categorias e produtos
- [ ] Função `filtrarPorCategoria(id)`: filtra `produtos` do estado local e re-renderiza grid
- [ ] Função `filtrarPorRestricao(restricoes[])`: filtra produtos que têm todas as restrições selecionadas
- [ ] Atualizar contador do carrinho no header a partir do sessionStorage

---

### [arquivo: src/totem/assets/js/carrinho.js]
**Ação:** CRIAR

**Mudanças:**
- [ ] Estado do carrinho salvo em `sessionStorage` como JSON sob chave `prontus_carrinho`
- [ ] Exportar: `adicionarItem(produto, extras, remocoes, quantidade)`, `removerItem(index)`, `getCarrinho()`, `limparCarrinho()`
- [ ] `calcularTotal()`: soma `(precoBase + somatorioExtras) * quantidade` de cada item

---

### [arquivo: src/totem/assets/js/pagamento.js]
**Ação:** CRIAR

**Mudanças:**
- [ ] Ao clicar numa opção de pagamento: destacar opção selecionada e mostrar painel de instrução
- [ ] Ao clicar "Confirmar": montar payload com `getCarrinho()` e chamar `criarPedido(payload)`
- [ ] Em caso de sucesso: navegar para `senha.html?senha={numero}`
- [ ] Em caso de erro: exibir `.alert` de erro sem perder o carrinho

---

## Fase 4 — KDS (Cozinha)

### [arquivo: src/kds/pages/login.html]
**Ação:** CRIAR

**Mudanças:**
- [ ] Mesmo layout do login admin, mas com título "Acesso à Cozinha"
- [ ] `action="/api/auth/login"` com redirect para `kds/pages/index.html`

---

### [arquivo: src/kds/pages/index.html]
**Ação:** CRIAR

**Mudanças:**
- [ ] Header fixo: logo Prontus + label "Cozinha" + botão Sair + relógio em tempo real
- [ ] Layout em colunas por status: "Recebido" | "Em Preparo" | "Pronto"
- [ ] Cada pedido é um card `.card` com:
  - Número da senha em destaque
  - Horário de entrada + tempo decorrido (ex: "há 3 min")
  - Lista de itens com extras e remoções
  - Badges `.tag-restricao` para cada restrição alimentar (destaque visual vermelho/amarelo)
  - Botão de ação para avançar status
- [ ] Botões de ação por status:
  - "Recebido" → botão "Iniciar preparo" → move para "Em Preparo"
  - "Em Preparo" → botão "Marcar como pronto" → move para "Pronto"
  - "Pronto" → botão "Finalizar" → remove da tela
- [ ] Pedidos ordenados por horário de entrada (mais antigo primeiro)
- [ ] Se um pedido está há mais de 10 minutos em "Em Preparo", destacar card com borda vermelha

---

### [arquivo: src/kds/assets/js/polling.js]
**Ação:** CRIAR

**Mudanças:**
- [ ] Exportar função `iniciarPolling(callback, intervalMs = 2000)` que chama `getPedidos()` a cada `intervalMs` ms
- [ ] Só re-renderizar se os dados mudaram (comparar JSON.stringify do estado anterior)
- [ ] Exportar `pararPolling()` para limpar o interval

---

### [arquivo: src/kds/assets/js/painel.js]
**Ação:** CRIAR

**Mudanças:**
- [ ] No DOMContentLoaded: chamar `iniciarPolling(renderizarPedidos)`
- [ ] `renderizarPedidos(pedidos)`: distribui pedidos nas 3 colunas por status
- [ ] `avancarStatus(id)`: chama `atualizarStatusPedido(id, proximoStatus)` e aguarda próximo poll
- [ ] Relógio: `setInterval` de 1s atualizando texto do header com hora atual
- [ ] No modo mock: simular mudança de status localmente sem chamar API

---

### [arquivo: src/kds/assets/css/kds.css]
**Ação:** CRIAR

**Mudanças:**
- [ ] Layout de 3 colunas iguais, altura da viewport (`100vh`), overflow-y scroll por coluna
- [ ] Header de cada coluna com cor distinta: Recebido `--color-info`, Em Preparo `--color-warning`, Pronto `--color-success`
- [ ] Cards: padding generoso, espaçamento entre itens, sombra suave
- [ ] Badge de alerta de tempo: fundo vermelho piscando (CSS animation `blink`)
- [ ] `.tag-restricao`: pill com fundo amarelo-âmbar, texto escuro, ícone de alerta ⚠

---

## Fase 5 — Display (Tela Pública)

### [arquivo: src/display/pages/index.html]
**Ação:** CRIAR

**Mudanças:**
- [ ] `<meta>` viewport para TV (sem zoom)
- [ ] Layout fullscreen: duas colunas iguais
  - Coluna esquerda: "EM PREPARO" — lista de senhas
  - Coluna direita: "PRONTAS PARA RETIRADA" — lista de senhas
- [ ] Cada senha exibida como pill grande, fonte mínima 72px
- [ ] Botão fullscreen no canto (chama `document.documentElement.requestFullscreen()`)
- [ ] Header: logo Prontus + relógio em tempo real
- [ ] Fundo escuro (`--color-secondary`), texto claro

---

### [arquivo: src/display/assets/js/display.js]
**Ação:** CRIAR

**Mudanças:**
- [ ] `iniciarPolling` via `getSenhas()` a cada 2s
- [ ] Comparar com estado anterior; se mudou, animar saída/entrada das senhas novas
- [ ] Animação de entrada: nova senha aparece com `keyframes` de fade-in + scale
- [ ] Animação de "chamada": quando senha aparece em "Prontas", piscar 3 vezes
- [ ] Relógio em tempo real no header (`setInterval` 1s)

---

### [arquivo: src/display/assets/css/display.css]
**Ação:** CRIAR

**Mudanças:**
- [ ] `body` fullscreen: `width: 100vw; height: 100vh; overflow: hidden`
- [ ] Fundo `--color-secondary`
- [ ] Fonte base 36px, senhas com 72px+
- [ ] Coluna "Prontas": fundo com leve destaque em verde
- [ ] Keyframe `@keyframes fadeInScale`: de `opacity 0 scale(0.8)` para `opacity 1 scale(1)`
- [ ] Keyframe `@keyframes blink`: alterna entre opacidade normal e `--color-success`

---

## Critérios de sucesso

### Verificação automática
- [ ] Nenhum erro no console do navegador ao abrir qualquer página
- [ ] Todos os `fetch` de mock retornam 200 (rodar via servidor local, não `file://`)
- [ ] Carrinho persiste ao navegar entre páginas do totem

### Verificação manual

**Totem:**
- [ ] Navegar categorias e ver produtos filtrados
- [ ] Filtrar por restrição alimentar e ver apenas produtos compatíveis
- [ ] Adicionar produto com extra, remover ingrediente, alterar quantidade
- [ ] Ver total atualizado em tempo real na tela de produto
- [ ] Finalizar pedido e ver número da senha gerada
- [ ] Auto-redirect após 30s na tela de senha

**KDS:**
- [ ] Ver 3 colunas de pedidos carregadas via mock
- [ ] Avançar um pedido de "Recebido" → "Em Preparo" → "Pronto" → desaparece
- [ ] Pedido com restrição alimentar tem badge destacado
- [ ] Pedido antigo tem borda vermelha de alerta

**Display:**
- [ ] Senhas em preparo e prontas exibidas em fontes grandes
- [ ] Botão fullscreen funciona
- [ ] Nova senha aparece com animação

**Admin:**
- [ ] Dashboard exibe 4 métricas e gráfico de barras
- [ ] Listar, criar, editar e excluir produto (com confirmação de exclusão)
- [ ] Listar, criar, editar e excluir categoria

---

## Riscos e dependências

| Risco | Impacto | Mitigação |
|---|---|---|
| Páginas abertas via `file://` quebram fetch | Bloqueante | Usar servidor local: `python3 -m http.server 8080` |
| Auth real depende do backend PHP | KDS e Admin ficam sem proteção real | `protegerRota()` retorna true no modo mock |
| Polling simultâneo em KDS + Display pode gerar muitas requisições | Performance | Compartilhar estado via `BroadcastChannel` se mesmo navegador (v2) |
| Sem bundler: imports ES module exigem servidor com MIME correto | Dev e prod | Documentar no README que é necessário servir os arquivos via HTTP |
| `sessionStorage` é por aba: carrinho perdido se cliente abrir nova aba | UX do totem | Totem roda em aba única (uso normal); sem impacto prático |
