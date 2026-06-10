# Auditoria de Bugs e Defeitos — Prontus v1

**Data:** 2026-05-11
**Fonte:** Análise de docs/deploy.md, docs/architecture.md e leitura do código-fonte completo

---

## Arquivos relevantes

| Arquivo | Linha | Relevância |
|---------|-------|------------|
| `backend/api/index.php` | 1–57 | Router principal — entry point da API |
| `backend/api/auth/login.php` | 1–36 | Autenticação — bug de sessão e redirect aberto |
| `backend/api/auth/logout.php` | 1–8 | Logout — falta CORS headers |
| `backend/api/auth/me.php` | 1–6 | Verifica sessão ativa |
| `backend/config/helpers.php` | 1–37 | CORS, session, json_response, require_auth |
| `backend/config/database.php` | 1–17 | PDO singleton — defaults inseguros |
| `backend/api/pedidos.php` | 103–135 | Criação de pedido — sem transação atômica |
| `backend/api/pedidos_status.php` | 1–26 | Atualização de status |
| `backend/api/adicionais.php` | 59, 81 | PUT sem validação de produto_id; DELETE físico inconsistente |
| `backend/api/produto.php` | 24–35 | PUT sem validação de campos obrigatórios |
| `backend/scripts/migrate.php` | 1–30 | Senha padrão em código; sem idempotência |
| `src/shared/js/api.js` | 1 | USE_MOCK = false; fetchAPI central |
| `src/admin/pages/login.php` | 53–57 | Botão desabilitado permanentemente em erro de login |
| `src/kds/pages/login.php` | 54–59 | Mesmo bug do botão |
| `src/kds/pages/index.php` | 80–86 | Falta proteção de rota (qualquer um acessa) |
| `src/kds/assets/js/painel.js` | 18–20 | Logout não invalida sessão no servidor |
| `src/kds/assets/js/polling.js` | 10–28 | Race condition — requisições simultâneas |
| `src/display/assets/js/display.js` | 30–50 | Race condition — polling sem AbortController |
| `src/totem/assets/js/pagamento.js` | 44–79 | Sem debounce — pedidos duplicados possíveis |
| `src/totem/assets/js/pagamento.js` | 71 | `resultado.senha` não validado antes do redirect |
| `src/admin/assets/js/dashboard.js` | 124–125 | `loadMetricas()` e `loadUltimosPedidos()` sem await |
| `docs/deploy.md` | 144–149 | Bug de login em aberto (status do fix) |
| `docs/architecture.md` | 229–246 | Pendências conhecidas — bug de sessão |

---

## Bugs confirmados

### 🔴 CRÍTICOS — Quebram funcionalidade principal

#### BUG-01: Botão de login desabilitado permanentemente em erro
**Arquivos:** `src/admin/pages/login.php:53–57` e `src/kds/pages/login.php:54–59`
**Sintoma:** Quando o login falha, o servidor redireciona de volta com `?erro=1`. O botão foi desabilitado no `submit` e nunca é reabilitado, pois é uma nova requisição de página inteira.
**Causa:** O listener de submit desabilita o botão mas não há lógica de reabilitação ao carregar a página com `?erro=1`.
```javascript
// Ambos os arquivos — submit desabilita sem verificar recarregamento por erro:
document.getElementById('loginForm').addEventListener('submit', () => {
  const btn = document.getElementById('btnLogin'); // ou 'btnEntrar'
  btn.disabled = true;
  btn.textContent = 'Entrando…';
});
```
**Fix necessário:** Ao detectar `?erro=1` na URL ao carregar a página, garantir que o botão permaneça habilitado.

---

#### BUG-02: KDS sem proteção de rota
**Arquivo:** `src/kds/pages/index.php:80–86`
**Sintoma:** Qualquer usuário não autenticado acessa o painel KDS diretamente via URL sem ser redirecionado para o login.
**Causa:** `painel.js` não importa nem chama `protegerRota()` ou `initAdminPage()`. O admin tem essa proteção corretamente em `dashboard.js:5`.
**Fix necessário:** Adicionar verificação de autenticação no carregamento do KDS, igual ao padrão do admin.

---

#### BUG-03: Logout do KDS não invalida sessão PHP
**Arquivo:** `src/kds/assets/js/painel.js:18–20`
**Sintoma:** Ao clicar em "Sair" no KDS, o usuário é redirecionado para a tela de login mas a sessão PHP permanece ativa no servidor. O cookie de sessão continua válido.
**Causa:** O handler de logout apenas faz `window.location.href` sem chamar `POST /api/auth/logout`.
```javascript
// painel.js — incorreto (sem chamada à API):
document.getElementById('btnLogout')?.addEventListener('click', () => {
  window.location.href = '/src/kds/pages/login.php';
});

// admin.js — correto (referência):
document.getElementById('btnLogout')?.addEventListener('click', async () => {
  try {
    await logout(); // chama POST /api/auth/logout
  } finally {
    redirectLogin();
  }
});
```

