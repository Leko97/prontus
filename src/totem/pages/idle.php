<!DOCTYPE html>
<html lang="pt-BR">
<?php $pageTitle = 'Prontus — Toque para começar'; require_once __DIR__ . '/inc/head.php'; ?>
<style>
  html, body {
    height: 100%;
    margin: 0;
    overflow: hidden;
  }

  .idle-screen {
    height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: var(--color-primary, #FF6B35);
    cursor: pointer;
    user-select: none;
    text-align: center;
    padding: 40px;
    gap: 24px;
  }

  .idle-logo {
    width: 120px;
    height: 120px;
    border-radius: 24px;
    object-fit: contain;
    background: rgba(255,255,255,0.15);
    padding: 12px;
    display: none;
  }

  .idle-logo-emoji {
    font-size: 80px;
    line-height: 1;
  }

  .idle-nome {
    font-size: 42px;
    font-weight: 800;
    color: #ffffff;
    letter-spacing: -1px;
    text-shadow: 0 2px 8px rgba(0,0,0,0.15);
  }

  .idle-slogan {
    font-size: 20px;
    color: rgba(255,255,255,0.8);
    font-weight: 400;
  }

  .idle-cta {
    margin-top: 20px;
    font-size: 28px;
    font-weight: 700;
    color: #ffffff;
    animation: pulso 2s ease-in-out infinite;
    text-shadow: 0 2px 12px rgba(0,0,0,0.2);
  }

  .idle-toque {
    font-size: 64px;
    animation: pulso 2s ease-in-out infinite;
  }

  @keyframes pulso {
    0%, 100% { opacity: 1; transform: scale(1); }
    50%       { opacity: 0.7; transform: scale(0.97); }
  }
</style>
<body>
  <div class="idle-screen" id="idleScreen">
    <img id="idleLogo" class="idle-logo" src="" alt="Logo">
    <div id="idleEmoji" class="idle-logo-emoji">🍽️</div>
    <div class="idle-nome" id="idleNome">Prontus</div>
    <div class="idle-slogan" id="idleSlogan">Peça, aguarde, saboreie</div>
    <div class="idle-toque">👆</div>
    <div class="idle-cta">Toque para fazer seu pedido</div>
  </div>

  <script type="module" src="/src/totem/assets/js/idle.js"></script>
</body>
</html>
