# Spec — Backend PHP Prontus v1

**Data:** 2026-05-11
**Baseado em:** docs/prd.md, docs/Spec.md (frontend), src/shared/js/api.js

---

## Resumo

Implementação do backend PHP puro para o Prontus: roteador único (`index.php`) que despacha para handlers por rota, banco MySQL com schema relacional normalizado, autenticação por sessão PHP com dois perfis (admin / cozinha), e todos os endpoints que o frontend já consome via `api.js`. O objetivo é substituir `USE_MOCK = true` por `USE_MOCK = false` no frontend sem nenhuma outra alteração.

---

## Arquivos a criar

| Arquivo | Ação | O que faz |
|---|---|---|
| `backend/api/index.php` | CRIAR | Router principal — despacha todas as rotas `/api/*` |
| `backend/config/database.php` | CRIAR | Factory de conexão PDO MySQL |
| `backend/config/constants.php` | CRIAR | Constantes de status, perfil, DB config |
| `backend/config/helpers.php` | CRIAR | `json_response()`, `require_auth()`, `require_admin()`, `input_json()` |
| `backend/api/cardapio.php` | CRIAR | `GET /api/cardapio` |
| `backend/api/pedidos.php` | CRIAR | `GET /api/pedidos`, `POST /api/pedidos` |
| `backend/api/pedidos_status.php` | CRIAR | `PATCH /api/pedidos/:id/status` |
| `backend/api/senhas.php` | CRIAR | `GET /api/senhas` |
| `backend/api/metricas.php` | CRIAR | `GET /api/metricas` |
| `backend/api/produtos.php` | CRIAR | `GET /api/produtos`, `POST /api/produtos` |
| `backend/api/produto.php` | CRIAR | `GET /api/produtos/:id`, `PUT /api/produtos/:id`, `DELETE /api/produtos/:id` |
| `backend/api/categorias.php` | CRIAR | `GET /api/categorias`, `POST /api/categorias`, `PUT /api/categorias/:id`, `DELETE /api/categorias/:id` |
| `backend/api/adicionais.php` | CRIAR | `GET /api/adicionais`, `POST /api/adicionais`, `PUT /api/adicionais/:id`, `DELETE /api/adicionais/:id` |
| `backend/api/auth/login.php` | CRIAR | `POST /api/auth/login` (form POST) |
| `backend/api/auth/logout.php` | CRIAR | `POST /api/auth/logout` |
| `backend/api/auth/me.php` | CRIAR | `GET /api/auth/me` |
| `backend/db/migrations/001_schema.sql` | CRIAR | Schema completo do banco |
| `backend/db/migrations/002_seed.sql` | CRIAR | Dados iniciais: usuário admin, restrições padrão |
| `backend/scripts/migrate.php` | CRIAR | Roda migrations na ordem |
| `backend/scripts/backup.php` | CRIAR | Exporta dump MySQL diário |
| `backend/.htaccess` | CRIAR | Rewrite: `/api/*` → `api/index.php` |

---

## Fase 1 — Infraestrutura (config, helpers, router, .htaccess)

### [arquivo: backend/.htaccess]
**Ação:** CRIAR

**Mudanças:**
- [x] Ativar `RewriteEngine On`
- [x] Redirecionar toda requisição a `/api/*` que não seja arquivo real para `backend/api/index.php`
- [x] Preservar query string com `[QSA]`
- [x] Header CORS para desenvolvimento local

**Trecho de referência:**
```apache
RewriteEngine On
RewriteBase /

# Evita rewrite de arquivos e diretórios reais
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# Roteia tudo que começa com /api para o index.php
RewriteRule ^api/(.*)$ backend/api/index.php [QSA,L]
```

---

### [arquivo: backend/config/constants.php]
**Ação:** CRIAR

**Mudanças:**
- [x] Constante `DB_HOST` (default: `localhost`)
- [x] Constante `DB_PORT` (default: `3306`)
- [x] Constante `DB_NAME` (default: `prontus`)
- [x] Constante `DB_USER` e `DB_PASS` — lidos de `$_ENV` com fallback para valores de dev
- [x] Array `STATUS_VALIDOS`: `['recebido', 'em-preparo', 'pronto', 'finalizado']`
- [x] Array `STATUS_SEQUENCIA`: mapeamento de qual status vem depois (`recebido → em-preparo → pronto → finalizado`)
- [x] Array `PERFIS_VALIDOS`: `['admin', 'cozinha']`
- [x] Constante `RESTRICOES_PADRAO`: array com slugs e nomes padrão

