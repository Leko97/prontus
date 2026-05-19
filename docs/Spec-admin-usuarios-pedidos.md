# Spec — Admin: Gestão de Usuários e Histórico de Pedidos

**Data:** 2026-05-18
**Baseado em:** análise direta do código-fonte (admin, backend, shared)

---

## Resumo

Adicionar ao painel admin duas novas seções: **Gestão de Usuários** (CRUD completo com perfis admin/cozinha) e **Histórico de Pedidos** (listagem filtrada com visualização de detalhes). A tabela `usuarios` já existe no banco. Serão criados 5 endpoints de API, 2 helpers de backend, 4 páginas PHP, 4 módulos JS e atualizada a sidebar.

---

## Decisões de design

| Decisão | Escolha | Justificativa |
|---|---|---|
| Paginação de pedidos | Client-side (limite 300 registros por query) | Consistência com o padrão do projeto |
| Edição de status de pedido | Somente visualização — sem edição pelo admin | Status é gerenciado pelo KDS |
| Auto-exclusão | Admin não pode excluir a si mesmo | Proteção backend + frontend |
| Senha no edit de usuário | Campo opcional — vazio = mantém hash atual | Não forçar redefinição desnecessária |
| Histórico padrão | Últimos 30 dias | Equilíbrio entre dados visíveis e performance |
| Reutilização de `montar_pedido()` | Extrair para `_pedidos_helpers.php` | Evita duplicação entre `pedidos.php` e `pedidos_historico.php` |

---

## Arquivos a criar / modificar

| Arquivo | Ação | O que muda |
|---|---|---|
| `backend/api/_pedidos_helpers.php` | CRIAR | Extrai `montar_pedido()` para reuso |
| `backend/api/pedidos.php` | MODIFICAR | Incluir `_pedidos_helpers.php` em vez de ter a função inline |
| `backend/api/pedidos_historico.php` | CRIAR | GET com filtros: data_inicio, data_fim, status, senha |
| `backend/api/usuarios.php` | CRIAR | GET listagem + POST criar |
| `backend/api/usuario.php` | CRIAR | GET por ID + PUT editar + DELETE soft-delete |
| `backend/api/index.php` | MODIFICAR | Registrar 6 novas rotas |
| `src/shared/js/api.js` | MODIFICAR | Adicionar 6 funções de API (usuários + histórico) |
| `src/admin/assets/js/admin.js` | MODIFICAR | Adicionar Usuários e Pedidos ao `NAV_LINKS` |
| `src/admin/pages/usuarios.php` | CRIAR | Listagem de usuários com busca e modal de exclusão |
| `src/admin/pages/usuario-form.php` | CRIAR | Formulário criar/editar usuário |
| `src/admin/assets/js/usuarios.js` | CRIAR | Lógica da listagem de usuários |
| `src/admin/assets/js/usuario-form.js` | CRIAR | Lógica do formulário de usuário |
| `src/admin/pages/pedidos.php` | CRIAR | Histórico com filtros e modal de detalhes |
| `src/admin/assets/js/pedidos-admin.js` | CRIAR | Lógica da listagem e detalhes de pedidos |

---

## Fase 1 — Backend: Helper de pedidos

### [arquivo: `backend/api/_pedidos_helpers.php`]
**Ação:** CRIAR

**Mudanças:**
- [ ] Mover a função `montar_pedido(PDO $pdo, array $pedido): array` de `pedidos.php` para este arquivo
- [ ] A função permanece idêntica — monta itens, extras, remoções e restrições

