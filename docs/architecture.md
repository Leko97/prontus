# Arquitetura — Prontus Backend v1

**Data:** 2026-05-11  
**Stack:** PHP 8.4 puro + MySQL + Nginx + PHP-FPM  
**Objetivo:** Substituir `USE_MOCK = true` por `USE_MOCK = false` em `src/shared/js/api.js` sem nenhuma outra alteração no frontend.

---

## Estrutura de pastas

```
prontus/
├── backend/
│   ├── .htaccess                  # Rewrite Apache (não usado — servidor usa Nginx)
│   ├── api/
│   │   ├── index.php              # Router principal — único entry point da API
│   │   ├── cardapio.php           # GET /api/cardapio
│   │   ├── pedidos.php            # GET + POST /api/pedidos
│   │   ├── pedidos_status.php     # PATCH /api/pedidos/:id/status
│   │   ├── senhas.php             # GET /api/senhas
│   │   ├── metricas.php           # GET /api/metricas
│   │   ├── produtos.php           # GET + POST /api/produtos
│   │   ├── produto.php            # GET + PUT + DELETE /api/produtos/:id
│   │   ├── categorias.php         # GET + POST + PUT + DELETE /api/categorias/:id
│   │   ├── adicionais.php         # GET + POST + PUT + DELETE /api/adicionais/:id
│   │   └── auth/
│   │       ├── login.php          # POST /api/auth/login (form POST com redirect)
│   │       ├── logout.php         # POST /api/auth/logout
│   │       └── me.php             # GET /api/auth/me
│   ├── config/
│   │   ├── constants.php          # Constantes globais (DB, status, perfis)
│   │   ├── database.php           # PDO singleton
│   │   ├── helpers.php            # json_response, require_auth, cors_headers, etc.
│   │   └── env.php                # ⚠️ NÃO VERSIONADO — credenciais do servidor
│   ├── db/
│   │   ├── migrations/
│   │   │   ├── 001_schema.sql     # 12 tabelas do banco
│   │   │   └── 002_seed.sql       # 5 restrições alimentares padrão
│   │   └── backups/               # Dumps diários (gerados por backup.php)
│   └── scripts/
│       ├── migrate.php            # Executa schema + seed + cria admin
│       └── backup.php             # Exporta mysqldump, mantém 7 últimos
└── src/
    ├── admin/         # Painel administrativo
    ├── kds/           # Kitchen Display System (cozinha)
    ├── display/       # Painel de senhas (TV/display)
    ├── totem/         # Totem de pedidos (cliente)
    └── shared/
        ├── js/
        │   └── api.js  # Cliente HTTP — USE_MOCK = false para usar o backend real
        └── mock/       # Dados mock (usados quando USE_MOCK = true)
```

---

## Como o roteamento funciona

O Nginx encaminha qualquer requisição a `/api/*` diretamente para `backend/api/index.php` via FastCGI:

```nginx
location ~ ^/api(/.*)?$ {
    fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    fastcgi_param SCRIPT_FILENAME /var/www/prontus/backend/api/index.php;
    fastcgi_param REQUEST_URI $request_uri;
}
```

O `index.php` extrai o path, bate contra uma tabela de rotas com regex e faz `require` do handler correspondente. Os parâmetros de rota (ex: `:id`) ficam disponíveis via `$routeParams['id']`.

---

## Autenticação

- **Mecanismo:** Sessão PHP (`session_start()` + `$_SESSION['usuario']`)
- **Perfis:** `admin` (acesso total) e `cozinha` (leitura + atualização de status)
- **Login:** Form POST para `/api/auth/login` — em sucesso redireciona para `$_POST['redirect']`; em falha redireciona para a mesma URL com `?erro=1`
- **Proteção:** `require_auth()` retorna 401; `require_admin()` retorna 403
- **Rotas públicas** (sem auth): `GET /api/cardapio`, `GET /api/senhas`, `POST /api/pedidos`

---

## API — Referência completa

### Cardápio

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| GET | `/api/cardapio` | Não | Retorna `{ categorias, produtos }` com extras, restrições e remoções aninhados |

**Resposta produto:**
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

---

### Pedidos

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| GET | `/api/pedidos` | Não | Pedidos do dia atual ordenados por horário |
| POST | `/api/pedidos` | Não | Cria pedido, gera senha sequencial `#001`, `#002`… |
| PATCH | `/api/pedidos/:id/status` | Sim | Atualiza status; se `pronto` registra `preparado_em` |

