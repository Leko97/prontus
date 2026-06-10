# Spec — Correção de Bugs por Prioridade (Prontus v1)

**Data:** 2026-05-11
**Baseado em:** docs/bugs-audit.md

## Resumo

Correção de 13 bugs identificados na auditoria, organizados em 4 fases deployáveis independentemente. A Fase 1 é crítica e desbloqueia o uso do sistema em produção. As demais fases protegem a integridade dos dados e estabilidade do polling.

---

## Arquivos a modificar

| Arquivo | Ação | Bugs |
|---------|------|------|
| `src/admin/pages/login.php` | MODIFICAR | BUG-01 |
| `src/kds/pages/login.php` | MODIFICAR | BUG-01 |
| `src/kds/pages/index.php` | MODIFICAR | BUG-02 |
| `src/kds/assets/js/painel.js` | MODIFICAR | BUG-02, BUG-03 |
| `backend/api/pedidos.php` | MODIFICAR | BUG-05 |
| `src/totem/assets/js/pagamento.js` | MODIFICAR | BUG-07, BUG-08 |
| `src/kds/assets/js/polling.js` | MODIFICAR | BUG-06 |
| `src/display/assets/js/display.js` | MODIFICAR | BUG-06 |
| `src/admin/assets/js/dashboard.js` | MODIFICAR | BUG-11 |
| `backend/api/adicionais.php` | MODIFICAR | BUG-09, BUG-10 |
| `backend/api/produto.php` | MODIFICAR | BUG-12 |
| `backend/config/helpers.php` | MODIFICAR | BUG-13 |

---

## Fase 1 — Auth e Sessão (BUG-01, BUG-02, BUG-03, BUG-04)

### [arquivo: src/admin/pages/login.php] — BUG-01

**Ação:** MODIFICAR — linhas 52–57

**Problema:** O listener de `submit` desabilita o botão. Se o browser mantiver o estado do DOM via bfcache ao navegar de volta (ou em caso de falha de rede), o botão fica preso desabilitado. O fix é reabilitar explicitamente o botão quando a página carrega com `?erro=1`.

**Mudanças:**
- [ ] No bloco `<script type="module">` existente, dentro do `if (params.get('erro'))`, garantir que o botão esteja habilitado e com texto correto.

**Como fica (substituir o bloco `<script>` inteiro, linhas 45–58):**
```html
<script type="module">
  const params = new URLSearchParams(window.location.search);
  if (params.get('erro')) {
    document.getElementById('errorAlert').classList.remove('hidden');
    const btn = document.getElementById('btnLogin');
    btn.disabled = false;
    btn.textContent = 'Entrar';
  }

  document.getElementById('loginForm').addEventListener('submit', () => {
    const btn = document.getElementById('btnLogin');
    btn.disabled = true;
    btn.textContent = 'Entrando…';
  });
</script>
```

---

### [arquivo: src/kds/pages/login.php] — BUG-01

**Ação:** MODIFICAR — linhas 48–60

**Mudanças:**
- [ ] Mesmo fix do admin/login.php, adaptando o ID do botão para `btnEntrar`.

**Como fica (substituir o bloco `<script>` inteiro, linhas 48–60):**
```html
<script type="module">
  const params = new URLSearchParams(window.location.search);
  if (params.get('erro')) {
    document.getElementById('errorAlert').classList.remove('hidden');
    const btn = document.getElementById('btnEntrar');
    btn.disabled = false;
    btn.textContent = 'Entrar no painel';
  }

  document.getElementById('loginForm').addEventListener('submit', () => {
    const btn = document.getElementById('btnEntrar');
    btn.disabled = true;
    btn.textContent = 'Entrando…';
  });
</script>
```

---

### [arquivo: src/kds/assets/js/painel.js] — BUG-02, BUG-03

**Ação:** MODIFICAR — linhas 1–3 (imports) e linhas 11–21 (DOMContentLoaded)

**Mudanças:**
- [ ] Adicionar imports de `checkAuth` e `logout` de `api.js` (BUG-02 e BUG-03)
- [ ] Chamar `checkAuth()` no DOMContentLoaded e redirecionar se não autenticado (BUG-02)
- [ ] Substituir o handler de logout por um que chame `POST /api/auth/logout` antes de redirecionar (BUG-03)