**Trecho de referência:**
```php
<?php
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_PORT', $_ENV['DB_PORT'] ?? '3306');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'prontus');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

const STATUS_VALIDOS = ['recebido', 'em-preparo', 'pronto', 'finalizado'];
const STATUS_SEQUENCIA = [
    'recebido'   => 'em-preparo',
    'em-preparo' => 'pronto',
    'pronto'     => 'finalizado',
];
const PERFIS_VALIDOS = ['admin', 'cozinha'];
const RESTRICOES_PADRAO = [
    ['slug' => 'sem-gluten',   'nome' => 'Sem Glúten',    'cor' => '#E74C3C'],
    ['slug' => 'vegetariano',  'nome' => 'Vegetariano',   'cor' => '#27AE60'],
    ['slug' => 'vegano',       'nome' => 'Vegano',        'cor' => '#2ECC71'],
    ['slug' => 'sem-lactose',  'nome' => 'Sem Lactose',   'cor' => '#3498DB'],
    ['slug' => 'sem-amendoim', 'nome' => 'Sem Amendoim',  'cor' => '#F39C12'],
];
```

---

### [arquivo: backend/config/database.php]
**Ação:** CRIAR

**Mudanças:**
- [x] Função `get_pdo(): PDO` que retorna conexão PDO MySQL singleton
- [x] `ATTR_ERRMODE => ERRMODE_EXCEPTION` para exceções em erros SQL
- [x] `ATTR_DEFAULT_FETCH_MODE => FETCH_ASSOC`
- [x] `charset=utf8mb4` no DSN

**Trecho de referência:**
```php
<?php
require_once __DIR__ . '/constants.php';

function get_pdo(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        DB_HOST, DB_PORT, DB_NAME
    );
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}
```

---

### [arquivo: backend/config/helpers.php]
**Ação:** CRIAR

**Mudanças:**
- [x] `json_response(mixed $data, int $status = 200): never` — define Content-Type JSON, http_response_code, ecoa JSON e encerra
- [x] `error_response(string $mensagem, int $status = 400): never` — atalho para `json_response(['erro' => $mensagem], $status)`
- [x] `input_json(): array` — lê e decodifica o body da requisição como JSON; lança erro 400 se inválido
- [x] `require_auth(): array` — verifica sessão PHP ativa; retorna dados do usuário ou chama `error_response(..., 401)`
- [x] `require_admin(): array` — chama `require_auth()` e verifica `perfil === 'admin'`; 403 se não for admin
- [x] `cors_headers(): void` — emite headers `Access-Control-Allow-*` para desenvolvimento local

**Trecho de referência:**
```php
<?php
function json_response(mixed $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function error_response(string $msg, int $status = 400): never {
    json_response(['erro' => $msg], $status);
}

function input_json(): array {
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);
    if (!is_array($data)) error_response('Body JSON inválido', 400);
    return $data;
}

function require_auth(): array {
    session_start();
    if (empty($_SESSION['usuario'])) error_response('Não autenticado', 401);
    return $_SESSION['usuario'];
}

function require_admin(): array {
    $usuario = require_auth();
    if ($usuario['perfil'] !== 'admin') error_response('Acesso negado', 403);
    return $usuario;
}

function cors_headers(): void {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}
```

---

### [arquivo: backend/api/index.php]
**Ação:** CRIAR

**Mudanças:**
- [x] Require `config/constants.php`, `config/helpers.php`
- [x] Chamar `cors_headers()` no topo
- [x] Responder `OPTIONS` com 200 imediatamente (preflight CORS)
- [x] Extrair `$method = $_SERVER['REQUEST_METHOD']` e `$path` de `$_SERVER['REQUEST_URI']` removendo o prefixo `/api`
- [x] Normalizar path: remover query string e trailing slash
- [x] Tabela de rotas via `switch/case` com `preg_match` para rotas dinâmicas
- [x] Expor `$routeParams` como variável global para os handlers usarem (ex: `$routeParams['id']`)
- [x] Retornar 404 JSON se nenhuma rota combinar

**Tabela de rotas a implementar no router:**

| Pattern | Método | Handler |
|---|---|---|
| `/cardapio` | GET | `cardapio.php` |
| `/pedidos` | GET | `pedidos.php` |
| `/pedidos` | POST | `pedidos.php` |
| `/pedidos/{id}/status` | PATCH | `pedidos_status.php` |
| `/senhas` | GET | `senhas.php` |
| `/metricas` | GET | `metricas.php` |
| `/produtos` | GET | `produtos.php` |
| `/produtos` | POST | `produtos.php` |
| `/produtos/{id}` | GET | `produto.php` |
| `/produtos/{id}` | PUT | `produto.php` |
| `/produtos/{id}` | DELETE | `produto.php` |
| `/categorias` | GET | `categorias.php` |
| `/categorias` | POST | `categorias.php` |
| `/categorias/{id}` | PUT | `categorias.php` |
| `/categorias/{id}` | DELETE | `categorias.php` |
| `/adicionais` | GET | `adicionais.php` |
| `/adicionais` | POST | `adicionais.php` |
| `/adicionais/{id}` | PUT | `adicionais.php` |
| `/adicionais/{id}` | DELETE | `adicionais.php` |
| `/auth/login` | POST | `auth/login.php` |
| `/auth/logout` | POST | `auth/logout.php` |
| `/auth/me` | GET | `auth/me.php` |

