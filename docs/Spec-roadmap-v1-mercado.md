# Spec — Roadmap v1 Comercializável: Alinhamento com o Mercado

**Data:** 2026-05-19  
**Baseado em:** análise completa do código atual + comparação com Goomer Go, TabletMenu, OMIE PDV, iFood para Negócios, Totem Digital  
**Objetivo:** Definir o que falta implementar para o Prontus estar pronto para vender a restaurantes reais

---

## Resumo

O Prontus tem uma base sólida e todos os fluxos principais funcionando (totem → KDS → display → admin). O que falta são **14 lacunas de produto** que dividimos em três prioridades. As P0 são bloqueadoras de venda: sem elas, o sistema não pode ser instalado num restaurante real sem fricção. As P1 são diferenciadoras competitivas. As P2 são desejáveis para versões futuras.

---

## Análise de Lacunas por Módulo

### O que JÁ existe (não reimplementar)
| Módulo | Implementado |
|--------|-------------|
| Admin | Login, dashboard (KPIs + gráfico), produtos CRUD, categorias CRUD, adicionais, restrições, histórico de pedidos (com filtros), usuários CRUD |
| Totem | Cardápio com filtros, detalhe + personalização, carrinho, pagamento (PIX/cartão/dinheiro), confirmação com contagem regressiva |
| KDS | Board Kanban 3 colunas com polling 2s, alertas de atraso, tags de restrição |
| Display | Tela de chamada de senhas com polling |
| Backend | API REST completa cobrindo todos os módulos acima |

---

## Arquivos a criar

| Arquivo | Prioridade | O que é |
|---------|-----------|---------|
| `src/admin/pages/configuracoes.php` | P0 | Configurações gerais do estabelecimento |
| `src/admin/assets/js/configuracoes.js` | P0 | Lógica da página de configurações |
| `src/admin/pages/relatorios.php` | P0 | Relatórios com exportação CSV |
| `src/admin/assets/js/relatorios.js` | P0 | Lógica de relatórios + download CSV |
| `src/totem/pages/idle.php` | P0 | Tela atrativa quando totem está inativo |
| `src/totem/assets/js/idle.js` | P0 | Lógica de detecção de inatividade e idle screen |
| `backend/api/configuracoes.php` | P0 | CRUD de configurações do sistema |
| `backend/db/migrations/004_configuracoes.sql` | P0 | Tabela `configuracoes` no banco |
| `backend/db/migrations/005_relatorios.sql` | P0 | Índices extras para relatórios por período |
| `src/admin/pages/remocoes.php` | P0 | Gestão de remoções por produto (o que pode ser removido) |
| `src/admin/assets/js/remocoes.js` | P0 | CRUD de remoções via API |
| `backend/api/remocoes.php` | P0 | Endpoints de remoções |
| `src/totem/pages/acompanhar.php` | P1 | Acompanhamento de pedido por senha |
| `src/totem/assets/js/acompanhar.js` | P1 | Consulta de status em tempo real |
| `src/admin/pages/combos.php` | P1 | Gestão de combos e promoções |
| `src/admin/assets/js/combos.js` | P1 | CRUD de combos |
| `backend/api/combos.php` | P1 | Endpoints de combos |
| `backend/db/migrations/006_combos.sql` | P1 | Tabelas de combos |
| `src/admin/pages/relatorio-satisfacao.php` | P2 | Relatório de avaliações |
| `backend/api/avaliacoes.php` | P2 | Endpoint de avaliações |
| `backend/db/migrations/007_avaliacoes.sql` | P2 | Tabela de avaliações |

## Arquivos a modificar