**Trecho de referência:**
```php
<?php
function montar_pedido(PDO $pdo, array $pedido): array {
    $stmtItens = $pdo->prepare(
        'SELECT id, produto_id, produto_nome, preco_unitario, quantidade
         FROM pedido_itens WHERE pedido_id = ? ORDER BY id'
    );
    $stmtItens->execute([(int)$pedido['id']]);
    $itensRows = $stmtItens->fetchAll();

    $stmtExtras   = $pdo->prepare('SELECT nome FROM pedido_item_extras WHERE pedido_item_id = ?');
    $stmtRemocoes = $pdo->prepare('SELECT nome FROM pedido_item_remocoes WHERE pedido_item_id = ?');
    $stmtRest     = $pdo->prepare('SELECT restricao_slug FROM pedido_item_restricoes WHERE pedido_item_id = ?');

    $itens = [];
    foreach ($itensRows as $item) {
        $itemId = (int)$item['id'];
        $stmtExtras->execute([$itemId]);
        $extras = array_column($stmtExtras->fetchAll(), 'nome');
        $stmtRemocoes->execute([$itemId]);
        $remocoes = array_column($stmtRemocoes->fetchAll(), 'nome');
        $stmtRest->execute([$itemId]);
        $restricoes = array_column($stmtRest->fetchAll(), 'restricao_slug');

        $itens[] = [
            'produto'    => $item['produto_nome'],
            'quantidade' => (int)$item['quantidade'],
            'extras'     => $extras,
            'remocoes'   => $remocoes,
            'restricoes' => $restricoes,
        ];
    }

    return [
        'id'          => (int)$pedido['id'],
        'senha'       => $pedido['senha'],
        'status'      => $pedido['status'],
        'pagamento'   => $pedido['pagamento'],
        'total'       => (float)$pedido['total'],
        'horario'     => $pedido['horario'],
        'preparado_em'=> $pedido['preparado_em'],
        'itens'       => $itens,
    ];
}
```

> **Nota:** adicionar `pagamento`, `total` e `preparado_em` ao retorno (o original não os incluía — o histórico vai precisar deles).

---

### [arquivo: `backend/api/pedidos.php`]
**Ação:** MODIFICAR

**Mudanças:**
- [ ] Substituir a função `montar_pedido()` inline por `require_once __DIR__ . '/_pedidos_helpers.php';` no topo
- [ ] Nenhuma outra alteração

---

## Fase 2 — Backend: Endpoint de histórico de pedidos

### [arquivo: `backend/api/pedidos_historico.php`]
**Ação:** CRIAR

**Mudanças:**
- [ ] Requer `require_auth()` (admin e cozinha podem visualizar)
- [ ] Aceita query params: `data_inicio` (YYYY-MM-DD), `data_fim` (YYYY-MM-DD), `status`, `senha`, `limite` (padrão 300)
- [ ] Padrão: últimos 30 dias quando nenhuma data for fornecida
- [ ] Retorna array de pedidos via `montar_pedido()` — inclui `pagamento`, `total`, `preparado_em`
- [ ] Validar `status` contra `STATUS_VALIDOS` quando fornecido

**Trecho de referência:**
```php
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/_pedidos_helpers.php';

require_auth();

$pdo = get_pdo();

$dataInicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$dataFim    = $_GET['data_fim']    ?? date('Y-m-d');
$status     = $_GET['status']      ?? '';
$senha      = $_GET['senha']       ?? '';
$limite     = min((int)($_GET['limite'] ?? 300), 500);

// Validação de datas
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataInicio)) $dataInicio = date('Y-m-d', strtotime('-30 days'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataFim))    $dataFim    = date('Y-m-d');
if ($status && !in_array($status, STATUS_VALIDOS, true)) $status = '';

$where  = ["DATE(horario) BETWEEN ? AND ?"];
$params = [$dataInicio, $dataFim];

if ($status) { $where[] = 'status = ?'; $params[] = $status; }
if ($senha)  { $where[] = 'senha LIKE ?'; $params[] = '%' . $senha . '%'; }

$sql = "SELECT id, senha, status, pagamento, total,
               DATE_FORMAT(horario, '%Y-%m-%dT%H:%i:%s') as horario,
               DATE_FORMAT(preparado_em, '%Y-%m-%dT%H:%i:%s') as preparado_em
        FROM pedidos
        WHERE " . implode(' AND ', $where) . "
        ORDER BY horario DESC
        LIMIT ?";
$params[] = $limite;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

json_response(array_map(fn($p) => montar_pedido($pdo, $p), $rows));
```