**Trecho de referência:**
```php
<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/helpers.php';

cors_headers();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path   = preg_replace('#^/?api#', '', $uri);
$path   = rtrim($path, '/') ?: '/';

$routeParams = [];

$routes = [
    ['GET',    '#^/cardapio$#',               'cardapio.php'],
    ['GET',    '#^/pedidos$#',                'pedidos.php'],
    ['POST',   '#^/pedidos$#',                'pedidos.php'],
    ['PATCH',  '#^/pedidos/(\d+)/status$#',   'pedidos_status.php', ['id']],
    ['GET',    '#^/senhas$#',                 'senhas.php'],
    ['GET',    '#^/metricas$#',               'metricas.php'],
    ['GET',    '#^/produtos$#',               'produtos.php'],
    ['POST',   '#^/produtos$#',               'produtos.php'],
    ['GET',    '#^/produtos/(\d+)$#',         'produto.php', ['id']],
    ['PUT',    '#^/produtos/(\d+)$#',         'produto.php', ['id']],
    ['DELETE', '#^/produtos/(\d+)$#',         'produto.php', ['id']],
    ['GET',    '#^/categorias$#',             'categorias.php'],
    ['POST',   '#^/categorias$#',             'categorias.php'],
    ['PUT',    '#^/categorias/(\d+)$#',       'categorias.php', ['id']],
    ['DELETE', '#^/categorias/(\d+)$#',       'categorias.php', ['id']],
    ['GET',    '#^/adicionais$#',             'adicionais.php'],
    ['POST',   '#^/adicionais$#',             'adicionais.php'],
    ['PUT',    '#^/adicionais/(\d+)$#',       'adicionais.php', ['id']],
    ['DELETE', '#^/adicionais/(\d+)$#',       'adicionais.php', ['id']],
    ['POST',   '#^/auth/login$#',             'auth/login.php'],
    ['POST',   '#^/auth/logout$#',            'auth/logout.php'],
    ['GET',    '#^/auth/me$#',                'auth/me.php'],
];

foreach ($routes as $route) {
    [$rMethod, $rPattern, $rFile] = $route;
    $rParams = $route[3] ?? [];
    if ($method !== $rMethod) continue;
    if (!preg_match($rPattern, $path, $matches)) continue;
    foreach ($rParams as $i => $name) {
        $routeParams[$name] = $matches[$i + 1];
    }
    require __DIR__ . '/' . $rFile;
    exit;
}

error_response('Rota não encontrada', 404);
```

---

## Fase 2 — Schema do banco de dados

### [arquivo: backend/db/migrations/001_schema.sql]
**Ação:** CRIAR

**Tabelas e campos:**

- [x] **`categorias`**: `id` PK AUTO_INCREMENT, `nome` VARCHAR(100) NOT NULL, `icone` VARCHAR(10) DEFAULT '', `ativo` TINYINT DEFAULT 1, `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP
- [x] **`restricoes`**: `id` PK AUTO_INCREMENT, `slug` VARCHAR(50) UNIQUE NOT NULL, `nome` VARCHAR(100) NOT NULL, `cor` VARCHAR(7) DEFAULT '#F39C12', `ativo` TINYINT DEFAULT 1
- [x] **`produtos`**: `id` PK AUTO_INCREMENT, `categoria_id` FK → categorias(id), `nome` VARCHAR(200) NOT NULL, `descricao` TEXT, `preco` DECIMAL(10,2) NOT NULL, `imagem` VARCHAR(500), `ativo` TINYINT DEFAULT 1, `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP. INDEX em `categoria_id`.
- [x] **`produto_restricoes`**: PK composta (`produto_id`, `restricao_slug`). FK `produto_id` → produtos(id) ON DELETE CASCADE.
- [x] **`adicionais`**: `id` PK AUTO_INCREMENT, `produto_id` FK → produtos(id) ON DELETE CASCADE, `nome` VARCHAR(100) NOT NULL, `preco` DECIMAL(10,2) DEFAULT 0
- [x] **`remocoes`**: `id` PK AUTO_INCREMENT, `produto_id` FK → produtos(id) ON DELETE CASCADE, `nome` VARCHAR(100) NOT NULL
- [x] **`pedidos`**: `id` PK AUTO_INCREMENT, `senha` VARCHAR(10) NOT NULL, `status` ENUM('recebido','em-preparo','pronto','finalizado') DEFAULT 'recebido', `pagamento` VARCHAR(20), `total` DECIMAL(10,2) DEFAULT 0, `horario` DATETIME DEFAULT CURRENT_TIMESTAMP, `preparado_em` DATETIME NULL. INDEX em `status`, INDEX em `DATE(horario)`.
- [x] **`pedido_itens`**: `id` PK AUTO_INCREMENT, `pedido_id` FK → pedidos(id) ON DELETE CASCADE, `produto_id` INT NULL (pode ser NULL se produto deletado), `produto_nome` VARCHAR(200) NOT NULL (snapshot), `preco_unitario` DECIMAL(10,2) NOT NULL, `quantidade` INT DEFAULT 1
- [x] **`pedido_item_extras`**: `id` PK AUTO_INCREMENT, `pedido_item_id` FK → pedido_itens(id) ON DELETE CASCADE, `nome` VARCHAR(100) NOT NULL
- [x] **`pedido_item_remocoes`**: `id` PK AUTO_INCREMENT, `pedido_item_id` FK → pedido_itens(id) ON DELETE CASCADE, `nome` VARCHAR(100) NOT NULL
- [x] **`pedido_item_restricoes`**: `id` PK AUTO_INCREMENT, `pedido_item_id` FK → pedido_itens(id) ON DELETE CASCADE, `restricao_slug` VARCHAR(50) NOT NULL
- [x] **`usuarios`**: `id` PK AUTO_INCREMENT, `nome` VARCHAR(100) NOT NULL, `email` VARCHAR(200) UNIQUE NOT NULL, `senha_hash` VARCHAR(255) NOT NULL, `perfil` ENUM('admin','cozinha') DEFAULT 'cozinha', `ativo` TINYINT DEFAULT 1