| Arquivo | O que muda |
|---------|-----------|
| `src/kds/assets/js/painel.js` | Adicionar alerta sonoro em novos pedidos (P0) |
| `src/admin/assets/js/admin.js` | Adicionar "Configurações" e "Relatórios" ao menu sidebar |
| `src/admin/pages/produto-form.php` | Adicionar upload de imagem (P0) e gestão de remoções inline |
| `src/admin/assets/js/produtos.js` | Integrar preview de imagem + upload |
| `backend/api/produto.php` | Adicionar endpoint de upload de imagem |
| `backend/config/helpers.php` | Rate limiting no login; CORS restritivo (P0 segurança) |
| `backend/api/auth/login.php` | Throttle de tentativas; fix CORS (P0 segurança) |
| `src/kds/pages/login.php` | Fix BUG-01: reabilitar botão em erro (P0 bugfix) |
| `src/admin/pages/login.php` | Fix BUG-01: reabilitar botão em erro (P0 bugfix) |
| `src/kds/assets/js/painel.js` | Fix BUG-02: protegerRota(); Fix BUG-03: logout chama API |
| `src/kds/assets/js/polling.js` | Fix BUG-06: race condition com flag |
| `src/display/assets/js/display.js` | Fix BUG-06: race condition com AbortController |
| `src/totem/assets/js/pagamento.js` | Fix BUG-07: debounce; Fix BUG-08: validar resultado.senha |
| `backend/api/pedidos.php` | Fix BUG-05: transação atômica (já existe beginTransaction) |
| `src/shared/js/api.js` | Adicionar `getConfiguracoes()`, `salvarConfiguracoes()`, `getRelatorio()` |
| `src/display/pages/index.php` | Adicionar suporte a logo e mensagem personalizada |
| `src/totem/pages/index.php` | Adicionar botão "Acompanhar pedido"; link para idle.php |
| `src/totem/pages/senha.php` | Adicionar botão "Acompanhar meu pedido" |

---

## FASE 0 — Bugfixes Críticos (pré-requisito para tudo)

> Estes bugs existem hoje e bloqueiam operação real. Resolver antes de qualquer feature nova.

### [arquivo: src/admin/pages/login.php e src/kds/pages/login.php]
**Ação:** MODIFICAR — Fix BUG-01

**Mudanças:**
- [x] Ao carregar a página, verificar se `window.location.search` contém `?erro=1`
- [x] Se sim, garantir que `btnLogin`/`btnEntrar` está habilitado e com texto original

**Trecho de referência:**
```javascript
// Adicionar logo após DOMContentLoaded nos dois arquivos de login
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('erro')) {
  const btn = document.getElementById('btnLogin') || document.getElementById('btnEntrar');
  if (btn) { btn.disabled = false; btn.textContent = 'Entrar'; }
}
```

---

### [arquivo: src/kds/assets/js/painel.js]
**Ação:** MODIFICAR — Fix BUG-02 + BUG-03

**Mudanças:**
- [x] Importar `checkAuth`, `logout` de `/src/shared/js/api.js`
- [x] No `DOMContentLoaded`, chamar `checkAuth()` e redirecionar para login se não autenticado (igual a `admin.js`)
- [x] Handler de logout: chamar `await logout()` antes de redirecionar (igual ao padrão do admin)

---

### [arquivo: src/kds/assets/js/polling.js]
**Ação:** MODIFICAR — Fix BUG-06

**Mudanças:**
- [x] Adicionar flag `let emAndamento = false`
- [x] No início da função `tick()`, retornar se `emAndamento === true`
- [x] Setar `emAndamento = true` antes do fetch; `emAndamento = false` no finally

---

### [arquivo: src/display/assets/js/display.js]
**Ação:** MODIFICAR — Fix BUG-06 (variante AbortController)

**Mudanças:**
- [x] Declarar `let controller = null` no escopo do módulo
- [x] Antes de cada fetch, fazer `controller?.abort(); controller = new AbortController()`
- [x] Passar `{ signal: controller.signal }` no fetch
- [x] Capturar `AbortError` silenciosamente (não logar como erro)

---

### [arquivo: src/totem/assets/js/pagamento.js]
**Ação:** MODIFICAR — Fix BUG-07 + BUG-08

**Mudanças:**
- [x] Adicionar flag `let processando = false`; verificar e setar no início de `confirmarPagamento()`
- [x] Após `const resultado = await criarPedido(...)`, verificar `if (!resultado?.senha) throw new Error('Senha não retornada')`
- [x] No catch, resetar `processando = false` e reabilitar botão

---

### [arquivo: backend/api/pedidos.php]
**Ação:** MODIFICAR — Fix BUG-05

**Mudanças:**
- [x] Confirmar que `$pdo->beginTransaction()` engloba TODOS os inserts (pedido + itens + extras + remoções + restrições)
- [x] Verificar que o `rollBack()` está no catch do bloco try completo

---

### [arquivo: backend/config/helpers.php]
**Ação:** MODIFICAR — Fix BUG-13 (CORS) + rate limiting básico

**Mudanças:**
- [x] Substituir `Access-Control-Allow-Origin: *` por lista de origens permitidas (domínio do servidor + localhost para dev)
- [x] Criar função `rate_limit_login(string $ip): void` — armazena tentativas em tabela `login_attempts` ou arquivo; bloqueia IP por 15min após 5 falhas em 10min
- [x] Chamar `rate_limit_login()` no topo de `backend/api/auth/login.php`