---

## Fase 3 — Backend: API de Usuários

### [arquivo: `backend/api/usuarios.php`]
**Ação:** CRIAR

**Mudanças:**
- [ ] GET: `require_admin()`, retorna `SELECT id, nome, email, perfil, ativo FROM usuarios ORDER BY nome`
- [ ] POST: `require_admin()`, valida nome (obrigatório), email (obrigatório, formato válido, único), senha (obrigatória, mínimo 6 chars), perfil (admin|cozinha)
- [ ] POST: `password_hash($senha, PASSWORD_BCRYPT)`, INSERT, retorna usuário criado sem senha_hash

**Trecho de referência:**
```php
if ($method === 'GET') {
    require_admin();
    $stmt = $pdo->query('SELECT id, nome, email, perfil, ativo FROM usuarios ORDER BY nome');
    json_response($stmt->fetchAll());
}

if ($method === 'POST') {
    require_admin();
    $data = input_json();

    $nome   = trim($data['nome']  ?? '');
    $email  = trim($data['email'] ?? '');
    $senha  = $data['senha']      ?? '';
    $perfil = $data['perfil']     ?? 'cozinha';

    if (!$nome)  error_response('Campo "nome" obrigatório');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) error_response('E-mail inválido');
    if (strlen($senha) < 6) error_response('Senha deve ter pelo menos 6 caracteres');
    if (!in_array($perfil, PERFIS_VALIDOS, true)) error_response('Perfil inválido');

    // Verificar unicidade do e-mail
    $check = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
    $check->execute([$email]);
    if ($check->fetch()) error_response('E-mail já cadastrado', 409);

    $stmt = $pdo->prepare(
        'INSERT INTO usuarios (nome, email, senha_hash, perfil) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$nome, $email, password_hash($senha, PASSWORD_BCRYPT), $perfil]);
    $id = (int)$pdo->lastInsertId();

    json_response(['id' => $id, 'nome' => $nome, 'email' => $email, 'perfil' => $perfil, 'ativo' => 1], 201);
}
```

---

### [arquivo: `backend/api/usuario.php`]
**Ação:** CRIAR

**Mudanças:**
- [ ] GET `/:id`: `require_admin()`, busca por ID, retorna sem senha_hash
- [ ] PUT `/:id`: `require_admin()`, atualiza nome/email/perfil; se `senha` vier no body e não for vazia, atualiza hash; verificar unicidade de email (excluindo o próprio ID)
- [ ] DELETE `/:id`: `require_admin()`, checar que `$id !== $usuario_logado['id']` (senão 403), `UPDATE usuarios SET ativo = 0 WHERE id = ?`

**Trecho de referência:**
```php
if ($method === 'GET') {
    require_admin();
    $stmt = $pdo->prepare('SELECT id, nome, email, perfil, ativo FROM usuarios WHERE id = ?');
    $stmt->execute([$id]);
    $u = $stmt->fetch();
    if (!$u) error_response('Usuário não encontrado', 404);
    json_response($u);
}

if ($method === 'PUT') {
    $logado = require_admin();
    $data   = input_json();

    $nome   = trim($data['nome']  ?? '');
    $email  = trim($data['email'] ?? '');
    $perfil = $data['perfil']     ?? '';
    $senha  = $data['senha']      ?? '';

    if (!$nome)  error_response('Campo "nome" obrigatório');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) error_response('E-mail inválido');
    if (!in_array($perfil, PERFIS_VALIDOS, true)) error_response('Perfil inválido');

    $check = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? AND id != ?');
    $check->execute([$email, $id]);
    if ($check->fetch()) error_response('E-mail já usado por outro usuário', 409);

    if ($senha) {
        if (strlen($senha) < 6) error_response('Senha deve ter pelo menos 6 caracteres');
        $pdo->prepare('UPDATE usuarios SET nome=?, email=?, perfil=?, senha_hash=? WHERE id=?')
            ->execute([$nome, $email, $perfil, password_hash($senha, PASSWORD_BCRYPT), $id]);
    } else {
        $pdo->prepare('UPDATE usuarios SET nome=?, email=?, perfil=? WHERE id=?')
            ->execute([$nome, $email, $perfil, $id]);
    }
    json_response(['id' => $id, 'nome' => $nome, 'email' => $email, 'perfil' => $perfil]);
}

if ($method === 'DELETE') {
    $logado = require_admin();
    if ((int)$logado['id'] === $id) error_response('Você não pode excluir sua própria conta', 403);
    $pdo->prepare('UPDATE usuarios SET ativo = 0 WHERE id = ?')->execute([$id]);
    json_response(['ok' => true]);
}
```

