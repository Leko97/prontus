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

Cada módulo tem um subdomínio dedicado (HTTPS) que faz proxy para o app na porta 8080.
A raiz de cada subdomínio redireciona para a página de entrada do módulo.

| Módulo | URL (produção) | Acesso direto (porta 8080) |
|---|---|---|
| Totem (cardápio) | `https://totem.leko97.com.br` | `http://159.223.165.79:8080/src/totem/pages/index.php` |
| KDS (cozinha) | `https://kds.leko97.com.br` | `http://159.223.165.79:8080/src/kds/pages/index.php` |
| Display (senhas) | `https://display.leko97.com.br` | `http://159.223.165.79:8080/src/display/pages/index.php` |
| Admin | `https://admin.leko97.com.br` | `http://159.223.165.79:8080/src/admin/pages/login.php` |

---

## Subdomínios (Nginx vhosts)

Cada módulo é um *virtual host* em `/etc/nginx/sites-available/<modulo>` (habilitado por
symlink em `sites-enabled/`) que faz **proxy reverso para `127.0.0.1:8080`** — onde o
PHP-FPM + Nginx do Prontus servem o app. Não há `root`/`fastcgi` nos vhosts de subdomínio;
todo o processamento PHP acontece no bloco da porta 8080.

### Certificado TLS

Um único certificado Let's Encrypt **multi-SAN** cobre todos os subdomínios:

- **Cert name:** `leko97.com.br` (em `/etc/letsencrypt/live/leko97.com.br/`)
- **Domínios (SAN):** `leko97.com.br`, `www.leko97.com.br`, `admin.leko97.com.br`,
  `kds.leko97.com.br`, `totem.leko97.com.br`, `display.leko97.com.br`
- Renovação automática via tarefa agendada do certbot.

> Não é wildcard. Ao adicionar um novo subdomínio, é preciso **expandir** o cert.

### Como adicionar um novo subdomínio (ex.: `display.leko97.com.br`)

Pré-requisito: o DNS do subdomínio já apontando para `159.223.165.79` (registro A).

```bash
# 1. Criar o vhost HTTP-only (necessário para o certbot validar via HTTP-01)
cat > /etc/nginx/sites-available/display <<'NGINX'
server {
    listen 80;
    server_name display.leko97.com.br;
    location = / { return 301 https://display.leko97.com.br/src/display/pages/index.php; }
    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
NGINX
ln -sf /etc/nginx/sites-available/display /etc/nginx/sites-enabled/display
nginx -t && systemctl reload nginx

# 2. Expandir o certificado existente para incluir o novo domínio
#    (certonly NÃO altera os vhosts dos outros módulos — só re-emite o cert)
certbot certonly --nginx --cert-name leko97.com.br \
  -d leko97.com.br -d www.leko97.com.br -d admin.leko97.com.br \
  -d kds.leko97.com.br -d totem.leko97.com.br -d display.leko97.com.br \
  --expand -n --agree-tos

# 3. Reescrever o vhost com o bloco HTTPS (443) + redirect 80 -> 443
cat > /etc/nginx/sites-available/display <<'NGINX'
server {
    server_name display.leko97.com.br;
    location = / { return 301 https://display.leko97.com.br/src/display/pages/index.php; }
    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }

    listen 443 ssl; # managed by Certbot
    ssl_certificate /etc/letsencrypt/live/leko97.com.br/fullchain.pem; # managed by Certbot
    ssl_certificate_key /etc/letsencrypt/live/leko97.com.br/privkey.pem; # managed by Certbot
    include /etc/letsencrypt/options-ssl-nginx.conf; # managed by Certbot
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem; # managed by Certbot
}
server {
    if ($host = display.leko97.com.br) {
        return 301 https://$host$request_uri;
    } # managed by Certbot

    listen 80;
    server_name display.leko97.com.br;
    return 404; # managed by Certbot
}
NGINX
nginx -t && systemctl reload nginx
```

> **Display configurado em 2026-06-12** seguindo exatamente este procedimento.
> Para outro módulo, basta trocar `display` e o caminho de entrada
> (`/src/<modulo>/pages/...`) e incluir o novo `-d` na lista do certbot.

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
