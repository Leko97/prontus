# PRD — Auditoria de Bugs, Falhas e Redundâncias (Codebase Completa)

**Data:** 2026-06-11
**Pergunta de pesquisa:** Verificação completa da codebase em busca de erros, bugs, redundâncias e falhas — backend (PHP/API), frontend (totem, KDS, display, admin), banco de dados e scripts.

> Nota de método: a auditoria foi feita por 4 investigações paralelas (backend, totem/KDS/display, admin, banco de dados). Os achados críticos foram verificados manualmente nos arquivos. Falsos positivos reportados pelos sub-agentes foram removidos ou reclassificados (registrados ao final).

---

## 1. Achados CRÍTICOS

| # | Arquivo | Linha | Categoria | Descrição |
|---|---------|-------|-----------|-----------|
| C1 | `backend/scripts/migrate.php` | 1–30 | Migration incompleta | O script executa **apenas** `001_schema.sql` e `002_seed.sql`. Não aplica as migrations 003–008. Um banco criado por ele **não tem** as tabelas `configuracoes`, `combos`, `combo_itens`, `avaliacoes`, nem as colunas `preco_unitario`/`quantidade` em `pedido_item_extras` — que o código usa. |
| C2 | `backend/api/_pedidos_helpers.php` | 4, 10, 20–21 | Schema vs código | O código faz `SELECT nome, preco_unitario, quantidade FROM pedido_item_extras`, mas essas colunas só existem após a migration `008_totem_fixes.sql` (linhas 4–6). Combinado com C1, qualquer instalação via `migrate.php` quebra em runtime ao consultar pedidos. |
| C3 | `src/admin/pages/*.php` (todas as 15 páginas) | — | Autenticação | **Nenhuma** página do admin tem verificação de sessão server-side (`grep -L "session\|require_auth"` retorna todas). A proteção é só client-side via `protegerRota()` em `src/admin/assets/js/admin.js:4-10`, que roda **após** a renderização. O HTML do painel é servido a qualquer visitante; a segurança real depende apenas dos endpoints da API exigirem auth. |
| C4 | `src/admin/pages/restricoes.php` | 77–80 | Falha de integração | A tela de restrições do admin salva em `localStorage` (`prontus_restricoes`), enquanto o totem lê as restrições do backend (`GET /api/restricoes`). Alterações feitas no admin **nunca chegam ao totem** — a tela é funcionalmente desconectada do sistema. Existe API `/api/restricoes` e helper em `api.js`, mas não são usados pela página. |
| C5 | `src/totem/assets/js/acompanhar.js` | 21, 74, 126–135 | XSS refletido | `senha` vem da URL (`getParam('senha')`, linha 21) e é injetada sem escape em `innerHTML` por `renderNaoEncontrado()` (linha 130: `Senha #${senha} não encontrada`). URL maliciosa do tipo `?senha=<img src=x onerror=...>` executa script. |

## 2. Achados de ALTA severidade

### Backend / API

| # | Arquivo | Linha | Descrição |
|---|---------|-------|-----------|
| A1 | `backend/config/helpers.php` | 32–64 | Rate limit de login com race condition: leitura (`file_get_contents`) e escrita (`file_put_contents` com `LOCK_EX`) não são atômicas entre si; requisições paralelas ultrapassam o limite. Usado em `backend/api/auth/login.php:18,27`. |
| A2 | `backend/api/produtos.php` | 22–25 | Validação de preço divergente entre POST e PUT: o PUT aceita `preco < 0` ser rejeitado mas a comparação difere do POST (`<= 0` vs `< 0`), permitindo preço 0 só num dos fluxos. |
| A3 | `backend/api/avaliacoes.php` | 9–33 | `POST /avaliacoes` é público e usa `ON DUPLICATE KEY UPDATE`: qualquer um pode criar/sobrescrever avaliação de qualquer pedido, sem prova de posse da senha. |
| A4 | `backend/scripts/backup.php` | 8–16 | Senha do banco passada via `-p` na linha de comando do `mysqldump` — visível em `ps aux` durante a execução. (O `sprintf` em si está correto; ver falsos positivos.) |