---

## Fase 4 — Router: novas rotas

### [arquivo: `backend/api/index.php`]
**Ação:** MODIFICAR

**Mudanças:**
- [ ] Adicionar rota de histórico **antes** das rotas de `/pedidos` existentes (regex mais específico primeiro)
- [ ] Adicionar 5 rotas de usuários

**Trecho — novas entradas no array `$routes`:**
```php
// Histórico (antes das rotas de pedidos para evitar conflito)
['GET',    '#^/pedidos/historico$#',      'pedidos_historico.php'],

// Usuários
['GET',    '#^/usuarios$#',               'usuarios.php'],
['POST',   '#^/usuarios$#',               'usuarios.php'],
['GET',    '#^/usuarios/(\d+)$#',         'usuario.php',  ['id']],
['PUT',    '#^/usuarios/(\d+)$#',         'usuario.php',  ['id']],
['DELETE', '#^/usuarios/(\d+)$#',         'usuario.php',  ['id']],
```

---

## Fase 5 — api.js: novas funções

### [arquivo: `src/shared/js/api.js`]
**Ação:** MODIFICAR

**Mudanças:**
- [ ] Adicionar bloco de funções de usuários após as funções de adicionais
- [ ] Adicionar `getPedidosHistorico(params)` após as funções de pedidos existentes

**Trecho de referência:**
```js
/* -------- Usuários -------- */

export async function getUsuarios() {
  return fetchAPI('/usuarios');
}

export async function getUsuarioById(id) {
  return fetchAPI(`/usuarios/${id}`);
}

export async function criarUsuario(dados) {
  return fetchAPI('/usuarios', { method: 'POST', body: JSON.stringify(dados) });
}

export async function atualizarUsuario(id, dados) {
  return fetchAPI(`/usuarios/${id}`, { method: 'PUT', body: JSON.stringify(dados) });
}

export async function deletarUsuario(id) {
  return fetchAPI(`/usuarios/${id}`, { method: 'DELETE' });
}

/* -------- Histórico de pedidos -------- */

export async function getPedidosHistorico(params = {}) {
  const qs = new URLSearchParams(params).toString();
  return fetchAPI(`/pedidos/historico${qs ? '?' + qs : ''}`);
}
```

---

## Fase 6 — Sidebar

### [arquivo: `src/admin/assets/js/admin.js`]
**Ação:** MODIFICAR

**Mudanças:**
- [ ] Adicionar duas entradas ao array `NAV_LINKS`, após `restricoes`:

```js
const NAV_LINKS = [
  { href: '/src/admin/pages/dashboard.php',    icon: '📊', label: 'Dashboard' },
  { href: '/src/admin/pages/produtos.php',     icon: '🍔', label: 'Produtos' },
  { href: '/src/admin/pages/categorias.php',   icon: '🗂️',  label: 'Categorias' },
  { href: '/src/admin/pages/adicionais.php',   icon: '➕', label: 'Adicionais' },
  { href: '/src/admin/pages/restricoes.php',   icon: '⚠️',  label: 'Restrições' },
  { href: '/src/admin/pages/usuarios.php',     icon: '👥', label: 'Usuários' },
  { href: '/src/admin/pages/pedidos.php',      icon: '📋', label: 'Pedidos' },
];
```