---

## FASE 1 — P0: Funcionalidades Bloqueadoras de Venda

### 1.1 Alerta Sonoro no KDS

**Contexto de mercado:** 100% das soluções concorrentes (Goomer, TabletMenu) tocam um som ao chegar novo pedido. Em cozinha barulhenta, o operador não olha a tela constantemente.

### [arquivo: src/kds/assets/js/painel.js]
**Ação:** MODIFICAR

**Mudanças:**
- [x] Declarar `let idsConhecidos = new Set()` no escopo do módulo
- [x] Na função `onNovosDados(pedidos)`, antes de renderizar, verificar quais IDs são novos (status `recebido` e não estavam em `idsConhecidos`)
- [x] Se existirem pedidos novos: chamar `tocarAlerta()`
- [x] Atualizar `idsConhecidos` com todos os IDs atuais após render
- [x] Implementar `tocarAlerta()`:
  - Criar `AudioContext` na primeira interação do usuário (política de autoplay do browser)
  - Gerar beep sintético: oscilador 880Hz, 0.3s, gain 0.5, ou usar arquivo `src/kds/assets/sounds/alerta.mp3`
  - Incluir botão "🔔 Som: ON/OFF" no header do KDS para controle pelo operador; persistir preferência em `localStorage`
- [x] Criar arquivo `src/kds/assets/sounds/alerta.mp3` (beep curto, ~0.5s, 44kHz)

**Trecho de referência:**
```javascript
let audioCtx = null;
let somAtivo = localStorage.getItem('kds_som') !== 'false';

function tocarAlerta() {
  if (!somAtivo) return;
  audioCtx = audioCtx || new AudioContext();
  const osc = audioCtx.createOscillator();
  const gain = audioCtx.createGain();
  osc.connect(gain); gain.connect(audioCtx.destination);
  osc.frequency.value = 880;
  gain.gain.setValueAtTime(0.4, audioCtx.currentTime);
  gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.4);
  osc.start(); osc.stop(audioCtx.currentTime + 0.4);
}
```

---

### 1.2 Configurações do Sistema

**Contexto de mercado:** Sem isso, o sistema só serve para um único cliente hardcoded. Para vender para N restaurantes, cada instalação precisa de nome, logo e chave PIX próprios.

### [arquivo: backend/db/migrations/004_configuracoes.sql]
**Ação:** CRIAR

**Mudanças:**
```sql
CREATE TABLE configuracoes (
  chave    VARCHAR(100) PRIMARY KEY,
  valor    TEXT         NOT NULL,
  grupo    VARCHAR(50)  DEFAULT 'geral',
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO configuracoes (chave, valor, grupo) VALUES
  ('nome_estabelecimento', 'Meu Restaurante', 'geral'),
  ('slogan',               'Peça, aguarde, saboreie', 'geral'),
  ('logo_url',             '', 'geral'),
  ('cor_primaria',         '#FF6B35', 'visual'),
  ('cor_secundaria',       '#2D3047', 'visual'),
  ('pix_chave',            '', 'pagamento'),
  ('pix_nome_recebedor',   '', 'pagamento'),
  ('mensagem_display',     'Aguarde sua senha ser chamada!', 'display'),
  ('tempo_alerta_kds',     '10', 'operacao'),
  ('totem_idle_segundos',  '60', 'operacao');
```

### [arquivo: backend/api/configuracoes.php]
**Ação:** CRIAR

**Mudanças:**
- [x] `GET /api/configuracoes` — retorna todas as configs como objeto `{ chave: valor }`; público (cardápio e display precisam de nome/logo)
- [x] `PUT /api/configuracoes` — atualiza múltiplas configs de uma vez; requer `require_admin()`
- [x] Sanitizar `cor_primaria` e `cor_secundaria` como hex válido (`/^#[0-9A-Fa-f]{6}$/`)
- [x] Sanitizar `tempo_alerta_kds` e `totem_idle_segundos` como inteiro positivo

### [arquivo: src/admin/pages/configuracoes.php]
**Ação:** CRIAR

