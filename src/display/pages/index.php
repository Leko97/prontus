<!DOCTYPE html>
<html lang="pt-BR">
<?php $pageTitle = 'Prontus — Painel de Senhas'; require_once __DIR__ . '/inc/head.php'; ?>
<body>

  <!-- Header -->
  <header class="display-header">
    <div class="display-logo">
      <img id="displayLogoImg" src="" alt="" style="height:40px;border-radius:6px;display:none">
      <div class="display-logo-icon" id="displayLogoIcon">🍽️</div>
      <div>
        <div class="display-logo-name" id="displayNome">Prontus</div>
        <div class="display-logo-sub">Painel de Senhas</div>
      </div>
    </div>

    <div class="display-header-right">
      <div class="connection-status">
        <div class="conn-dot" id="connDot"></div>
        <span>ao vivo</span>
      </div>
      <div class="display-clock" id="displayClock">00:00</div>
      <button class="btn-fullscreen" id="btnFullscreen" title="Tela cheia">⛶</button>
    </div>
  </header>

  <!-- Board principal -->
  <div class="display-board">

    <!-- Coluna: Em Preparo -->
    <div class="display-col em-preparo">
      <div class="display-col-header">
        <span class="display-col-title">🔥 Em Preparo</span>
        <span class="display-col-count" id="emPreparoCount">0</span>
      </div>
      <div class="display-col-body" id="emPreparoList">
        <div class="display-col-empty">
          <div class="display-col-empty-icon">⏳</div>
          <div class="display-col-empty-text">Nenhum pedido em preparo</div>
        </div>
      </div>
    </div>

    <!-- Coluna: Prontas para Retirada -->
    <div class="display-col prontas">
      <div class="display-col-header">
        <span class="display-col-title">🔔 Prontas para Retirada</span>
        <span class="display-col-count" id="prontasCount">0</span>
      </div>
      <div class="display-col-body" id="prontasList">
        <div class="display-col-empty">
          <div class="display-col-empty-icon">✅</div>
          <div class="display-col-empty-text">Nenhuma senha pronta</div>
        </div>
      </div>
    </div>

  </div>

  <!-- Rodapé marquee com mensagem personalizada -->
  <div id="displayMarqueeBar" class="display-marquee" style="display:none">
    <div id="displayMarqueeText" class="display-marquee-text"></div>
  </div>

  <script type="module" src="/src/display/assets/js/display.js"></script>
  <script type="module">
    /* Carregar configurações e personalizar o display */
    try {
      const res = await fetch('/api/configuracoes');
      if (!res.ok) throw new Error();
      const cfg = await res.json();

      /* CSS variables dinâmicas */
      if (cfg.cor_primaria)   document.documentElement.style.setProperty('--color-primary',   cfg.cor_primaria);
      if (cfg.cor_secundaria) document.documentElement.style.setProperty('--color-secondary',  cfg.cor_secundaria);

      /* Nome do estabelecimento */
      const nomeEl = document.getElementById('displayNome');
      if (nomeEl && cfg.nome_estabelecimento) nomeEl.textContent = cfg.nome_estabelecimento;

      /* Logo */
      if (cfg.logo_url) {
        const img  = document.getElementById('displayLogoImg');
        const icon = document.getElementById('displayLogoIcon');
        img.src = cfg.logo_url;
        img.style.display = 'block';
        if (icon) icon.style.display = 'none';
      }

      /* Marquee */
      if (cfg.mensagem_display) {
        const bar  = document.getElementById('displayMarqueeBar');
        const text = document.getElementById('displayMarqueeText');
        text.textContent = cfg.mensagem_display;
        bar.style.display = 'block';
      }
    } catch {
      /* Sem configurações: mantém valores padrão */
    }
  </script>
</body>
</html>