---

## Fase 7 — Página: Listagem de Usuários

### [arquivo: `src/admin/pages/usuarios.php`]
**Ação:** CRIAR

**Estrutura HTML:**
- Segue o padrão de `produtos.php`: `admin-layout`, `sidebar`, `admin-content`, `admin-topbar`, `admin-main`
- `pageTitle = 'Usuários — Prontus Admin'`
- Topbar: título "Usuários" + botão "+ Novo Usuário" → link para `usuario-form.php`
- `page-header` com subtítulo "Gerencie os acessos ao sistema"
- `search-bar` com input de busca (id `busca`)
- `table-wrapper` com `data-table`:
  - Colunas: `Nome`, `E-mail`, `Perfil`, `Status`, `Ações`
  - `tbody` id `usuariosTbody` com spinner inicial
- Modal id `modalExcluir` (padrão idêntico ao de `produtos.php`)

**Referência de colunas da tabela:**
```html
<thead>
  <tr>
    <th>Nome</th>
    <th>E-mail</th>
    <th>Perfil</th>
    <th>Status</th>
    <th>Ações</th>
  </tr>
</thead>
```

- Script: `<script type="module" src="/src/admin/assets/js/usuarios.js"></script>`

---

### [arquivo: `src/admin/assets/js/usuarios.js`]
**Ação:** CRIAR

**Mudanças:**
- [ ] `await initAdminPage()` — proteção de rota + sidebar
- [ ] `load()` chama `getUsuarios()`, renderiza tabela
- [ ] `renderTabela(usuarios)` gera linhas com:
  - Badge de perfil: `admin` → badge laranja (`.badge-warning`); `cozinha` → badge azul (`.badge-info`)
  - Badge de status: `ativo=1` → `.badge-success` "Ativo"; `ativo=0` → `.badge-neutral` "Inativo"
  - Botões de ação: editar (`onclick="location.href='/src/admin/pages/usuario-form.php?id=X'"`) e excluir
- [ ] Busca filtra por nome ou e-mail (client-side, mesmo padrão de `produtos.js`)
- [ ] Botão excluir abre `modalExcluir` (mesmo padrão de `produtos.js`)
- [ ] Confirmação de exclusão chama `deletarUsuario(id)`, atualiza lista localmente, exibe toast

**Trecho de renderização de linha:**
```js
`<tr>
  <td><strong>${u.nome}</strong></td>
  <td>${u.email}</td>
  <td>
    <span class="badge ${u.perfil === 'admin' ? 'badge-warning' : 'badge-info'}">
      ${u.perfil === 'admin' ? 'Admin' : 'Cozinha'}
    </span>
  </td>
  <td>
    <span class="badge ${u.ativo ? 'badge-success' : 'badge-neutral'}">
      ${u.ativo ? 'Ativo' : 'Inativo'}
    </span>
  </td>
  <td>
    <div class="table-actions">
      <button class="btn-action btn-edit" title="Editar"
        onclick="location.href='/src/admin/pages/usuario-form.php?id=${u.id}'">✏️</button>
      <button class="btn-action btn-delete" title="Excluir"
        data-id="${u.id}" data-nome="${u.nome}">🗑️</button>
    </div>
  </td>
</tr>`
```

---

## Fase 8 — Página: Formulário de Usuário

### [arquivo: `src/admin/pages/usuario-form.php`]
**Ação:** CRIAR

**Estrutura HTML:**
- Segue o padrão de `produto-form.php`
- `pageTitle` dinâmico: "Novo Usuário" ou "Editar Usuário"
- Topbar: título dinâmico + botão "Cancelar" → `usuarios.php`
- Formulário id `formUsuario` com campos:
  - `nome` — input text, required
  - `email` — input email, required
  - `senha` — input password, placeholder "Deixe em branco para manter a senha atual" (no edit)
  - `perfil` — select com options `admin` / `cozinha`