**Como fica (início do arquivo):**
```javascript
import { atualizarStatusPedido, checkAuth, logout } from '/src/shared/js/api.js';
import { formatTime, formatElapsed, minutesAgo, proximoStatus, btnAvancarLabel, restricaoLabel } from '/src/shared/js/utils.js';
import { iniciarPolling } from './polling.js';

const COLUNAS = ['recebido', 'em-preparo', 'pronto'];
let pedidosLocal = [];

document.addEventListener('DOMContentLoaded', async () => {
  /* BUG-02: Proteger rota — redireciona se não autenticado */
  try {
    const { logado } = await checkAuth();
    if (!logado) { window.location.href = '/src/kds/pages/login.php'; return; }
  } catch {
    window.location.href = '/src/kds/pages/login.php';
    return;
  }

  atualizarRelogio();
  setInterval(atualizarRelogio, 1000);
  setInterval(atualizarTempos, 30000);

  iniciarPolling(onNovosDados, 2000);

  /* BUG-03: Logout invalida sessão no servidor */
  document.getElementById('btnLogout')?.addEventListener('click', async () => {
    try {
      await logout();
    } finally {
      window.location.href = '/src/kds/pages/login.php';
    }
  });
});
```

---

### BUG-04 — Investigação da sessão PHP (diagnóstico antes do fix)

**Este bug requer investigação no servidor antes de aplicar qualquer fix.** Executar os seguintes comandos para diagnosticar:

```bash
# 1. Ver config Nginx do Prontus
ssh root@159.223.165.79 "cat /etc/nginx/sites-available/prontus.conf"

# 2. Verificar se o bloco /api inclui fastcgi_params (essencial para HTTP_COOKIE)
ssh root@159.223.165.79 "grep -A 10 'location.*api' /etc/nginx/sites-available/prontus.conf"

# 3. Ver se há sessões sendo criadas
ssh root@159.223.165.79 "ls -la /var/lib/php/sessions/ | head -20"

# 4. Ver logs de erro
ssh root@159.223.165.79 "tail -50 /var/log/nginx/prontus_error.log"
```

**Fix provável — bloco `/api` no Nginx precisa de `include fastcgi_params;`:**

A causa mais provável é que o bloco da API não inclui `fastcgi_params`, o que impede que `HTTP_COOKIE` seja passado ao PHP-FPM. Sem o cookie de sessão, `session_start()` cria uma nova sessão vazia em vez de retomar a existente.

O bloco deve ficar assim em `/etc/nginx/sites-available/prontus.conf`:
```nginx
location ~ ^/api(/.*)?$ {
    include fastcgi_params;                    # ← linha crítica
    fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    fastcgi_param SCRIPT_FILENAME /var/www/prontus/backend/api/index.php;
    fastcgi_param REQUEST_URI $request_uri;
}
```

Após editar: `nginx -t && systemctl reload nginx`

---

## Fase 2 — Integridade de Dados (BUG-05, BUG-07, BUG-08)

### [arquivo: backend/api/pedidos.php] — BUG-05

**Ação:** MODIFICAR — linhas 103–135

**Problema:** Múltiplos INSERTs sem transação. Se qualquer INSERT falhar no meio, o pedido fica incompleto no banco (ex: pedido criado mas sem itens, ou itens sem extras).

**Mudanças:**
- [ ] Envolver toda a sequência de INSERTs (linhas 103–135) em `beginTransaction()` / `commit()` com `rollBack()` no catch.
- [ ] Manter o `error_response` para validações fora da transação (elas não alteram dados).