---

#### BUG-04: Bug de sessão PHP — login redireciona de volta para login (em aberto)
**Arquivo:** `backend/api/auth/login.php` + `docs/architecture.md:229–246`
**Sintoma:** Após submeter o formulário de login, o browser vai para `dashboard.php` e retorna imediatamente para a tela de login.
**Hipótese 1:** A sessão PHP não persiste entre o bloco `/api` (FastCGI via `index.php`) e o bloco `\.php$` no Nginx — podem ter `session.save_path` diferentes entre os dois blocos.
**Hipótese 2:** `dashboard.php` chama `checkAuth()` via `api.js` com `fetchAPI('/auth/me')` — se o cookie de sessão não for enviado corretamente (domínio/path), retorna 401 e redireciona.
**Fix aplicado em 2026-05-11:** Removido `e.preventDefault()` dos formulários — ainda não confirmado como resolvido.
**Como investigar:**
```bash
tail -f /var/log/nginx/prontus_error.log
ssh root@159.223.165.79 "ls /var/lib/php/sessions/"
```

---

### 🟠 ALTOS — Podem gerar dados inconsistentes ou vulnerabilidades

#### BUG-05: Criação de pedido sem transação atômica
**Arquivo:** `backend/api/pedidos.php:103–135`
**Sintoma:** Se o servidor falhar após inserir o pedido mas antes de inserir os itens (ou extras/remoções), o banco fica com um pedido vazio ou incompleto.
**Causa:** Múltiplos `INSERT` em sequência sem `BEGIN ... COMMIT`.
**Fix necessário:** Envolver toda a criação de pedido em uma transação PDO (`beginTransaction` / `commit` / `rollBack`).

---

#### BUG-06: Race condition no polling (KDS e Display)
**Arquivos:** `src/kds/assets/js/polling.js:10–28` e `src/display/assets/js/display.js:30–50`
**Sintoma:** Se a requisição `getPedidos()` demorar mais de 2 segundos (intervalo do polling), múltiplas requisições ficam em voo simultâneo. A ordem das respostas não é garantida e pode causar flickering ou estado desatualizado sendo exibido.
**Causa:** `setInterval(tick, 2000)` não espera a resolução da Promise anterior antes de disparar a próxima.
**Fix necessário:** Usar flag `emAndamento` ou `AbortController` para garantir apenas uma requisição ativa por vez.

---

#### BUG-07: Pedidos duplicados no totem (falta debounce)
**Arquivo:** `src/totem/assets/js/pagamento.js:44–50`
**Sintoma:** Cliques muito rápidos no botão "Confirmar pagamento" antes de ele ser desabilitado podem enviar múltiplos pedidos.
**Causa:** O botão é desabilitado dentro da função `async confirmarPagamento()` após o início da execução assíncrona. Cliques em sequência muito rápida passam pela guarda `if (!metodoPagamento)` antes da desabilitação.

---

#### BUG-08: `resultado.senha` não validado antes de redirecionar
**Arquivo:** `src/totem/assets/js/pagamento.js:71`
**Sintoma:** Se a API retornar resposta sem campo `senha` (ex.: erro silencioso com HTTP 200), o redirect fica `/src/totem/pages/senha.php?senha=undefined`, quebrando a página de confirmação.
**Causa:** Nenhuma verificação de `resultado.senha` antes de usar no template de URL.
```javascript
// Linha 71 — sem validação:
window.location.href = `/src/totem/pages/senha.php?senha=${encodeURIComponent(resultado.senha)}`;
```

---

#### BUG-09: PUT em adicionais não valida se produto_id existe
**Arquivo:** `backend/api/adicionais.php:59`
**Sintoma:** É possível atualizar um adicional para apontar para um `produto_id` inexistente, quebrando integridade referencial (ainda que FK no MySQL possa rejeitar, a mensagem de erro seria genérica).
**Causa:** O endpoint `PUT /api/adicionais/:id` não verifica a existência do produto antes do UPDATE, ao contrário do `POST` que verifica.

---