### Frontend (totem / KDS / display)

| # | Arquivo | Linha | Descrição |
|---|---------|-------|-----------|
| A5 | `src/totem/pages/carrinho.php` | 123–137 | `render()` re-adiciona event listeners (`.btn-editar` etc.) a cada renderização sem cleanup — cliques duplicados e leak de memória em sessão longa de totem. |
| A6 | `src/totem/assets/js/pagamento.js` | 45–98 | Janela para dupla submissão de pedido: o guard `processando`/`btn.disabled` é revertido no fluxo de erro, e o redirect pós-sucesso deixa janela para novo clique em rede lenta. Risco de pedido duplicado. |
| A7 | `src/display/assets/js/display.js` | 30–56 | Flag `emAndamento` do polling é local à função `iniciarPolling()`; se chamada mais de uma vez, há polls concorrentes duplicados. |
| A8 | `src/totem/pages/index.php` | 142 | `JSON.parse(btn.dataset.combo)` sem try/catch — atributo corrompido quebra a feature de adicionar combo. |

### Admin

| # | Arquivo | Linha | Descrição |
|---|---------|-------|-----------|
| A9 | `src/admin/assets/js/combos.js` | 23, 110, 130, 156 | Único módulo do admin que chama a API com `fetch()` direto em vez dos helpers de `src/shared/js/api.js` (não existem `getCombos`/`salvarCombo`/`deletarCombo` lá). Tratamento de erro e base URL divergem do resto do painel. |
| A10 | `src/admin/assets/js/usuario-form.js` | 19–29 | Se `getUsuarioById()` falha em modo edição, o form permanece em estado "edição" vazio — salvar nesse estado pode sobrescrever o usuário com campos em branco. |

### Banco de dados

| # | Arquivo | Linha | Descrição |
|---|---------|-------|-----------|
| A11 | `backend/db/migrations/001_schema.sql` | 89–94 | `pedido_item_restricoes.restricao_slug` sem FOREIGN KEY para `restricoes(slug)` — aceita slugs inexistentes (contraste com `produto_restricoes`, que tem FK em `produto_id`). |
| A12 | `backend/db/migrations/001_schema.sql` | 66–93 | Falta de índices nas colunas de junção mais consultadas: `pedido_item_id` (em `pedido_item_extras/remocoes/restricoes`) e `produto_id` (em `adicionais`, `remocoes`, `produto_restricoes`). A migration 005 só indexa `pedidos`. |
| A13 | `backend/db/migrations/003_dados.sql` (linha 4) e `backend/scripts/migrate.php` (linhas 13, 18) | — | Senhas padrão em texto plano versionadas no repositório (`prontus123`, `admin123`, `cozinha123`), com eco da credencial no output do migrate. |

## 3. Achados de MÉDIA severidade

