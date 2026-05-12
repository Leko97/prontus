<!DOCTYPE html>
<html lang="pt-BR">
<?php $pageTitle = 'Acesso à Cozinha — Prontus KDS'; require_once __DIR__ . '/inc/head.php'; ?>
<body>
  <div class="kds-login-page">
    <div class="kds-login-card">

      <div class="kds-login-logo">
        <div class="kds-login-icon">👨‍🍳</div>
        <div>
          <h1 class="kds-login-title">Acesso à Cozinha</h1>
          <p class="kds-login-sub">Painel de Pedidos — Prontus KDS</p>
        </div>
      </div>

      <div id="errorAlert" class="alert alert-danger hidden"
        style="background:rgba(231,76,60,0.15);border-color:var(--color-danger);color:#ff8a80;margin-bottom:20px">
        ❌ Credenciais inválidas. Tente novamente.
      </div>

      <form id="loginForm" action="/api/auth/login" method="POST" novalidate>
        <input type="hidden" name="redirect" value="/src/kds/pages/index.php">

        <div class="form-group">
          <label class="form-label" for="email">E-mail</label>
          <input type="email" id="email" name="email" class="form-control"
            placeholder="cozinha@prontus.app" autocomplete="email">
        </div>

        <div class="form-group">
          <label class="form-label" for="senha">Senha</label>
          <input type="password" id="senha" name="senha" class="form-control"
            placeholder="••••••" autocomplete="current-password">
        </div>

        <button type="submit" class="btn btn-primary btn-full" id="btnEntrar"
          style="height:56px;font-size:var(--text-base)">
          Entrar no painel
        </button>
      </form>

      <p style="text-align:center;margin-top:24px;font-size:11px;color:rgba(255,255,255,0.25)">
        Use as credenciais fornecidas pelo administrador
      </p>
    </div>
  </div>

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
</body>
</html>
