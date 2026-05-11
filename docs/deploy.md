# Deploy & Infraestrutura — Prontus v1

**Data:** 2026-05-11

---

## Servidor

| Campo | Valor |
|---|---|
| Provedor | DigitalOcean |
| IP | `159.223.165.79` |
| OS | Ubuntu 25.10 |
| Acesso | `ssh root@159.223.165.79` |

### Stack instalada

- **Nginx 1.28** — porta 80 (outros projetos) e porta 8080 (Prontus)
- **PHP 8.4-FPM** — via FastCGI
- **MySQL** — banco `prontus`, usuário `prontus`

### Outros projetos no mesmo servidor

| Porta | Processo | Projeto |
|---|---|---|
| 3847 | Docker | Frontend de projeto anterior |
| 5000 | Gunicorn (Python) | API de projeto anterior |
| **8080** | PHP-FPM + Nginx | **Prontus** |

---

## Prontus no servidor

### Arquivos

- **Raiz do projeto:** `/var/www/prontus`
- **Config Nginx:** `/etc/nginx/sites-available/prontus.conf`
- **Variáveis de ambiente:** `/var/www/prontus/backend/config/env.php` *(não versionado — não commitar)*

### Banco de dados

| Campo | Valor |
|---|---|
| Host | `localhost` |
| Banco | `prontus` |
| Usuário | `prontus` |
| Senha | `prontus2026` |

Credenciais salvas em `/var/www/prontus/backend/config/env.php` no servidor.

### Usuário admin padrão

| Campo | Valor |
|---|---|
| Email | `admin@prontus.app` |
| Senha | `prontus123` |

> Trocar a senha após o primeiro acesso em produção real.

### Rodar migration (primeira vez ou novo servidor)

```bash
DB_HOST=localhost DB_PORT=3306 DB_NAME=prontus DB_USER=prontus DB_PASS=prontus2026 \
  php8.4 /var/www/prontus/backend/scripts/migrate.php
```

---

## Acessar o sistema

| Módulo | URL |
|---|---|
| Totem (cardápio) | `http://159.223.165.79:8080/src/totem/pages/index.php` |
| KDS (cozinha) | `http://159.223.165.79:8080/src/kds/pages/index.php` |
| Display (senhas) | `http://159.223.165.79:8080/src/display/pages/index.php` |
| Admin | `http://159.223.165.79:8080/src/admin/pages/login.php` |

---

## CI/CD — GitHub Actions

### Fluxo

```
push em main
     ↓
GitHub Actions (.github/workflows/deploy.yml)
     ↓
webfactory/ssh-agent carrega SERVER_SSH_KEY
     ↓
SSH no servidor → git fetch + git reset --hard origin/main
     ↓
chown/chmod → deploy concluído (~20s)
```

### Secrets configurados no repositório

| Secret | Descrição |
|---|---|
| `SERVER_HOST` | `159.223.165.79` |
| `SERVER_USER` | `root` |
| `SERVER_SSH_KEY` | Chave privada ED25519 para SSH no servidor |

> Os secrets foram definidos via `gh secret set` para evitar problemas de formatação no copy-paste.

### Deploy key no GitHub

A chave pública do servidor (`~/.ssh/github_deploy.pub`) foi adicionada como **Deploy Key** no repositório para que o servidor consiga fazer `git fetch` do repositório privado.

### Como fazer deploy manualmente

```bash
# No servidor
cd /var/www/prontus
git fetch origin
git reset --hard origin/main
```

---

## Problemas encontrados e soluções

### 1. PHP 8.2 não disponível (Ubuntu 25.10)
**Problema:** `apt` não encontrou `php8.2-fpm`.  
**Solução:** Ubuntu 25.10 já traz PHP 8.4 nativamente. Instalado `php8.4-fpm`.

### 2. Variáveis `$_ENV` não chegavam via `fastcgi_param`
**Problema:** `fastcgi_param DB_USER prontus` no Nginx não populava `$_ENV` no PHP.  
**Solução:** Criar `/var/www/prontus/backend/config/env.php` com `putenv()` e `$_ENV[...]` explícitos. O `constants.php` inclui esse arquivo se existir.

### 3. Chave SSH corrompida no copy-paste para GitHub Secrets
**Problema:** Copiar a chave privada manualmente no campo do GitHub Secret corrompeu o formato (CRLF, quebras de linha).  
**Solução:** Usar `gh secret set SERVER_SSH_KEY --repo Leko97/prontus < /tmp/prontus_deploy_key` via CLI.

### 4. `git pull` falhava no servidor (repositório privado)
**Problema:** O servidor não tinha credenciais para acessar o repositório privado via HTTPS.  
**Solução:**
1. Gerar chave SSH no servidor: `ssh-keygen -t ed25519 -f ~/.ssh/github_deploy`
2. Adicionar como Deploy Key no GitHub via `gh repo deploy-key add`
3. Configurar `~/.ssh/config` no servidor com `IdentityFile ~/.ssh/github_deploy`
4. Alterar o remote para SSH: `git remote set-url origin git@github.com:Leko97/prontus.git`
5. Usar `git reset --hard origin/main` no workflow (em vez de `git pull`) para evitar conflitos com arquivos untracked

### 5. Login abre e fecha a página (bug em aberto)
**Problema:** Após submeter o formulário de login, o browser redireciona de volta para a tela de login.  
**Causa identificada:** Os formulários de login (`admin/login.php` e `kds/login.php`) tinham um `addEventListener('submit', e => e.preventDefault())` herdado do modo mock que impedia a submissão real.  
**Fix aplicado:** Removido o `preventDefault()` — o formulário agora submete normalmente para `/api/auth/login`.  
**Status:** Fix deployado em 2026-05-11. Verificar se sessão PHP persiste entre a requisição `/api/auth/login` (FastCGI via bloco `/api`) e a página PHP seguinte (bloco `\.php$`).