- Botão submit: "Salvar usuário" / "Criar usuário"
- Alert de erro id `alertErro` (hidden por padrão)
- Script: `<script type="module" src="/src/admin/assets/js/usuario-form.js"></script>`

---

### [arquivo: `src/admin/assets/js/usuario-form.js`]
**Ação:** CRIAR

**Mudanças:**
- [ ] `await initAdminPage()`
- [ ] Detecta `?id=X` na URL → modo edição; sem param → modo criação
- [ ] No modo edição: `getUsuarioById(id)` preenche o form; label da senha muda para "Nova senha (opcional)"
- [ ] Submit do form:
  - Monta objeto `{ nome, email, perfil, senha }` — omite `senha` se campo vazio no edit
  - Chama `criarUsuario()` ou `atualizarUsuario(id, dados)` conforme o modo
  - Em sucesso: `showToast('Usuário salvo!')` + redirect para `usuarios.php` após 800ms
  - Em erro: exibe `alertErro` com a mensagem retornada pela API
- [ ] Desabilita botão durante request (mesmo padrão de `produto-form.js`)

---

## Fase 9 — Página: Histórico de Pedidos

### [arquivo: `src/admin/pages/pedidos.php`]
**Ação:** CRIAR

**Estrutura HTML:**
- `pageTitle = 'Pedidos — Prontus Admin'`
- Topbar: título "Pedidos" (sem botão de ação)
- `page-header` com subtítulo "Histórico de pedidos realizados"
- **Barra de filtros** (nova — não existe em outras páginas):
  ```html
  <div class="filtros-bar">
    <input type="date" id="filtroDataInicio">
    <input type="date" id="filtroDataFim">
    <select id="filtroStatus">
      <option value="">Todos os status</option>
      <option value="recebido">Recebido</option>
      <option value="em-preparo">Em Preparo</option>
      <option value="pronto">Pronto</option>
      <option value="finalizado">Finalizado</option>
    </select>
    <input type="text" id="filtroBusca" placeholder="Buscar senha…">
    <button class="btn btn-primary btn-sm" id="btnFiltrar">Filtrar</button>
  </div>
  ```
- `table-wrapper` com `data-table`:
  - Colunas: `Senha`, `Data/Hora`, `Status`, `Pagamento`, `Total`, `Itens`, `Ações`
  - `tbody` id `pedidosTbody`
- **Modal de detalhes** id `modalDetalhes`:
  - Título: "Pedido #XXX"
  - Corpo: lista de itens com extras/remoções/restrições
  - Rodapé: Total, Status, Pagamento, Horário, Tempo de preparo
  - Botão fechar apenas (sem ações de edição)
- Script: `<script type="module" src="/src/admin/assets/js/pedidos-admin.js"></script>`

**CSS adicional para filtros** (adicionar em `admin.css`):
```css
.filtros-bar {
  display: flex;
  gap: var(--space-3);
  flex-wrap: wrap;
  align-items: center;
  margin-bottom: var(--space-6);
  background: var(--color-surface);
  padding: var(--space-4) var(--space-5);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
}

.filtros-bar input[type="date"],
.filtros-bar select,
.filtros-bar input[type="text"] {
  height: var(--btn-height-sm);
  padding: 0 var(--space-3);
  border: 1.5px solid var(--color-border);
  border-radius: var(--radius-sm);
  font-size: var(--text-sm);
  color: var(--color-text);
  background: var(--color-bg);
}
```

---

### [arquivo: `src/admin/assets/js/pedidos-admin.js`]
**Ação:** CRIAR

**Mudanças:**
- [ ] `await initAdminPage()`
- [ ] Inicializa `filtroDataInicio` e `filtroDataFim` com os últimos 30 dias
- [ ] `load(params)` chama `getPedidosHistorico(params)`, renderiza tabela
- [ ] `renderTabela(pedidos)` gera linhas:
  - Badge de status: cores por status (`recebido` → info, `em-preparo` → warning, `pronto` → success, `finalizado` → neutral)
  - Pagamento: mostrar `—` se null
  - Total: `formatCurrency(p.total)`
  - Itens: count de `p.itens.length`
  - Botão "👁 Ver" abre modal de detalhes