**Mudanças:**
- [x] Página com layout padrão admin (sidebar + topbar)
- [x] Seção "Identidade": campos `nome_estabelecimento`, `slogan`, preview de logo + campo URL `logo_url`
- [x] Seção "Aparência": color pickers para `cor_primaria` e `cor_secundaria` com preview em tempo real
- [x] Seção "Pagamento PIX": campos `pix_chave` (tipo: CPF/CNPJ/email/telefone/chave aleatória) e `pix_nome_recebedor`
- [x] Seção "Operação": número `tempo_alerta_kds` (minutos), número `totem_idle_segundos`
- [x] Seção "Display": textarea `mensagem_display`
- [x] Botão "Salvar configurações" que chama `PUT /api/configuracoes`
- [x] Feedback visual de sucesso/erro

### [arquivo: src/admin/assets/js/configuracoes.js]
**Ação:** CRIAR

**Mudanças:**
- [x] Ao carregar: buscar `GET /api/configuracoes` e preencher todos os campos
- [x] Color pickers: atualizar variável CSS `--color-primary` / `--color-secondary` em tempo real no preview
- [x] Submit: serializar formulário para objeto e chamar `PUT /api/configuracoes`
- [x] Exibir toast de sucesso/erro

### [arquivo: src/admin/assets/js/admin.js]
**Ação:** MODIFICAR

**Mudanças:**
- [x] Adicionar item "Configurações" no array de navegação do sidebar, com ícone ⚙️, apontando para `configuracoes.php`
- [x] Adicionar item "Relatórios" com ícone 📊, apontando para `relatorios.php`
- [x] Adicionar item "Remoções" com ícone ✂️ (dentro de Cardápio), apontando para `remocoes.php`

---

### 1.3 Gestão de Remoções no Admin

**Contexto:** A tabela `remocoes` existe no banco e os dados vão para o totem, mas não há página admin para gerenciar. Hoje só é possível adicionar remoções editando o banco diretamente.

### [arquivo: backend/api/remocoes.php]
**Ação:** CRIAR

**Mudanças:**
- [x] `GET /api/remocoes?produto_id=X` — lista remoções de um produto
- [x] `POST /api/remocoes` — cria remoção; campos: `produto_id`, `nome`; requer `require_admin()`
- [x] `DELETE /api/remocoes/:id` — soft-delete ou delete direto (remoções não ficam em histórico, delete físico é OK aqui)
- [x] Validar que `produto_id` existe antes de inserir

### [arquivo: src/admin/pages/remocoes.php]
**Ação:** CRIAR

**Mudanças:**
- [x] Selector de produto no topo (dropdown buscando `GET /api/produtos`)
- [x] Ao selecionar produto: listar remoções existentes em tabela com botão "Excluir"
- [x] Input + botão "Adicionar remoção" que chama `POST /api/remocoes`
- [x] Layout padrão admin (sidebar + topbar)

### [arquivo: src/admin/pages/produto-form.php]
**Ação:** MODIFICAR

**Mudanças:**
- [x] Adicionar seção "Remoções permitidas" no formulário de produto (após adicionais)
- [x] Listar remoções existentes com botão de excluir
- [x] Input inline para adicionar nova remoção diretamente no form
- [x] Em modo edição (`?id=X`), carregar remoções via `GET /api/remocoes?produto_id=X`

---

### 1.4 Upload de Imagem de Produto

**Contexto de mercado:** Cardápios com foto têm até 30% mais conversão. Todas as soluções concorrentes suportam upload. Atualmente o campo `imagem` é uma URL texto.

### [arquivo: backend/api/produto.php]
**Ação:** MODIFICAR

**Mudanças:**
- [x] Adicionar bloco `POST /api/produtos/:id/imagem` que recebe `multipart/form-data`
- [x] Validar: apenas `image/jpeg`, `image/png`, `image/webp`; tamanho máximo 2MB
- [x] Redimensionar para máximo 800×800px usando `imagecreatefromjpeg` / GD lib (PHP nativo)
- [x] Salvar em `uploads/produtos/{id}.webp` (converter sempre para webp para otimização)
- [x] Atualizar campo `imagem` na tabela `produtos` com o path relativo
- [x] Retornar `{ url: '/uploads/produtos/{id}.webp' }`
- [x] Nginx já deve servir `/uploads/` como diretório estático

### [arquivo: src/admin/pages/produto-form.php]
**Ação:** MODIFICAR