**Como fica (substituir linhas 103–144):**
```php
    $pdo->beginTransaction();
    try {
        $stmtPedido = $pdo->prepare(
            'INSERT INTO pedidos (senha, status, pagamento, total) VALUES (?, ?, ?, ?)'
        );
        $stmtPedido->execute([$senha, 'recebido', $data['pagamento'] ?? null, $total]);
        $pedidoId = (int)$pdo->lastInsertId();

        foreach ($itensPreparados as $item) {
            $stmtItem = $pdo->prepare(
                'INSERT INTO pedido_itens (pedido_id, produto_id, produto_nome, preco_unitario, quantidade)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmtItem->execute([
                $pedidoId,
                $item['produto_id'],
                $item['produto_nome'],
                $item['preco_unitario'],
                $item['quantidade'],
            ]);
            $itemId = (int)$pdo->lastInsertId();

            foreach ($item['extras'] as $nome) {
                $pdo->prepare('INSERT INTO pedido_item_extras (pedido_item_id, nome) VALUES (?, ?)')
                    ->execute([$itemId, $nome]);
            }
            foreach ($item['remocoes'] as $nome) {
                $pdo->prepare('INSERT INTO pedido_item_remocoes (pedido_item_id, nome) VALUES (?, ?)')
                    ->execute([$itemId, $nome]);
            }
            foreach ($item['restricoes'] as $slug) {
                $pdo->prepare('INSERT INTO pedido_item_restricoes (pedido_item_id, restricao_slug) VALUES (?, ?)')
                    ->execute([$itemId, $slug]);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_response('Erro ao registrar pedido', 500);
    }

    $pedidoRow = $pdo->prepare(
        "SELECT id, senha, status, DATE_FORMAT(horario, '%Y-%m-%dT%H:%i:%s') as horario
         FROM pedidos WHERE id = ?"
    );
    $pedidoRow->execute([$pedidoId]);
    $pedido = $pedidoRow->fetch();

    json_response(montar_pedido($pdo, $pedido), 201);
```

---

### [arquivo: src/totem/assets/js/pagamento.js] — BUG-07, BUG-08

**Ação:** MODIFICAR — função `confirmarPagamento` (linhas 44–79)

**Problema BUG-07:** Cliques múltiplos rápidos chegam antes de `btn.disabled = true` porque a desabilitação é síncrona mas o evento pode disparar de novo antes de `disabled` ser processado pelo browser. O fix é uma flag de controle.

**Problema BUG-08:** `resultado.senha` não é validado. Se a API retornar `{}` sem o campo, o redirect fica `?senha=undefined`.

**Mudanças:**
- [ ] Adicionar variável `let processando = false` no escopo do módulo (junto com `let metodoPagamento = null`)
- [ ] Verificar e setar `processando` no início de `confirmarPagamento`
- [ ] Validar `resultado?.senha` antes de redirecionar; exibir erro se ausente

**Como fica (substituir linhas 5 e 44–79):**
```javascript
let metodoPagamento = null;
let processando = false;          // BUG-07: flag anti-duplicação

// ...

async function confirmarPagamento() {
  if (!metodoPagamento || processando) return;   // BUG-07
  processando = true;

  const btn = document.getElementById('btnConfirmar');
  btn.disabled = true;
  btn.textContent = 'Processando…';

  const alertEl = document.getElementById('alertErro');
  if (alertEl) alertEl.classList.add('hidden');

  try {
    const carrinho = getCarrinho();
    const payload  = {
      itens: carrinho.map(item => ({
        produtoId:  item.id,
        quantidade: item.quantidade,
        extras:     (item.extras || []).filter(e => (e.quantidade || 1) > 0).map(e => e.nome),
        remocoes:   item.remocoes || [],
        restricoes: item.restricoes || [],
      })),
      pagamento: metodoPagamento,
      total: calcularTotal(),
    };

    const resultado = await criarPedido(payload);

    if (!resultado?.senha) throw new Error('Resposta inválida: campo "senha" ausente');  // BUG-08

    limparCarrinho();
    window.location.href = `/src/totem/pages/senha.php?senha=${encodeURIComponent(resultado.senha)}`;
  } catch (err) {
    console.error(err);
    if (alertEl) alertEl.classList.remove('hidden');
    btn.disabled = false;
    btn.textContent = 'Confirmar pagamento';
    processando = false;  // BUG-07: libera para nova tentativa
  }
}
```

> **Atenção:** O campo `produtoId` foi corrigido de `produto: item.nome` para `produtoId: item.id` — verifique que o objeto de carrinho armazena `id` (número inteiro do produto). Se o campo for diferente, ajuste conforme a estrutura real do carrinho.

---

## Fase 3 — Race Conditions e UX (BUG-06, BUG-11)

### [arquivo: src/kds/assets/js/polling.js] — BUG-06

**Ação:** MODIFICAR — função `iniciarPolling` (linhas 10–28)