- [ ] `btnFiltrar` lê os 4 filtros, monta params e chama `load(params)`
- [ ] `abrirDetalhes(pedido)` preenche e abre `modalDetalhes`:
  - Lista cada item com nome, quantidade, extras, remoções e tags de restrição
  - Exibe total formatado, status, pagamento, horário formatado, tempo de preparo (`preparado_em - horario` em minutos)
- [ ] `load()` chamado no topo com os defaults de data

**Trecho de renderização de linha:**
```js
`<tr>
  <td><strong>${p.senha}</strong></td>
  <td>${formatDateTime(p.horario)}</td>
  <td><span class="badge ${badgeStatus(p.status)}">${statusLabel(p.status)}</span></td>
  <td>${p.pagamento ?? '—'}</td>
  <td>${formatCurrency(p.total)}</td>
  <td>${p.itens.length} item(s)</td>
  <td>
    <button class="btn-action" title="Ver detalhes" data-id="${p.id}">👁</button>
  </td>
</tr>`
```

**Função auxiliar `badgeStatus(status)`:**
```js
function badgeStatus(status) {
  const map = {
    'recebido':   'badge-info',
    'em-preparo': 'badge-warning',
    'pronto':     'badge-success',
    'finalizado': 'badge-neutral',
  };
  return map[status] ?? 'badge-neutral';
}
```

**Função auxiliar `formatDateTime(iso)` — adicionar em `utils.js`:**
```js
export function formatDateTime(isoString) {
  if (!isoString) return '—';
  return new Date(isoString).toLocaleString('pt-BR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  });
}
```

---

## Critérios de sucesso

### Verificação automática
- [ ] Nenhum erro 500 nos novos endpoints (testar com curl ou browser DevTools)
- [ ] `GET /api/usuarios` retorna 401 sem sessão, 403 com perfil cozinha
- [ ] `DELETE /api/usuarios/:id` retorna 403 quando `id` é o do usuário logado
- [ ] `POST /api/usuarios` com e-mail duplicado retorna 409
- [ ] `GET /api/pedidos/historico` com e sem query params retorna array válido
- [ ] Novos arquivos JS sem erros de console no carregamento

### Verificação manual
- [ ] Criar usuário com perfil "cozinha" → aparece na listagem com badge azul
- [ ] Editar usuário sem preencher senha → senha não muda
- [ ] Clicar "Excluir" no próprio usuário admin → botão não aparece (ou backend bloqueia com toast de erro)
- [ ] Filtrar pedidos por status "finalizado" + data de hoje → lista correta
- [ ] Abrir modal de detalhes em pedido com extras e remoções → itens completos exibidos
- [ ] Sidebar mostra "Usuários" e "Pedidos" como itens ativos na página correspondente
- [ ] Usuário com perfil "cozinha" não consegue acessar `/api/usuarios` (403)

---

## Riscos e dependências

| Risco | Mitigação |
|---|---|
| `montar_pedido()` referenciada em `pedidos.php` — mover vai quebrar se a extração falhar | Testar KDS após a migração para o helper |
| E-mail do admin seedado pode conflitar com o campo único ao tentar criar outro com mesmo e-mail | Backend retorna 409 com mensagem clara |
| Histórico com muitos pedidos pode ser lento sem índice em `horario` | Índice `idx_horario` já existe no schema (`001_schema.sql:61`) |
| Admin pode criar usuário `cozinha` com e-mail do próprio admin | Unicidade de e-mail garante bloqueio no banco |
| Rota `/pedidos/historico` precisa vir antes de `/pedidos/(\d+)/status` no array de rotas | Já contemplado na Fase 4 — regex `#^/pedidos/historico$#` não captura dígitos |