**Mudanças:**
- [x] No campo de imagem: mostrar `<img>` preview se já existe `imagem`; caso contrário, placeholder cinza
- [x] Adicionar `<input type="file" accept="image/*" id="inputImagem">` com botão "Trocar foto"
- [x] Ao selecionar arquivo: mostrar preview local com `FileReader` + URL.createObjectURL
- [x] Ao salvar o produto: se há arquivo novo, fazer upload separado via `POST /api/produtos/:id/imagem` após salvar o produto
- [x] Não mudar o campo `imagem` no payload do PUT (o upload cuida disso separadamente)

---

### 1.5 Relatórios com Exportação CSV

**Contexto de mercado:** Qualquer gestor precisa fechar o caixa, ver o faturamento da semana, exportar para Excel. Sem isso o admin é decorativo para gestão financeira.

### [arquivo: backend/db/migrations/005_relatorios.sql]
**Ação:** CRIAR

**Mudanças:**
```sql
-- Índice composto para queries de relatório por período
ALTER TABLE pedidos ADD INDEX idx_status_horario (status, horario);
ALTER TABLE pedidos ADD INDEX idx_pagamento (pagamento);
```

### [arquivo: backend/api/relatorios.php]
**Ação:** CRIAR — novo endpoint `/api/relatorios`

**Mudanças:**
- [x] Requer `require_admin()`
- [x] Parâmetros GET: `data_inicio`, `data_fim` (obrigatórios), `formato` (`json` padrão, `csv`)
- [x] Retornar objeto com:
  - `resumo`: total_pedidos, total_faturado, ticket_medio, tempo_medio_minutos
  - `por_dia`: array com `{ data, pedidos, faturado }` para cada dia do período
  - `por_forma_pagamento`: array com `{ pagamento, total, percentual }`
  - `produtos_mais_vendidos`: top 10 com `{ produto_nome, quantidade, total_faturado }`
  - `pedidos_por_hora`: distribuição agregada do período (soma por slot de hora)
- [x] Se `formato=csv`: gerar CSV com todos os pedidos do período (campos: senha, horario, status, pagamento, total, itens)
- [x] Header correto para CSV: `Content-Type: text/csv; charset=utf-8` + `Content-Disposition: attachment; filename=relatorio_YYYY-MM-DD.csv`

### [arquivo: src/admin/pages/relatorios.php]
**Ação:** CRIAR

**Mudanças:**
- [x] Filtros no topo: `data_inicio`, `data_fim` (default: últimos 7 dias), botão "Gerar relatório"
- [x] Seção de KPIs: 4 cards (total pedidos, faturamento, ticket médio, tempo médio)
- [x] Gráfico de barras por dia (reutilizar função `renderChart` de `dashboard.js` ou extrair para shared)
- [x] Tabela "Formas de pagamento" com percentual
- [x] Tabela "Top 10 produtos mais vendidos"
- [x] Botão "Exportar CSV" que abre `GET /api/relatorios?...&formato=csv` em nova aba

### [arquivo: src/admin/assets/js/relatorios.js]
**Ação:** CRIAR

**Mudanças:**
- [x] Importar `getRelatorio` de `api.js`
- [x] Ao clicar "Gerar relatório": buscar dados e renderizar todas as seções
- [x] Botão CSV: montar URL com query params e chamar `window.open(url, '_blank')`
- [x] Estado vazio: mostrar "Selecione um período e clique em Gerar relatório"

---

### 1.6 Tela Idle do Totem

**Contexto de mercado:** Totem parado parece quebrado. Soluções como Goomer Go mostram promoções, logo e "Toque para começar" quando ocioso. Essencial para lançamento em local público.

### [arquivo: src/totem/pages/idle.php]
**Ação:** CRIAR

**Mudanças:**
- [x] Tela fullscreen com fundo na `--color-primary` do estabelecimento
- [x] Logo do estabelecimento (de `configuracoes.logo_url`) centralizado
- [x] Nome do estabelecimento em fonte grande
- [x] Texto pulsante: "Toque para fazer seu pedido"
- [x] Animação CSS suave (fade in/out ou pulso no texto)
- [x] Ao qualquer toque/clique: redirecionar para `index.php`
- [x] Opcional: exibir últimos produtos em destaque ou promoção ativa

### [arquivo: src/totem/assets/js/idle.js]
**Ação:** CRIAR

**Mudanças:**
- [x] Carregar `GET /api/configuracoes` para preencher logo e nome
- [x] Handler de toque: redirecionar para `index.php`

### [arquivo: src/totem/pages/index.php]
**Ação:** MODIFICAR