**Trecho de referência:**
```sql
CREATE TABLE categorias (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  nome      VARCHAR(100) NOT NULL,
  icone     VARCHAR(10)  DEFAULT '',
  ativo     TINYINT      NOT NULL DEFAULT 1,
  criado_em DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE restricoes (
  id    INT AUTO_INCREMENT PRIMARY KEY,
  slug  VARCHAR(50) NOT NULL UNIQUE,
  nome  VARCHAR(100) NOT NULL,
  cor   VARCHAR(7)  DEFAULT '#F39C12',
  ativo TINYINT     NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE produtos (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  categoria_id INT          NOT NULL,
  nome         VARCHAR(200) NOT NULL,
  descricao    TEXT,
  preco        DECIMAL(10,2) NOT NULL,
  imagem       VARCHAR(500)  DEFAULT NULL,
  ativo        TINYINT       NOT NULL DEFAULT 1,
  criado_em    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_categoria (categoria_id),
  FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE produto_restricoes (
  produto_id     INT         NOT NULL,
  restricao_slug VARCHAR(50) NOT NULL,
  PRIMARY KEY (produto_id, restricao_slug),
  FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE adicionais (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  produto_id INT           NOT NULL,
  nome       VARCHAR(100)  NOT NULL,
  preco      DECIMAL(10,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE remocoes (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  produto_id INT          NOT NULL,
  nome       VARCHAR(100) NOT NULL,
  FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pedidos (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  senha        VARCHAR(10)   NOT NULL,
  status       ENUM('recebido','em-preparo','pronto','finalizado') NOT NULL DEFAULT 'recebido',
  pagamento    VARCHAR(20)   DEFAULT NULL,
  total        DECIMAL(10,2) NOT NULL DEFAULT 0,
  horario      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  preparado_em DATETIME      DEFAULT NULL,
  INDEX idx_status  (status),
  INDEX idx_horario (horario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pedido_itens (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  pedido_id      INT           NOT NULL,
  produto_id     INT           DEFAULT NULL,
  produto_nome   VARCHAR(200)  NOT NULL,
  preco_unitario DECIMAL(10,2) NOT NULL,
  quantidade     INT           NOT NULL DEFAULT 1,
  FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pedido_item_extras (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  pedido_item_id INT          NOT NULL,
  nome           VARCHAR(100) NOT NULL,
  FOREIGN KEY (pedido_item_id) REFERENCES pedido_itens(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pedido_item_remocoes (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  pedido_item_id INT          NOT NULL,
  nome           VARCHAR(100) NOT NULL,
  FOREIGN KEY (pedido_item_id) REFERENCES pedido_itens(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pedido_item_restricoes (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  pedido_item_id INT         NOT NULL,
  restricao_slug VARCHAR(50) NOT NULL,
  FOREIGN KEY (pedido_item_id) REFERENCES pedido_itens(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE usuarios (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  nome        VARCHAR(100) NOT NULL,
  email       VARCHAR(200) NOT NULL UNIQUE,
  senha_hash  VARCHAR(255) NOT NULL,
  perfil      ENUM('admin','cozinha') NOT NULL DEFAULT 'cozinha',
  ativo       TINYINT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

### [arquivo: backend/db/migrations/002_seed.sql]
**Ação:** CRIAR

**Mudanças:**
- [x] Inserir usuário admin padrão: email `admin@prontus.app`, senha `prontus123` (hash `password_hash('prontus123', PASSWORD_DEFAULT)` — inserir o hash literal, não a função PHP)
- [x] Inserir as 5 restrições padrão definidas em `RESTRICOES_PADRAO`

**Trecho de referência:**
```sql
INSERT INTO usuarios (nome, email, senha_hash, perfil) VALUES
('Administrador', 'admin@prontus.app',
 '$2y$12$HASH_GERADO_PELO_PHP_AQUI', 'admin');