| # | Arquivo | Linha | Descrição |
|---|---------|-------|-----------|
| M1 | `backend/.htaccess` | 5–7 | `Access-Control-Allow-Origin: *` no Apache conflita com a política de CORS mais restrita aplicada em PHP (`cors_headers()`). |
| M2 | `backend/api/pedidos_status.php` | 18–22 | UPDATE de status sem transação; falha parcial deixa `preparado_em` inconsistente. |
| M3 | `backend/api/pedidos.php` | 70 | Mensagem de erro expõe ID interno do produto ("Produto {id} não encontrado") — permite enumeração. |
| M4 | `backend/api/produto.php` | 109–110 | Cálculo de resize de imagem divide por `$origW`/`$origH` sem validar dimensão 0 — divisão por zero com imagem corrompida. |
| M5 | `backend/api/auth/login.php` | 11–12 | Login lê `$_POST` direto (resto da API usa `input_json()`); sem validação de vazio antes de consultar o banco. |
| M6 | `backend/api/combos.php` | 9–16 | Extração de ID por parse manual da URL (`explode('/')`) em vez do mecanismo de rotas usado nos demais endpoints. |
| M7 | `src/totem/pages/index.php` | 92 | `fetch('/api/combos')` com `catch` vazio: qualquer erro oculta a seção de combos silenciosamente, sem log. |
| M8 | `src/kds/assets/js/painel.js` | 82–110 | Estrutura de `pedido.itens` assumida sem validação; resposta inesperada quebra a renderização da coluna inteira do KDS. |
| M9 | `src/admin/assets/js/pedidos-admin.js` | 100–109 | Dados da API (`e.nome`, nomes de itens) injetados em `innerHTML` sem `escapeHtml()` — XSS armazenado se um nome de produto/extra contiver HTML. |
| M10 | `src/admin/assets/js/remocoes.js` | 6–11 | `getElementById` sem null-check seguido de `addEventListener` — falha silenciosa se IDs do HTML mudarem. |
| M11 | `src/admin/pages/login.php` | 1–61 | Não redireciona usuário já autenticado; sem uso de `checkAuth()`. |
| M12 | `backend/db/migrations/008_totem_fixes.sql` | 1–11 | Migration não idempotente (`ADD COLUMN` sem guarda) e usa coluna gerada `STORED` (requer MySQL ≥ 5.7.6). |
| M13 | `database/prontus_dump.sql` (linha ~353) vs `migrate.php:13` | — | Hashes bcrypt mistos (`$2y$` e `$2b$`) entre usuários; `PASSWORD_DEFAULT` vs `PASSWORD_BCRYPT` (`backend/api/usuario.php:35`) dependem da versão do PHP. |
| M14 | `backend/db/migrations/003_dados.sql` | 56–57, 78 | Comentários com mapeamento de IDs errado ("14=Batata Frita" quando 14=Brownie; Batata Frita é 17). O SQL em si está correto. |
| M15 | `src/totem/assets/js/acompanhar.js` | 37–42 | Polling multi-aba sem coordenação; `visibilitychange` reinicia polling podendo duplicar timers. |

## 4. Redundâncias identificadas

| # | Onde | Descrição |
|---|------|-----------|
| R1 | `database/001_schema.sql`, `002_seed.sql`, `003_dados.sql` vs `backend/db/migrations/` (mesmos nomes) | Duplicação **byte a byte** (diff vazio). Duas "fontes de verdade" para o schema; `backend/db/migrations/` é a completa (tem 004–008). |
| R2 | `backend/api/cardapio.php:18-50` vs `backend/api/_produto_helpers.php:2-41` | Mesma lógica de montagem de produto (produto + restrições + extras + remoções) implementada duas vezes. |
| R3 | `src/admin/assets/js/dashboard.js:24-91` vs `src/admin/assets/js/relatorios.js:92-165` | Função `renderChart()` ~95% idêntica duplicada nos dois arquivos. |
| R4 | `src/display/assets/js/display.js:24-27` vs `src/kds/assets/js/painel.js:74-79` | `atualizarRelogio()` duplicada com código idêntico. |
| R5 | `backend/api/pedidos.php:46-48`, `backend/api/produto.php:56-58` e outros | Padrão `foreach { prepare()->execute() }` para inserir em tabelas de junção repetido em vários endpoints, sem helper comum. |
| R6 | `src/admin/assets/js/*.js` | Padrão de renderização de tabela (`.map(...).join('')`) repetido em 5+ módulos (categorias, usuarios, produtos, adicionais, pedidos-admin). |
| R7 | `backend/api/index.php` vs respostas manuais | Alguns endpoints montam `['erro' => ...]` manualmente em vez de usar `error_response()` de `helpers.php`. |

## 5. Achados de BAIXA severidade (resumo)