**Problema:** `setInterval` dispara `tick` a cada 2s independente da Promise anterior ter resolvido. Em rede lenta, múltiplas requisições ficam simultâneas; a última a chegar pode sobrescrever estado mais recente.

**Mudanças:**
- [ ] Adicionar flag `emAndamento` para garantir apenas um `tick` ativo por vez.
- [ ] Chamar `setConexao(false)` no painel — mas como `polling.js` não tem acesso direto ao DOM, repassar o erro ao callback (o painel já tem `setConexao`). Solução: aceitar callback de erro opcional.

**Como fica (substituir arquivo inteiro):**
```javascript
import { getPedidos } from '/src/shared/js/api.js';

let intervalId   = null;
let ultimoHash   = null;
let emAndamento  = false;    // BUG-06: impede requisições simultâneas

export function iniciarPolling(callback, intervalMs = 2000, onErro = null) {
  async function tick() {
    if (emAndamento) return;
    emAndamento = true;
    try {
      const pedidos = await getPedidos();
      const hash    = JSON.stringify(pedidos);

      if (hash !== ultimoHash) {
        ultimoHash = hash;
        callback(pedidos);
      }
    } catch (err) {
      console.warn('[polling] Erro ao buscar pedidos:', err.message);
      if (onErro) onErro(err);
    } finally {
      emAndamento = false;
    }
  }

  tick();
  intervalId = setInterval(tick, intervalMs);
  return intervalId;
}

export function pararPolling() {
  if (intervalId !== null) {
    clearInterval(intervalId);
    intervalId = null;
    ultimoHash = null;
    emAndamento = false;
  }
}
```

**Ajuste no chamador — painel.js:** passar o callback de erro para atualizar o dot de conexão:
```javascript
// Linha 16 do painel.js — substituir:
iniciarPolling(onNovosDados, 2000);

// Por:
iniciarPolling(onNovosDados, 2000, () => setConexao(false));
```

---

### [arquivo: src/display/assets/js/display.js] — BUG-06

**Ação:** MODIFICAR — função `iniciarPolling` interna (linhas 30–50)

**Mudanças:**
- [ ] Adicionar flag `emAndamento` local idêntica ao fix do polling.js.

**Como fica (substituir função `iniciarPolling`, linhas 30–50):**
```javascript
function iniciarPolling() {
  let emAndamento = false;   // BUG-06

  async function tick() {
    if (emAndamento) return;
    emAndamento = true;
    try {
      const senhas = await getSenhas();
      setConexao(true);

      const hash = JSON.stringify(senhas);
      if (hash !== estadoAnterior) {
        const anterior = estadoAnterior ? JSON.parse(estadoAnterior) : null;
        estadoAnterior = hash;
        renderSenhas(senhas, anterior);
      }
    } catch (err) {
      console.warn('[display] Erro no polling:', err.message);
      setConexao(false);
    } finally {
      emAndamento = false;
    }
  }

  tick();
  intervaloPolling = setInterval(tick, 2000);
}
```

---

### [arquivo: src/admin/assets/js/dashboard.js] — BUG-11

**Ação:** MODIFICAR — linhas 124–125

**Problema:** `loadMetricas()` e `loadUltimosPedidos()` são disparadas sem `await` no escopo de módulo de nível superior. Erros de inicialização (ex: sessão expirada no meio) não são capturados pelo contexto global, podendo mascarar a causa real.

**Mudanças:**
- [ ] Substituir as duas chamadas soltas por um bloco `try/catch` com `await`.

**Como fica (substituir linhas 124–125):**
```javascript
try {
  await Promise.all([loadMetricas(), loadUltimosPedidos()]);
} catch (err) {
  console.error('[dashboard] Erro na inicialização:', err);
}
```

---

## Fase 4 — Consistência do Backend (BUG-09, BUG-10, BUG-12, BUG-13)

### [arquivo: backend/api/adicionais.php] — BUG-09, BUG-10

**Ação:** MODIFICAR

**Problema BUG-09:** `PUT` não valida se `produto_id` existe (o `POST` valida, o `PUT` não).