INSERT INTO restricoes (slug, nome, cor) VALUES
('sem-gluten',   'Sem Glúten',   '#E74C3C'),
('vegetariano',  'Vegetariano',  '#27AE60'),
('vegano',       'Vegano',       '#2ECC71'),
('sem-lactose',  'Sem Lactose',  '#3498DB'),
('sem-amendoim', 'Sem Amendoim', '#F39C12');
```

**Importante:** O script `migrate.php` deve gerar o hash PHP e fazer o INSERT programaticamente para o usuário admin, não usar hash hardcoded.

---

### [arquivo: backend/scripts/migrate.php]
**Ação:** CRIAR

**Mudanças:**
- [x] Conectar ao banco via `get_pdo()`
- [x] Ler e executar `001_schema.sql` com `$pdo->exec()`
- [x] Criar usuário admin via `password_hash()` e INSERT
- [x] Executar `002_seed.sql` (restrições)
- [x] Exibir mensagem de sucesso/erro no terminal
- [x] Uso: `php backend/scripts/migrate.php`

---

## Fase 3 — Autenticação

### [arquivo: backend/api/auth/login.php]
**Ação:** CRIAR

**Mudanças:**
- [x] Aceitar apenas método `POST`; retornar 405 se outro
- [x] Ler campos `email`, `senha`, `redirect` de `$_POST`
- [x] Buscar usuário no banco por email com `WHERE ativo = 1`
- [x] Verificar senha com `password_verify($senha, $usuario['senha_hash'])`
- [x] Em falha: redirecionar para `$_POST['redirect'] ?? '/admin/pages/login.html'` com `?erro=1`
- [x] Em sucesso: `session_start()`, armazenar em `$_SESSION['usuario']` os campos `id`, `nome`, `email`, `perfil`; redirecionar para `$_POST['redirect']`
- [x] Nunca retornar JSON (é form POST)

**Trecho de referência:**
```php
<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$email    = trim($_POST['email'] ?? '');
$senha    = $_POST['senha'] ?? '';
$redirect = $_POST['redirect'] ?? '/admin/pages/login.php';
$fallback = strtok($redirect, '?') . '?erro=1';

$stmt = get_pdo()->prepare(
    'SELECT id, nome, email, senha_hash, perfil FROM usuarios WHERE email = ? AND ativo = 1'
);
$stmt->execute([$email]);
$usuario = $stmt->fetch();

if (!$usuario || !password_verify($senha, $usuario['senha_hash'])) {
    header('Location: ' . $fallback);
    exit;
}

$_SESSION['usuario'] = [
    'id'     => $usuario['id'],
    'nome'   => $usuario['nome'],
    'email'  => $usuario['email'],
    'perfil' => $usuario['perfil'],
];

header('Location: ' . $redirect);
exit;
```

---

### [arquivo: backend/api/auth/logout.php]
**Ação:** CRIAR

**Mudanças:**
- [x] `session_start()`, `session_destroy()`
- [x] Retornar `json_response(['ok' => true])`

---

### [arquivo: backend/api/auth/me.php]
**Ação:** CRIAR

**Mudanças:**
- [x] Chamar `require_auth()` (retorna 401 automaticamente se não autenticado)
- [x] Retornar `json_response(['logado' => true, 'usuario' => $usuario])`

---

## Fase 4 — Endpoints de leitura

### [arquivo: backend/api/cardapio.php]
**Ação:** CRIAR

**Mudanças:**
- [x] Apenas `GET`; retornar 405 se outro método
- [x] Buscar categorias ativas: `SELECT id, nome, icone FROM categorias WHERE ativo = 1 ORDER BY id`
- [x] Buscar produtos ativos com seus dados base
- [x] Para cada produto: fazer 3 queries adicionais (restricoes, extras/adicionais, remocoes) e montar o objeto completo
- [x] Remapear `categoria_id` → `categoriaId` (camelCase que o frontend espera)
- [x] Converter `preco` para `float`
- [x] Retornar `json_response(['categorias' => $cats, 'produtos' => $prods])`

**Estrutura do produto na resposta:**
```json
{
  "id": 1,
  "categoriaId": 1,
  "nome": "X-Burguer Clássico",
  "descricao": "...",
  "preco": 24.90,
  "imagem": null,
  "restricoes": ["sem-gluten"],
  "extras": [{ "id": 1, "nome": "Bacon extra", "preco": 4.00 }],
  "remocoes": ["Alface", "Tomate"]
}
```

**Queries a usar:**
```sql
-- Categorias
SELECT id, nome, icone FROM categorias WHERE ativo = 1 ORDER BY id;

-- Produtos (loop por categoria ou todos de uma vez)
SELECT id, categoria_id, nome, descricao, preco, imagem
FROM produtos WHERE ativo = 1 ORDER BY categoria_id, id;