- `backend/api/pedidos_historico.php:24` — wildcards `%`/`_` do usuário não escapados no LIKE (busca imprecisa; **não** é SQL injection, o valor é parametrizado).
- `backend/api/combos.php:111` — `UPDATE ... SET ' . implode(...)` com nomes de coluna concatenados; seguro hoje (lista controlada), padrão frágil.
- `backend/api/usuarios.php` vs `usuario.php` — formato de retorno inconsistente (campo `ativo` presente só em alguns fluxos).
- `backend/api/adicionais.php:36,66` — `preco` default 0 sem validação (adicional gratuito silencioso).
- `src/totem/pages/index.php:141-160` — `window._adicionarCombo` exposta globalmente (mesmo padrão `window._*` em `combos.js:42-43` do admin com `onclick` inline).
- `src/admin/assets/js/{produtos,usuarios}.js:52` — `location.href` com ID interpolado sem encode.
- `001_schema.sql` — coluna `ativo` sem índice; `pedido_itens.produto_id` nullable sem FK (intencional para histórico, mas sem constraint).
- `005_relatorios.sql:1-2` — índice `(status, horario)` não atende as queries de `relatorios.php:27-37` que filtram primeiro por `horario` (ordem invertida do prefixo).
- `relatorios.php:24` — `' 23:59:59'` concatenado manualmente; sensível a timezone do banco.
- `src/admin/pages/produto-form.php:131-311` e `categoria-form.php:60-117` — scripts inline extensos, fora do padrão modular dos demais.

## 6. Falsos positivos descartados (verificados manualmente)

- **"SQL injection em `pedidos_historico.php:24`"** — o valor entra como parâmetro bound do PDO; reclassificado como escape de wildcard LIKE (baixa).
- **"`sprintf` com argumentos faltando em `backup.php`"** — verificado: 6 placeholders, 6 argumentos (incluindo `DB_PORT`). Correto. Permanece apenas a exposição da senha via `-p` (A4).
- **"FormData sem Content-Type em `api.js:208`"** — omitir o Content-Type com FormData é o comportamento correto (o browser define o boundary); não é bug.

## 7. Estado atual / Arquitetura observada

- **Stack:** PHP vanilla (sem framework) + MySQL + JS ES Modules sem build. Apache (`.htaccess`) roteando `/api/*` para `backend/api/index.php`.
- **Módulos:** `src/totem` (autoatendimento), `src/kds` (cozinha), `src/display` (painel de senhas), `src/admin` (gestão), `src/shared` (api.js, utils.js, CSS).
- **Banco (16 tabelas):** categorias, restricoes, produtos, produto_restricoes, adicionais, remocoes, pedidos, pedido_itens, pedido_item_extras, pedido_item_remocoes, pedido_item_restricoes, usuarios, configuracoes (mig. 004), combos + combo_itens (mig. 006), avaliacoes (mig. 007).
- **Pontos sólidos verificados:** PDO com prepared statements em toda a API; transações com rollback em `pedidos.php:112-173` e `combos.php:61-87`; `require_admin()` em relatórios; verificação de email duplicado em `usuario.php`.

## 8. Gaps identificados (o que precisará ser tratado)

1. **Pipeline de migrations** — `migrate.php` precisa aplicar 003–008 (ou um runner ordenado com controle de versão aplicada). Sem isso, instalação nova quebra (C1+C2).
2. **Camada de auth server-side para o admin** — hoje inexistente nas páginas PHP (C3).
3. **Persistência das restrições do admin** — tela usa localStorage; precisa integrar com `GET/PUT /api/restricoes` (C4).
4. **Helpers de API para combos** em `src/shared/js/api.js` (A9).
5. **Escape de HTML padronizado** — `escapeHtml()` existe mas não é aplicado consistentemente (C5, M9).
6. **Consolidação da pasta `database/`** — duplicada e desatualizada frente a `backend/db/migrations/` (R1).

## 9. Referências externas consultadas

Nenhuma — auditoria 100% baseada no código local.
