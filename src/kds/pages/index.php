<!DOCTYPE html>
<html lang="pt-BR">
<?php $pageTitle = 'Cozinha — Prontus KDS'; require_once __DIR__ . '/inc/head.php'; ?>
<body>

  <!-- Header fixo -->
  <header class="kds-header">
    <div class="kds-logo">
      <div class="kds-logo-icon">👨‍🍳</div>
      <div>
        <span class="kds-logo-text">Prontus</span>
        <span class="kds-logo-badge">Cozinha</span>
      </div>
    </div>

    <div class="kds-header-center">
      <div>
        <div class="kds-clock" id="kdsClock">00:00:00</div>
        <div class="kds-date" id="kdsDate"></div>
      </div>
      <div style="display:flex;align-items:center;gap:8px">
        <div class="connection-dot" id="connDot" title="Conexão com o servidor"></div>
        <span style="font-size:var(--text-xs);color:rgba(255,255,255,0.35)">ao vivo</span>
      </div>
    </div>

    <div class="kds-header-right">
      <span class="kds-total-badge" id="totalBadge">carregando…</span>
      <button class="btn-kds-logout" id="btnLogout">🚪 Sair</button>
    </div>
  </header>

  <!-- Board de 3 colunas -->
  <div class="kds-board">

    <!-- Coluna: Recebido -->
    <div class="kds-col" data-status="recebido">
      <div class="kds-col-header recebido">
        <span>🔔 Recebido</span>
        <span class="kds-col-count">0</span>
      </div>
      <div class="kds-col-body">
        <div class="kds-col-empty">
          <div class="kds-col-empty-icon">✓</div>
          <div>Nenhum pedido</div>
        </div>
      </div>
    </div>

    <!-- Coluna: Em Preparo -->
    <div class="kds-col" data-status="em-preparo">
      <div class="kds-col-header em-preparo">
        <span>🔥 Em Preparo</span>
        <span class="kds-col-count">0</span>
      </div>
      <div class="kds-col-body">
        <div class="kds-col-empty">
          <div class="kds-col-empty-icon">✓</div>
          <div>Nenhum pedido</div>
        </div>
      </div>
    </div>

    <!-- Coluna: Pronto -->
    <div class="kds-col" data-status="pronto">
      <div class="kds-col-header pronto">
        <span>✅ Pronto</span>
        <span class="kds-col-count">0</span>
      </div>
      <div class="kds-col-body">
        <div class="kds-col-empty">
          <div class="kds-col-empty-icon">✓</div>
          <div>Nenhum pedido</div>
        </div>
      </div>
    </div>

  </div>

  <script type="module" src="/src/kds/assets/js/painel.js"></script>
  <script>
    /* Data no header */
    document.getElementById('kdsDate').textContent =
      new Date().toLocaleDateString('pt-BR', { weekday: 'short', day: 'numeric', month: 'short' });
  </script>
</body>
</html>