-- Para cada produto (parameterizado por produto_id):
SELECT restricao_slug FROM produto_restricoes WHERE produto_id = ?;
SELECT id, nome, preco FROM adicionais WHERE produto_id = ? ORDER BY id;
SELECT nome FROM remocoes WHERE produto_id = ? ORDER BY id;
```

---

### [arquivo: backend/api/produtos.php]
**Ação:** CRIAR

**Mudanças:**
- [x] `GET`: mesma lógica de `cardapio.php` mas retorna só o array de produtos
- [x] `POST`: chamar `require_admin()`, ler JSON body, validar campos obrigatórios (`nome`, `preco`, `categoriaId`), inserir em `produtos`, depois inserir `produto_restricoes`, `adicionais`, `remocoes`, retornar produto criado completo com `json_response($produto, 201)`

**Validações no POST:**
- `nome`: string não vazia, máx 200 chars
- `preco`: float > 0
- `categoriaId`: int, deve existir em `categorias`
- `restricoes`: array de strings (slugs)
- `extras`: array de `{ nome, preco }`
- `remocoes`: array de strings

**Trecho de referência (POST):**
```php
$pdo = get_pdo();
$data = input_json();

$stmt = $pdo->prepare(
    'INSERT INTO produtos (categoria_id, nome, descricao, preco, imagem)
     VALUES (?, ?, ?, ?, ?)'
);
$stmt->execute([
    $data['categoriaId'],
    $data['nome'],
    $data['descricao'] ?? '',
    $data['preco'],
    $data['imagem'] ?? null,
]);
$produtoId = (int)$pdo->lastInsertId();

// Inserir restricoes
foreach ($data['restricoes'] ?? [] as $slug) {
    $pdo->prepare('INSERT INTO produto_restricoes VALUES (?, ?)')->execute([$produtoId, $slug]);
}
// Inserir extras
foreach ($data['extras'] ?? [] as $extra) {
    $pdo->prepare('INSERT INTO adicionais (produto_id, nome, preco) VALUES (?, ?, ?)')
        ->execute([$produtoId, $extra['nome'], $extra['preco']]);
}
// Inserir remocoes
foreach ($data['remocoes'] ?? [] as $nome) {
    $pdo->prepare('INSERT INTO remocoes (produto_id, nome) VALUES (?, ?)')->execute([$produtoId, $nome]);
}