**Problema BUG-10:** `DELETE` é físico. Como `pedido_item_extras` armazena o nome como string (sem FK para `adicionais`), o histórico de pedidos não é diretamente quebrado. Porém, a inconsistência com o padrão soft-delete do sistema cria risco se no futuro for adicionada uma FK. O fix é trocar por soft-delete.

**Mudanças:**
- [ ] No bloco `PUT` (linha 54–76): adicionar validação de existência do adicional antes do UPDATE. O campo `nome` também precisa de guard.
- [ ] No bloco `DELETE` (linhas 78–83): trocar `DELETE FROM adicionais` por `UPDATE adicionais SET ativo = 0`.
- [ ] No bloco `GET` (linhas 8–26): filtrar apenas adicionais com `ativo = 1`.
- [ ] **Atenção:** `produto.php` linha 38 faz `DELETE FROM adicionais WHERE produto_id = ?` ao atualizar um produto — esse DELETE deve ser mantido como físico (é uma recriação intencional dos extras, não uma exclusão de negócio).

**Como fica o bloco PUT (substituir linhas 54–76):**
```php
if ($method === 'PUT') {
    require_admin();
    $id   = (int)$routeParams['id'];
    $data = input_json();

    if (empty($data['nome'])) error_response('Campo "nome" obrigatório');

    $check = $pdo->prepare('SELECT id FROM adicionais WHERE id = ? AND ativo = 1');
    $check->execute([$id]);
    if (!$check->fetch()) error_response('Adicional não encontrado', 404);

    $stmt = $pdo->prepare('UPDATE adicionais SET nome = ?, preco = ? WHERE id = ?');
    $stmt->execute([$data['nome'], (float)($data['preco'] ?? 0), $id]);

    $row = $pdo->prepare(
        'SELECT a.id, a.nome, a.preco, a.produto_id as produtoId, p.nome as produtoNome
         FROM adicionais a JOIN produtos p ON a.produto_id = p.id WHERE a.id = ?'
    );
    $row->execute([$id]);
    $r = $row->fetch();
    if (!$r) error_response('Adicional não encontrado', 404);
    json_response([
        'id'          => (int)$r['id'],
        'nome'        => $r['nome'],
        'preco'       => (float)$r['preco'],
        'produtoId'   => (int)$r['produtoId'],
        'produtoNome' => $r['produtoNome'],
    ]);
}
```

**Como fica o bloco DELETE (substituir linhas 78–83):**
```php
if ($method === 'DELETE') {
    require_admin();
    $id = (int)$routeParams['id'];
    $pdo->prepare('UPDATE adicionais SET ativo = 0 WHERE id = ?')->execute([$id]);
    json_response(['ok' => true]);
}
```

**Como fica o bloco GET (substituir linhas 8–26) para respeitar `ativo`:**
```php
if ($method === 'GET') {
    $rows = $pdo->query(
        'SELECT a.id, a.nome, a.preco, a.produto_id as produtoId, p.nome as produtoNome
         FROM adicionais a
         JOIN produtos p ON a.produto_id = p.id
         WHERE a.ativo = 1 AND p.ativo = 1
         ORDER BY a.produto_id, a.id'
    )->fetchAll();

    $adicionais = array_map(fn($r) => [
        'id'          => (int)$r['id'],
        'nome'        => $r['nome'],
        'preco'       => (float)$r['preco'],
        'produtoId'   => (int)$r['produtoId'],
        'produtoNome' => $r['produtoNome'],
    ], $rows);

    json_response($adicionais);
}
```

> **Migration necessária:** A tabela `adicionais` não tem coluna `ativo`. Antes de fazer deploy desta fase, executar no banco:
> ```sql
> ALTER TABLE adicionais ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1;
> ```

---

### [arquivo: backend/api/produto.php] — BUG-12

**Ação:** MODIFICAR — bloco `PUT` (linhas 16–52)

**Problema:** `PUT /api/produtos/:id` não valida `nome` e `preco` antes do UPDATE. Se ausentes, PHP usa `null` silenciosamente.

**Mudanças:**
- [ ] Adicionar validação de `nome` e `preco` após `input_json()` e antes do UPDATE.