**Mudanças:**
- [x] Adicionar script que detecta inatividade: após `N` segundos sem interação (onde N vem de `configuracoes.totem_idle_segundos`, default 60), redirecionar para `idle.php`
- [x] Resetar timer a cada `touchstart`, `click`, `keydown`
- [x] Implementar como módulo separado `idle-timer.js` para reuso nas demais páginas do totem

---

## FASE 2 — P1: Diferenciadoras Competitivas

### 2.1 Acompanhamento de Pedido por Senha (Totem)

**Contexto:** Cliente quer saber se seu pedido está pronto sem precisar olhar o display. Goomer Go e TabletMenu têm essa funcionalidade.

### [arquivo: src/totem/pages/acompanhar.php]
**Ação:** CRIAR

**Mudanças:**
- [ ] Campo de input grande para digitar o número da senha (ex: `042`)
- [ ] Botão "Consultar"
- [ ] Exibir status atual do pedido com ícone e cor de acordo com o status
- [ ] Se status `pronto`: exibir destaque "🎉 Seu pedido está pronto! Retire no balcão."
- [ ] Polling a cada 5s para atualizar automaticamente se a página estiver aberta
- [ ] Link "Fazer novo pedido" no rodapé

### [arquivo: src/totem/assets/js/acompanhar.js]
**Ação:** CRIAR

**Mudanças:**
- [ ] Buscar `GET /api/pedidos/por-senha?senha=XXX` (novo endpoint)
- [ ] Polling com flag (sem race condition)
- [ ] Parar polling ao navegar para outra página

### [arquivo: backend/api/pedidos.php]
**Ação:** MODIFICAR

**Mudanças:**
- [ ] Adicionar branch `GET` com parâmetro `?senha=XXX` que retorna apenas `{ id, senha, status, horario }` do pedido mais recente com aquela senha no dia
- [ ] Não requer autenticação (público — cliente acessa)

### [arquivo: src/totem/pages/senha.php]
**Ação:** MODIFICAR

**Mudanças:**
- [ ] Adicionar botão "📱 Acompanhar meu pedido" que leva para `acompanhar.php?senha=XXX`

---

### 2.2 Personalização Visual do Display

**Contexto:** A tela de chamada de senhas fica em TV visível para todos. Ter logo e mensagem do restaurante é requisito comercial básico.

### [arquivo: src/display/pages/index.php]
**Ação:** MODIFICAR

**Mudanças:**
- [ ] No `<head>`: carregar `GET /api/configuracoes` via fetch e injetar CSS variables `--color-primary` e `--color-secondary` dinamicamente
- [ ] Mostrar logo do estabelecimento no header do display (de `configuracoes.logo_url`)
- [ ] Mostrar `configuracoes.nome_estabelecimento` no header
- [ ] Mostrar `configuracoes.mensagem_display` como rodapé rolante (marquee CSS)

---

### 2.3 Histórico de Pedidos no KDS

**Contexto:** O chef de cozinha precisa ver pedidos finalizados do dia para conferir quantidades e detectar padrões. Hoje o KDS só mostra ativos.

### [arquivo: src/kds/pages/index.php]
**Ação:** MODIFICAR

**Mudanças:**
- [ ] Adicionar 4ª coluna "✅ Finalizados" (colapsável ou com scroll separado, fundo mais escuro)
- [ ] Coluna mostra os últimos 10 pedidos com status `finalizado` do dia (lista simples, sem botão de avançar)
- [ ] Incluir contagem total do dia no header do KDS

### [arquivo: src/kds/assets/js/painel.js]
**Ação:** MODIFICAR

**Mudanças:**
- [ ] Incluir pedidos `finalizado` no `pedidosLocal` (atualmente filtrados)
- [ ] Renderizar coluna finalizado com função `renderCardFinalizado(p)` simplificada (apenas senha + hora + itens)
- [ ] Limitar a 10 mais recentes para não sobrecarregar a tela

---

### 2.4 Busca de Produto no Totem

**Contexto:** Cardápios com 20+ itens tornam a navegação por categoria lenta. Busca é feature padrão em soluções modernas.

### [arquivo: src/totem/pages/index.php]
**Ação:** MODIFICAR

**Mudanças:**
- [ ] Adicionar `<input type="search">` no topo do cardápio com placeholder "Buscar no cardápio..."
- [ ] Botão de lupa que expande o campo em mobile/totem
- [ ] Ao digitar 2+ caracteres: filtrar produtos no DOM (sem nova requisição) por nome e descrição (case-insensitive)
- [ ] Se nenhum resultado: mostrar "Nenhum produto encontrado para '...'"
- [ ] Limpar busca = voltar à listagem normal com filtros de categoria