json_response(buscar_produto_completo($pdo, $produtoId), 201);
```

**Extrair função auxiliar `buscar_produto_completo(PDO $pdo, int $id): array`** que monta o objeto produto com restricoes, extras e remocoes — reutilizada em `produto.php` e `produtos.php`.

---

### [arquivo: backend/api/produto.php]
**Ação:** CRIAR

**Mudanças:**
- [x] `GET /api/produtos/:id`: buscar produto com `buscar_produto_completo()`; 404 se não encontrado
- [x] `PUT /api/produtos/:id`: `require_admin()`, ler JSON, UPDATE no produto + DELETE + re-INSERT das tabelas filhas (restricoes, adicionais, remocoes), retornar produto atualizado
- [x] `DELETE /api/produtos/:id`: `require_admin()`, `UPDATE produtos SET ativo = 0 WHERE id = ?` (soft delete), retornar `{ ok: true }`
- [x] Usar `$routeParams['id']` para obter o ID da rota

---

### [arquivo: backend/api/categorias.php]
**Ação:** CRIAR

**Mudanças:**
- [x] `GET`: `SELECT id, nome, icone FROM categorias WHERE ativo = 1 ORDER BY id`; retornar array
- [x] `POST`: `require_admin()`, inserir categoria, retornar criada com status 201
- [x] `PUT /:id`: `require_admin()`, UPDATE, retornar atualizada
- [x] `DELETE /:id`: `require_admin()`, verificar se não há produtos ativos na categoria (retornar 409 se houver), então soft delete `ativo = 0`

---

### [arquivo: backend/api/adicionais.php]
**Ação:** CRIAR

**Mudanças:**
- [x] `GET`: buscar todos adicionais com JOIN em produtos para retornar `produtoId` e `produtoNome`:
  ```sql
  SELECT a.id, a.nome, a.preco, a.produto_id as produtoId, p.nome as produtoNome
  FROM adicionais a JOIN produtos p ON a.produto_id = p.id
  WHERE p.ativo = 1 ORDER BY a.produto_id, a.id
  ```
- [x] `POST`: `require_admin()`, inserir em `adicionais`, retornar criado
- [x] `PUT /:id`: `require_admin()`, UPDATE, retornar atualizado
- [x] `DELETE /:id`: `require_admin()`, DELETE físico (adicionais não precisam de soft delete), retornar `{ ok: true }`

---

### [arquivo: backend/api/pedidos.php]
**Ação:** CRIAR

**Mudanças:**

**GET:**
- [x] Buscar pedidos do dia atual: `WHERE DATE(horario) = CURDATE()` (pode mostrar todos se KDS precisar de contexto — usar `DATE(horario) = CURDATE()` para manter volume razoável)
- [x] Para cada pedido, buscar itens com extras, remocoes e restricoes em queries separadas
- [x] Montar o objeto no formato exato do mock:
  ```json
  {
    "id": 1, "senha": "#042", "status": "recebido",
    "horario": "2026-05-10T12:01:00",
    "itens": [{ "produto": "Nome", "quantidade": 1, "extras": [], "remocoes": [], "restricoes": [] }]
  }
  ```
- [x] Ordenar por `horario ASC`

**POST:**
- [x] Ler JSON body com `itens` (array) e `pagamento` (string)
- [x] Gerar senha sequencial do dia:
  ```sql
  SELECT COUNT(*) FROM pedidos WHERE DATE(horario) = CURDATE()
  ```
  Senha = `'#' . str_pad($count + 1, 3, '0', STR_PAD_LEFT)`
- [x] Calcular `total`: somar `(preco_unitario + soma_extras_escolhidos) * quantidade` por item
- [x] Inserir em `pedidos` com status `recebido`
- [x] Para cada item: inserir em `pedido_itens`; para cada item, inserir em `pedido_item_extras`, `pedido_item_remocoes`, `pedido_item_restricoes`
- [x] Buscar produto do banco para obter `preco_unitario` e nome (snapshot de segurança)
- [x] Retornar `json_response($pedidoCriado, 201)` no mesmo formato do GET

**Payload esperado do frontend:**
```json
{
  "pagamento": "pix",
  "itens": [
    {
      "produtoId": 1,
      "quantidade": 2,
      "extras": ["Bacon extra"],
      "remocoes": ["Cebola"],
      "restricoes": ["sem-gluten"]
    }
  ]
}
```

---

### [arquivo: backend/api/pedidos_status.php]
**Ação:** CRIAR

**Mudanças:**
- [x] Aceitar apenas `PATCH`
- [x] `require_auth()` (cozinha e admin podem atualizar)
- [x] Ler `$routeParams['id']` e JSON body `{ status }`
- [x] Validar que `status` está em `STATUS_VALIDOS`
- [x] UPDATE `pedidos SET status = ?` WHERE `id = ?`
- [x] Se o novo status for `'pronto'`: também setar `preparado_em = NOW()`
- [x] Retornar `json_response(['id' => $id, 'status' => $status])`

---

### [arquivo: backend/api/senhas.php]
**Ação:** CRIAR

**Mudanças:**
- [x] `GET` somente
- [x] Buscar senhas em preparo:
  ```sql
  SELECT senha FROM pedidos
  WHERE status IN ('recebido', 'em-preparo') AND DATE(horario) = CURDATE()
  ORDER BY horario ASC
  ```
- [x] Buscar senhas prontas:
  ```sql
  SELECT senha FROM pedidos
  WHERE status = 'pronto' AND DATE(horario) = CURDATE()
  ORDER BY horario ASC
  ```
- [x] Retornar `json_response(['em_preparo' => $emPreparo, 'prontas' => $prontas])`

---

### [arquivo: backend/api/metricas.php]
**Ação:** CRIAR

**Mudanças:**
- [x] `require_admin()`
- [x] Query `total_pedidos`: `SELECT COUNT(*) FROM pedidos WHERE DATE(horario) = CURDATE()`
- [x] Query `volume_vendas`: `SELECT COALESCE(SUM(total), 0) FROM pedidos WHERE DATE(horario) = CURDATE()`
- [x] Query `tempo_medio_minutos`: média de `TIMESTAMPDIFF(MINUTE, horario, preparado_em)` para pedidos onde `preparado_em IS NOT NULL AND DATE(horario) = CURDATE()`
- [x] Query `pedidos_por_hora`: GROUP BY hora (0-23), retornar array de 24 posições (zero onde não há pedidos)
  ```sql
  SELECT HOUR(horario) as hora, COUNT(*) as total
  FROM pedidos WHERE DATE(horario) = CURDATE()
  GROUP BY HOUR(horario) ORDER BY hora
  ```
  Montar array `[0..23]` preenchendo com 0 onde hora não aparecer
- [x] Retornar `json_response(['total_pedidos' => ..., 'tempo_medio_minutos' => ..., 'volume_vendas' => ..., 'pedidos_por_hora' => ...])`

---

## Fase 5 — Scripts de suporte

### [arquivo: backend/scripts/backup.php]
**Ação:** CRIAR

**Mudanças:**
- [x] Usar `mysqldump` via `exec()` ou `shell_exec()` para exportar o banco
- [x] Salvar em `backend/db/backups/prontus_YYYY-MM-DD.sql`
- [x] Manter apenas os 7 backups mais recentes (deletar os mais antigos)
- [x] Logar resultado no stdout (para cron)
- [x] Uso: `php backend/scripts/backup.php` ou via cron `0 3 * * * php /var/www/prontus/backend/scripts/backup.php`

**Trecho de referência:**
```php
<?php
require_once __DIR__ . '/../config/constants.php';

