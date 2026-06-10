<!DOCTYPE html>
<html lang="pt-BR">
<?php $pageTitle = 'Acompanhar Pedido — Prontus'; require_once __DIR__ . '/inc/head.php'; ?>
<body>

  <header class="totem-header">
    <a href="/src/totem/pages/index.php" class="totem-logo">
      <div class="totem-logo-icon">🍽️</div>
      <div>
        <div class="totem-logo-name">Prontus</div>
        <div class="totem-logo-sub">Acompanhar pedido</div>
      </div>
    </a>
  </header>

  <div class="totem-container">
    <div class="senha-page">

      <h1 class="senha-titulo" style="margin-bottom:8px">Acompanhar Pedido</h1>
      <p style="color:var(--color-text-muted);margin-bottom:32px;text-align:center">
        Digite o número da senha exibido no seu comprovante
      </p>

      <!-- Formulário de consulta -->
      <div style="display:flex;gap:12px;margin-bottom:32px;width:100%;max-width:360px">
        <input
          type="text"
          id="inputSenha"
          placeholder="Ex: 042"
          maxlength="10"
          style="flex:1;font-size:var(--text-2xl);font-weight:700;text-align:center;
                 padding:16px;border:2px solid var(--color-border);border-radius:12px;
                 outline:none;background:var(--color-surface)"
          autocomplete="off"
          inputmode="numeric"
        >
        <button class="btn btn-primary btn-lg" id="btnConsultar" style="white-space:nowrap">
          Consultar
        </button>
      </div>

      <!-- Resultado -->
      <div id="resultadoArea" style="width:100%;max-width:400px"></div>

      <a href="/src/totem/pages/index.php"
         style="margin-top:32px;color:var(--color-text-muted);font-size:var(--text-sm);text-decoration:none">
        ← Fazer novo pedido
      </a>

    </div>
  </div>

  <script type="module" src="/src/totem/assets/js/acompanhar.js"></script>
</body>
</html>