**Payload POST /api/pedidos:**
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

**Resposta pedido:**
```json
{
  "id": 1,
  "senha": "#001",
  "status": "recebido",
  "horario": "2026-05-11T12:01:00",
  "itens": [{ "produto": "Nome", "quantidade": 1, "extras": [], "remocoes": [], "restricoes": [] }]
}
```

---

### Senhas

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| GET | `/api/senhas` | Não | `{ em_preparo: [...], prontas: [...] }` — senhas do dia |

---

### Métricas

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| GET | `/api/metricas` | Admin | `{ total_pedidos, volume_vendas, tempo_medio_minutos, pedidos_por_hora[24] }` |

---

### Produtos

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| GET | `/api/produtos` | Não | Array de produtos ativos com extras/restrições/remoções |
| POST | `/api/produtos` | Admin | Cria produto. Retorna 201. |
| GET | `/api/produtos/:id` | Não | Produto completo |
| PUT | `/api/produtos/:id` | Admin | Atualiza produto e reescreve tabelas filhas |
| DELETE | `/api/produtos/:id` | Admin | Soft delete (`ativo = 0`) |

---

### Categorias

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| GET | `/api/categorias` | Não | Lista categorias ativas |
| POST | `/api/categorias` | Admin | Cria categoria |
| PUT | `/api/categorias/:id` | Admin | Atualiza categoria |
| DELETE | `/api/categorias/:id` | Admin | Soft delete — retorna 409 se houver produtos ativos |

---

### Adicionais

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| GET | `/api/adicionais` | Não | Lista com `produtoId` e `produtoNome` |
| POST | `/api/adicionais` | Admin | Cria adicional |
| PUT | `/api/adicionais/:id` | Admin | Atualiza adicional |
| DELETE | `/api/adicionais/:id` | Admin | Delete físico |

---

### Auth

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| POST | `/api/auth/login` | Não | Form POST — redireciona em sucesso/falha |
| POST | `/api/auth/logout` | Não | Destrói sessão |
| GET | `/api/auth/me` | Sim | Retorna `{ logado: true, usuario: {...} }` |

---

## Schema do banco

12 tabelas MySQL (InnoDB, utf8mb4):

| Tabela | Propósito |
|---|---|
| `categorias` | Categorias de produtos (soft delete) |
| `restricoes` | Restrições alimentares (sem-glúten, vegano, etc.) |
| `produtos` | Produtos do cardápio (soft delete) |
| `produto_restricoes` | M:N produtos ↔ restrições |
| `adicionais` | Extras disponíveis por produto |
| `remocoes` | Ingredientes removíveis por produto |
| `pedidos` | Pedidos com senha, status, total, horário |
| `pedido_itens` | Itens do pedido (snapshot do nome/preço) |
| `pedido_item_extras` | Extras escolhidos por item |
| `pedido_item_remocoes` | Remoções escolhidas por item |
| `pedido_item_restricoes` | Restrições do item (do cliente) |
| `usuarios` | Usuários admin/cozinha |

> **Importante:** `pedido_itens.produto_nome` e `preco_unitario` são snapshots — o produto pode ser deletado sem perder o histórico de pedidos.

---

## Pendências conhecidas

### Bug: login redireciona de volta para a tela de login

**Sintoma:** Ao submeter o formulário de login, o browser vai para a página correta e volta imediatamente para o login.

**Hipóteses:**
1. A sessão PHP não persiste entre o bloco `/api` (FastCGI) e o bloco `\.php$` — verificar se ambos usam o mesmo `session.save_path`
2. O `dashboard.php` faz um `checkAuth()` via `api.js` com `fetchAPI('/auth/me')` — se a sessão não for enviada com o cookie correto (domínio/path), retorna 401 e redireciona

**Como investigar:**
```bash
# Ver logs do Nginx no servidor
tail -f /var/log/nginx/prontus_error.log

# Ver se a sessão está sendo criada
ssh root@159.223.165.79 "ls /var/lib/php/sessions/"
```

**Fix aplicado em 2026-05-11:** Removido `e.preventDefault()` dos formulários de login (era código do modo mock). Ainda não confirmado se resolveu completamente.