$backupDir = __DIR__ . '/../db/backups';
if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

$filename  = $backupDir . '/prontus_' . date('Y-m-d') . '.sql';
$command   = sprintf(
    'mysqldump -h%s -P%s -u%s -p%s %s > %s 2>&1',
    escapeshellarg(DB_HOST),
    escapeshellarg(DB_PORT),
    escapeshellarg(DB_USER),
    escapeshellarg(DB_PASS),
    escapeshellarg(DB_NAME),
    escapeshellarg($filename)
);

exec($command, $output, $code);

if ($code !== 0) {
    echo "ERRO no backup: " . implode("\n", $output) . "\n";
    exit(1);
}

// Manter apenas últimos 7
$backups = glob($backupDir . '/prontus_*.sql');
sort($backups);
while (count($backups) > 7) {
    unlink(array_shift($backups));
}

echo "Backup salvo em $filename\n";
```

---

## Critérios de sucesso

### Verificação automática
- [x] `php -l backend/api/index.php` sem erros de sintaxe em todos os arquivos PHP
- [x] `php backend/scripts/migrate.php` executa sem erros e cria todas as tabelas
- [x] Todas as rotas retornam `Content-Type: application/json`
- [x] `GET /api/cardapio` retorna objeto com `categorias` e `produtos`
- [x] `GET /api/senhas` retorna objeto com `em_preparo` e `prontas`
- [x] `GET /api/metricas` retorna 401 sem sessão ativa

### Verificação manual

**Auth:**
- [x] POST para `/api/auth/login` com credenciais corretas redireciona para `redirect`
- [x] POST com credenciais erradas redireciona com `?erro=1`
- [x] GET `/api/auth/me` retorna 401 sem sessão
- [x] GET `/api/auth/me` retorna dados do usuário após login

**Cardápio / Totem:**
- [x] `GET /api/cardapio` retorna categorias e produtos com `restricoes`, `extras` e `remocoes` corretamente aninhados
- [x] `POST /api/pedidos` com payload válido retorna pedido com senha no formato `#001`
- [x] Segunda chamada a `POST /api/pedidos` retorna senha `#002` (sequencial)
- [x] `PATCH /api/pedidos/1/status` com `{ status: 'em-preparo' }` atualiza banco e retorna `{ id, status }`
- [x] Atualizar status para `pronto` registra `preparado_em` no banco

**KDS:**
- [x] `GET /api/pedidos` retorna apenas pedidos do dia atual
- [x] Cada pedido tem `itens` com `extras`, `remocoes` e `restricoes`

**Display:**
- [x] `GET /api/senhas` reflete corretamente os status do banco

**Admin:**
- [x] `POST /api/produtos` sem sessão retorna 401
- [x] `POST /api/produtos` com sessão de cozinha retorna 403
- [x] `POST /api/produtos` com sessão de admin cria produto e retorna 201
- [x] `PUT /api/produtos/1` atualiza produto e suas tabelas filhas (restricoes, extras, remocoes são reescritas)
- [x] `DELETE /api/produtos/1` faz soft delete (`ativo = 0`), produto não aparece mais em `GET /api/produtos`
- [x] `DELETE /api/categorias/1` retorna 409 se houver produtos ativos na categoria
- [x] `GET /api/metricas` retorna `pedidos_por_hora` com 24 posições (zeros onde não há pedidos)

---

## Riscos e dependências

| Risco | Impacto | Mitigação |
|---|---|---|
| CORS bloqueando requisições do frontend | Totem/KDS/Display quebram | `cors_headers()` no topo do `index.php`; em produção restringir a origem real |
| Sessão PHP e polling a cada 2s: overhead de `session_start()` | Lentidão em alta carga | Rotas de polling (`/senhas`, `/pedidos`) não exigem auth — sem `session_start()` |
| Soft delete de produto com pedidos ativos | Pedidos existentes perdem referência | `produto_nome` é snapshot na `pedido_itens`; `produto_id` pode ser NULL — frontend mostra nome do snapshot |
| Senha duplicada em condição de corrida (dois pedidos simultâneos) | Senhas repetidas no dia | Usar transação MySQL + SELECT FOR UPDATE ao gerar senha; SQLite não tem esse problema pois é serial |
| `mysqldump` indisponível no servidor | Backup falha silenciosamente | Verificar existência do binário no `backup.php`; log de erro explícito |
| `mod_rewrite` desabilitado no Apache | Nenhuma rota funciona | Confirmar `AllowOverride All` no VirtualHost; documentar no README |
| Fuso horário do servidor diferente do restaurante | Métricas e senhas do dia erradas | Chamar `date_default_timezone_set('America/Sao_Paulo')` no topo do `index.php` e `constants.php` |