#### BUG-10: DELETE de adicionais é físico — inconsistente com demais entidades
**Arquivo:** `backend/api/adicionais.php:81`
**Sintoma:** Deletar um adicional remove o registro permanentemente. Histórico de pedidos que referenciaram esse adicional pode perder integridade semântica.
**Causa:** `DELETE FROM adicionais WHERE id = ?` em vez de `UPDATE ... SET ativo = 0` como fazem produtos e categorias.

---

### 🟡 MÉDIOS — Afetam UX ou podem causar comportamento inesperado

#### BUG-11: `loadMetricas()` e `loadUltimosPedidos()` sem await no dashboard
**Arquivo:** `src/admin/assets/js/dashboard.js:124–125`
**Sintoma:** Erros nessas chamadas não são capturados no contexto correto; se houver falha de inicialização, a causa pode ser mascarada.
**Causa:** Chamadas disparadas sem `await` e sem encadeamento de `.catch()` explícito.

---

#### BUG-12: PUT em produto sem validação de campos obrigatórios
**Arquivo:** `backend/api/produto.php:24–35`
**Sintoma:** Enviar `PUT /api/produtos/:id` sem `nome` ou `preco` pode causar undefined array key no PHP ou gravar dados inválidos no banco.
**Causa:** Ausência de validação dos campos antes do UPDATE, diferente do POST que valida.

---

#### BUG-13: CORS totalmente aberto
**Arquivo:** `backend/config/helpers.php:33`
**Sintoma:** Qualquer origem pode fazer requisições à API.
**Causa:** `header('Access-Control-Allow-Origin: *')` sem restrição de domínio.

---

## Padrões existentes a seguir

### Proteção de rota (admin — referência correta)
```javascript
// src/admin/assets/js/admin.js
export async function protegerRota() {
  try {
    const { logado } = await checkAuth();
    if (!logado) redirectLogin();
  } catch {
    redirectLogin();
  }
}
```

### Logout correto (admin — referência)
```javascript
// src/admin/assets/js/admin.js
document.getElementById('btnLogout')?.addEventListener('click', async () => {
  try {
    await logout();
  } finally {
    redirectLogin();
  }
});
```

### Soft-delete (padrão de produtos e categorias)
```sql
UPDATE produtos SET ativo = 0 WHERE id = ?
UPDATE categorias SET ativo = 0 WHERE id = ?
```

---

## Tecnologias já em uso

| Tecnologia | Uso |
|---|---|
| PHP 8.4 puro | Backend — sem framework |
| PDO + MySQL | Banco de dados (InnoDB, utf8mb4) |
| Sessões PHP (`$_SESSION`) | Autenticação |
| Nginx 1.28 (porta 8080) | Servidor web + proxy FastCGI |
| PHP-FPM (`php8.4-fpm.sock`) | Execução PHP |
| GitHub Actions | CI/CD automático no push para main |
| `sessionStorage` | Carrinho do totem |
| `setInterval` | Polling KDS e Display (2s) |
| `JSON.stringify` como hash | Detecção de mudança no polling |

---

## Estado atual

### O que funciona
- Router de API com regex e `require` de handlers
- Autenticação por sessão PHP com perfis `admin` e `cozinha`
- Cardápio público com extras, restrições e remoções aninhados
- Criação de pedidos com senha sequencial (`#001`, `#002`…)
- Soft-delete em produtos e categorias
- CI/CD via GitHub Actions (deploy em ~20s)
- Polling funcional no KDS e Display (com a race condition latente)
- Proteção de rota correta no admin

### Em aberto (docs/architecture.md:229–246 + docs/deploy.md:144–149)
- BUG-04: Bug de sessão PHP no login — fix parcialmente aplicado em 2026-05-11, não confirmado
- Verificar se `session.save_path` é consistente entre os dois blocos Nginx

---

## Gaps identificados

| Gap | Impacto |
|---|---|
| Sem transação em `POST /api/pedidos` (BUG-05) | Pedidos incompletos em falha de servidor |
| KDS sem `protegerRota()` (BUG-02) | Acesso não autorizado ao painel de cozinha |
| Logout KDS sem `POST /api/auth/logout` (BUG-03) | Sessão zumbi no servidor |
| Botão de login permanentemente desabilitado em erro (BUG-01) | Usuário preso — não consegue tentar de novo |
| Race condition no polling (BUG-06) | Respostas fora de ordem em rede lenta |
| Sem debounce no checkout (BUG-07) | Pedidos duplicados possíveis |
| `resultado.senha` sem validação (BUG-08) | URL `?senha=undefined` quebrando página de confirmação |
| Soft-delete inconsistente em adicionais (BUG-10) | Histórico de pedidos comprometido |
| CORS `*` (BUG-13) | Risco de segurança cross-origin |