**Como fica (adicionar após linha 18, `$data = input_json();`):**
```php
if ($method === 'PUT') {
    require_admin();
    $data = input_json();

    if (empty($data['nome']) || strlen($data['nome']) > 200)
        error_response('Campo "nome" inválido ou ausente');
    if (!isset($data['preco']) || !is_numeric($data['preco']) || (float)$data['preco'] < 0)
        error_response('Campo "preco" inválido ou ausente');

    $check = $pdo->prepare('SELECT id FROM produtos WHERE id = ? AND ativo = 1');
    // ... resto permanece igual
```

---

### [arquivo: backend/config/helpers.php] — BUG-13

**Ação:** MODIFICAR — função `cors_headers()` (linhas 32–36)

**Problema:** `Access-Control-Allow-Origin: *` permite qualquer origem fazer requisições à API. Dado que a autenticação é por sessão (cookie), o risco prático em produção é limitado (cookies não são enviados em cross-origin por padrão com `*`), mas é uma má prática.

**Mudanças:**
- [ ] Restringir o `Allow-Origin` ao IP/domínio do servidor (por ora, o IP do servidor).
- [ ] Em produção real com domínio, substituir pelo domínio correto.

**Como fica (substituir função `cors_headers`, linhas 32–36):**
```php
function cors_headers(): void {
    $origem = $_SERVER['HTTP_ORIGIN'] ?? '';
    $permitidas = ['http://159.223.165.79:8080', 'http://localhost:8080'];
    if (in_array($origem, $permitidas, true)) {
        header("Access-Control-Allow-Origin: {$origem}");
    }
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Allow-Credentials: true');
}
```

---

## Critérios de sucesso

### Verificação automática
- [ ] `php -l backend/api/pedidos.php` sem erros de sintaxe
- [ ] `php -l backend/api/adicionais.php` sem erros
- [ ] `php -l backend/api/produto.php` sem erros
- [ ] `php -l backend/config/helpers.php` sem erros

### Verificação manual — Fase 1
- [ ] Fazer login com senha errada → mensagem de erro aparece, botão fica habilitado para nova tentativa (admin e KDS)
- [ ] Acessar `http://159.223.165.79:8080/src/kds/pages/index.php` sem estar logado → redireciona para login
- [ ] Clicar em "Sair" no KDS → redireciona para login E sessão é destruída (verificar com `ls /var/lib/php/sessions/`)
- [ ] Fazer login no admin e KDS → não redireciona de volta para login (BUG-04)

### Verificação manual — Fase 2
- [ ] Criar pedido com múltiplos itens e extras → pedido completo no KDS
- [ ] Clicar rapidamente várias vezes em "Confirmar pagamento" → apenas um pedido criado
- [ ] Verificar redirect após pedido → URL com senha válida (ex: `?senha=%23001`), não `?senha=undefined`

### Verificação manual — Fase 3
- [ ] Desconectar o servidor por 10s e reconectar → KDS e Display se recuperam sem duplicar requisições
- [ ] Abrir o dashboard → métricas e últimos pedidos carregam (verificar console do browser, sem erros não capturados)

### Verificação manual — Fase 4
- [ ] Deletar um adicional → não aparece mais no GET; existência histórica de pedidos não é afetada
- [ ] `PUT /api/adicionais/:id` sem `nome` → retorna 400 com mensagem clara
- [ ] `PUT /api/produtos/:id` sem `nome` ou `preco` → retorna 400
- [ ] Verificar header `Access-Control-Allow-Origin` nas respostas da API → não é mais `*`

---

## Riscos e dependências

| Risco | Mitigação |
|-------|-----------|
| BUG-04 depende de diagnóstico no servidor antes do fix | Executar os comandos de diagnóstico ANTES de implementar as outras fases |
| BUG-10 requer migration (`ALTER TABLE adicionais ADD COLUMN ativo`) | Executar a migration no servidor antes de fazer deploy da Fase 4 |
| Fix do `pagamento.js` usa `item.id` — verificar estrutura real do objeto de carrinho | Antes de implementar, ler `src/totem/assets/js/carrinho.js` e confirmar o nome do campo ID |
| Fix de CORS em helpers.php afeta todos os endpoints | Testar em dev antes de deploy; se o sistema ficar inacessível, reverter para `*` temporariamente |
| `produto.php` linha 38 faz DELETE físico em adicionais ao editar produto — correto por design (recriação) | Não alterar esse DELETE; é comportamento intencional, não um bug |