### [arquivo: src/totem/assets/js/cardapio.js]
**Ação:** MODIFICAR

**Mudanças:**
- [ ] Guardar array de todos os produtos em memória após carga inicial
- [ ] Função `filtrarPorBusca(termo)` que itera sobre todos os produtos e renderiza apenas os que batem
- [ ] Escutar `input` event no campo de busca com debounce de 200ms

---

### 2.5 Combos e Promoções Simples

**Contexto:** Restaurantes precisam criar "Combo do Dia" (produto A + produto B com desconto). Feature diferenciadora que aumenta ticket médio.

### [arquivo: backend/db/migrations/006_combos.sql]
**Ação:** CRIAR

**Mudanças:**
```sql
CREATE TABLE combos (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  nome      VARCHAR(200) NOT NULL,
  descricao TEXT,
  preco     DECIMAL(10,2) NOT NULL,
  imagem    VARCHAR(500) DEFAULT NULL,
  ativo     TINYINT NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE combo_itens (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  combo_id   INT NOT NULL,
  produto_id INT NOT NULL,
  quantidade INT NOT NULL DEFAULT 1,
  FOREIGN KEY (combo_id)   REFERENCES combos(id) ON DELETE CASCADE,
  FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### [arquivo: backend/api/combos.php]
**Ação:** CRIAR

**Mudanças:**
- [ ] `GET /api/combos` — lista combos ativos com seus itens aninhados (público)
- [ ] `POST /api/combos` — cria combo; requer admin; campos: `nome`, `descricao`, `preco`, `itens[]`
- [ ] `PUT /api/combos/:id` — atualiza; requer admin
- [ ] `DELETE /api/combos/:id` — soft-delete (ativo = 0); requer admin

### [arquivo: src/admin/pages/combos.php]
**Ação:** CRIAR

**Mudanças:**
- [ ] Tabela listando combos com colunas: nome, preço, nº de itens, status, ações (editar/excluir)
- [ ] Modal para criar/editar combo:
  - Campos: nome, descrição, preço, imagem URL
  - Seção "Itens do combo": multi-select de produtos com quantidade
- [ ] Layout padrão admin

### [arquivo: src/totem/pages/index.php]
**Ação:** MODIFICAR

**Mudanças:**
- [ ] Adicionar seção "Combos em Destaque" acima do cardápio principal (se existirem combos ativos)
- [ ] Card de combo exibe nome, descrição, lista de itens incluídos e preço total
- [ ] Botão "Adicionar combo" inclui todos os itens do combo no carrinho com um click

---

### 2.6 Avaliação de Experiência (Totem)

**Contexto:** NPS rápido no totem após retirada. Fonte de dados valiosa para o gestor. Goomer Go tem esta feature.

### [arquivo: src/totem/pages/senha.php]
**Ação:** MODIFICAR

**Mudanças:**
- [ ] Adicionar seção "Como foi seu atendimento hoje?" após o resumo do pedido
- [ ] 5 botões emoji (😡 😕 😐 🙂 😍) — escala de 1 a 5
- [ ] Ao clicar: enviar `POST /api/avaliacoes { pedido_id, nota }` e mostrar "Obrigado pelo feedback!"
- [ ] A seção desaparece após avaliação ou skip

---

## FASE 3 — P2: Desejáveis para Versões Futuras

> Estas features NÃO bloqueiam o lançamento v1. Documentadas aqui para planejamento.

### 3.1 Integração PIX Real (Pix Cobrança)
- Integrar com API de banco (ex: Banco Inter, Mercado Pago) para gerar QR Code dinâmico
- Webhook para confirmar pagamento automaticamente e avançar pedido
- **Dependência:** conta PJ no banco parceiro

### 3.2 SSE (Server-Sent Events) em vez de polling
- Substituir `setInterval` no KDS e Display por conexão SSE persistente
- Reduz requisições de ~1440 req/hora/cliente para 0 req + 1 conexão
- **Recomendação:** implementar apenas se polling causar problemas em produção (não é premature optimization urgente para v1)

### 3.3 Impressão de Ticket
- Endpoint `GET /api/pedidos/:id/ticket?formato=escpos` que gera comando ESC/POS
- Suporte a impressoras térmicas USB/rede
- **Dependência:** hardware no restaurante

### 3.4 Programa de Fidelidade
- Tabela `clientes` com `cpf`, `nome`, `pontos`
- No totem: campo CPF opcional no checkout
- No admin: página de clientes com pontuação
- **Dependência:** decisão de produto sobre regras de pontuação

### 3.5 Relatório de Satisfação
- Página admin mostrando NPS médio, distribuição de notas, comentários
- Integrado com tabela `avaliacoes` criada na Fase 2

### 3.6 Multi-unidade
- Tabela `unidades` + FK em pedidos, produtos, usuários
- Header `X-Unidade-ID` nas requisições
- **Dependência:** decisão arquitetural significativa — não trivial

---

## Critérios de Sucesso

### Verificação por fase

#### Fase 0 (Bugfixes)
- [ ] Login com senha errada: botão volta habilitado em ambos os formulários
- [ ] Acesso direto a `/src/kds/pages/index.php` sem login redireciona para login
- [ ] Clicar "Sair" no KDS invalida sessão (verificar `ls /var/lib/php/sessions/` no servidor)
- [ ] Criar pedido com falha de rede não gera pedido sem itens no banco
- [ ] Polling do KDS com 3s de delay não exibe pedidos fora de ordem

#### Fase 1 (P0)
- [ ] Novo pedido chegando no KDS toca beep audível
- [ ] Salvar configurações persiste no banco e recarrega na próxima visita
- [ ] Upload de foto de produto: imagem aparece no cardápio do totem
- [ ] Relatório de 30 dias carrega em < 3s e CSV faz download
- [ ] Totem parado por 60s vai para tela idle; toque retorna ao cardápio
- [ ] Admin consegue adicionar/remover remoções de produtos sem editar banco

#### Fase 2 (P1)
- [ ] Digitar número da senha em `acompanhar.php` mostra status correto
- [ ] Display TV mostra logo e nome do estabelecimento (de configurações)
- [ ] KDS mostra coluna de finalizados com pedidos do dia
- [ ] Busca "burguer" no totem filtra produtos relevantes sem recarregar página
- [ ] Criar combo "Combo Executivo" aparece no totem em seção de destaque
- [ ] Avaliação no totem: nota gravada em `avaliacoes` no banco

---

## Riscos e Dependências

| Risco | Impacto | Mitigação |
|-------|---------|-----------|
| GD Library não instalada no VPS para resize de imagem | Upload sem resize funciona, mas imagens grandes degradam performance | Verificar `php -m \| grep gd` no servidor; se ausente, `apt install php8.4-gd` |
| BUG-04 (sessão PHP) ainda em aberto | Login pode continuar quebrado | Prioridade zero: investigar `session.save_path` no Nginx antes de qualquer deploy de feature |
| Alerta sonoro bloqueado por política de autoplay | Beep não toca sem interação prévia do usuário | Exigir que operador clique em "Ativar som" uma vez ao abrir o KDS; persistir em localStorage |
| Upload de imagens em `/uploads/` requer permissão de escrita no VPS | `500 Internal Server Error` no upload | `chown www-data:www-data /var/www/prontus/uploads && chmod 755 /uploads` |
| CSS variables dinâmicas (cor primária de configurações) | Temas podem conflitar com variáveis hardcoded no CSS | Usar `document.documentElement.style.setProperty('--color-primary', valor)` no JS após carregar config |

---

## Ordem de Implementação Recomendada

```
Semana 1: Fase 0 — todos os bugfixes (2-3 dias)
Semana 1: Fase 1.1 — alerta sonoro KDS (1 dia)
Semana 2: Fase 1.2 — configurações do sistema (2 dias)
Semana 2: Fase 1.3 — gestão de remoções no admin (1 dia)
Semana 3: Fase 1.4 — upload de imagem de produto (2 dias)
Semana 3: Fase 1.5 — relatórios com CSV (2 dias)
Semana 4: Fase 1.6 — tela idle do totem (1 dia)
Semana 5: Fase 2.1 — acompanhamento de pedido (1 dia)
Semana 5: Fase 2.2 — personalização do display (1 dia)
Semana 5: Fase 2.3 — histórico KDS (1 dia)
Semana 6: Fase 2.4 — busca no totem (1 dia)
Semana 6: Fase 2.5 — combos (2 dias)
Semana 6: Fase 2.6 — avaliação (1 dia)
Fase 3: Backlog para versões futuras
```

**Total estimado para v1 comercializável:** 5-6 semanas de desenvolvimento solo.
