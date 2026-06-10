<!DOCTYPE html>
<html lang="pt-BR">
<?php $pageTitle = 'Pedido Confirmado — Prontus'; require_once __DIR__ . '/inc/head.php'; ?>
<body>

  <header class="totem-header">
    <div class="totem-logo">
      <div class="totem-logo-icon">🍽️</div>
      <div>
        <div class="totem-logo-name">Prontus</div>
        <div class="totem-logo-sub">Pedido confirmado!</div>
      </div>
    </div>
  </header>

  <div class="totem-container">
    <div class="senha-page">

      <!-- Senha em destaque -->
      <div class="senha-badge" id="senhaBadge">#000</div>

      <h1 class="senha-titulo">Pedido recebido! 🎉</h1>
      <p class="senha-msg">
        Aguarde seu número ser chamado na tela. Seu pedido está sendo preparado com carinho.
      </p>

      <!-- Resumo dos itens -->
      <div class="senha-resumo" id="senhaResumo">
        <div class="senha-resumo-title">Resumo do pedido</div>
        <div id="senhaItensList">
          <div class="senha-resumo-item">
            <span style="color:var(--color-text-muted)">Carregando…</span>
          </div>
        </div>
      </div>

      <!-- Ações -->
      <div style="display:flex;flex-direction:column;gap:12px;width:100%;max-width:320px">
        <button class="btn btn-primary btn-lg" id="btnAcompanhar">
          📱 Acompanhar meu pedido
        </button>
        <button class="btn btn-ghost btn-lg" id="btnNovoPedido">
          🍽️ Fazer novo pedido
        </button>
      </div>

      <!-- Avaliação de experiência -->
      <div id="avaliacaoSection" style="
        width:100%;max-width:360px;
        background:var(--color-surface);
        border:1px solid var(--color-border);
        border-radius:14px;
        padding:20px;
        text-align:center;
        margin-top:4px
      ">
        <div style="font-weight:600;margin-bottom:12px">Como foi seu atendimento hoje?</div>
        <div style="display:flex;justify-content:center;gap:12px;font-size:32px" id="avaliacaoEmojis">
          <button class="btn-emoji" data-nota="1" title="Péssimo">😡</button>
          <button class="btn-emoji" data-nota="2" title="Ruim">😕</button>
          <button class="btn-emoji" data-nota="3" title="Regular">😐</button>
          <button class="btn-emoji" data-nota="4" title="Bom">🙂</button>
          <button class="btn-emoji" data-nota="5" title="Ótimo">😍</button>
        </div>
        <div id="avaliacaoFeedback" style="display:none;color:var(--color-primary);font-weight:600;margin-top:8px">
          Obrigado pelo feedback!
        </div>
        <button id="avaliacaoSkip"
          style="margin-top:8px;background:none;border:none;color:var(--color-text-muted);
                 font-size:var(--text-xs);cursor:pointer;text-decoration:underline">
          Pular
        </button>
      </div>

      <style>
        .btn-emoji {
          background: none; border: none; cursor: pointer;
          line-height: 1; padding: 4px; border-radius: 8px;
          transition: transform 0.15s ease;
        }
        .btn-emoji:hover { transform: scale(1.2); }
      </style>

      <div class="countdown" id="countdown">
        Redirecionando automaticamente em <span class="countdown-n" id="countdownN">30</span>s
      </div>

    </div>
  </div>

  <script type="module">
    import { formatCurrency, getParam, escapeHtml } from '/src/shared/js/utils.js';
    import { limparCarrinho } from '/src/totem/assets/js/carrinho.js';

    const senha = getParam('senha') || '#???';
    document.getElementById('senhaBadge').textContent = senha;
    document.title = `Senha ${senha} — Prontus`;

    /* Resumo salvo antes de limpar o carrinho */
    const resumoRaw = sessionStorage.getItem('prontus_ultimo_resumo');
    let resumo = null;
    try { resumo = JSON.parse(resumoRaw); } catch {}

    const list = document.getElementById('senhaItensList');

    if (resumo?.itens?.length) {
      list.innerHTML = resumo.itens.map(item => `
        <div class="senha-resumo-item">
          <span>${item.quantidade}× ${escapeHtml(item.produto)}</span>
          ${item.extras?.length ? `<span style="color:var(--color-text-muted);font-size:var(--text-xs)">+ ${item.extras.map(escapeHtml).join(', ')}</span>` : ''}
        </div>
      `).join('');

      if (resumo.total) {
        list.innerHTML += `
          <div class="senha-resumo-item" style="margin-top:8px;font-weight:600">
            <span>Total</span>
            <span style="color:var(--color-primary)">${formatCurrency(resumo.total)}</span>
          </div>
        `;
      }
    } else {
      list.innerHTML = `
        <div class="senha-resumo-item">
          <span style="color:var(--color-text-muted)">Pedido registrado com sucesso.</span>
        </div>
      `;
    }

    /* Limpar dados da sessão */
    limparCarrinho();
    sessionStorage.removeItem('prontus_ultimo_resumo');

    /* Avaliação de experiência */
    const pedidoId = parseInt(getParam('pedido_id'), 10) || 0;
    const avaliacaoSection = document.getElementById('avaliacaoSection');

    if (pedidoId > 0) {
      document.querySelectorAll('.btn-emoji').forEach(btn => {
        btn.addEventListener('click', async () => {
          const nota = parseInt(btn.dataset.nota, 10);
          try {
            await fetch('/api/avaliacoes', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ pedido_id: pedidoId, nota }),
            });
          } catch { /* silencioso */ }
          document.getElementById('avaliacaoEmojis').style.display = 'none';
          document.getElementById('avaliacaoSkip').style.display = 'none';
          document.getElementById('avaliacaoFeedback').style.display = '';
        });
      });
      document.getElementById('avaliacaoSkip').addEventListener('click', () => {
        if (avaliacaoSection) avaliacaoSection.style.display = 'none';
      });
    } else {
      /* Sem pedido_id: ocultar seção */
      if (avaliacaoSection) avaliacaoSection.style.display = 'none';
    }

    /* Botão acompanhar pedido */
    document.getElementById('btnAcompanhar').addEventListener('click', () => {
      const s = (getParam('senha') || '').replace('#', '');
      window.location.href = `/src/totem/pages/acompanhar.php?senha=${encodeURIComponent(s)}`;
    });

    /* Botão novo pedido */
    document.getElementById('btnNovoPedido').addEventListener('click', () => {
      window.location.href = '/src/totem/pages/index.php';
    });

    /* Contagem regressiva e auto-redirect */
    let segundos = 30;
    const countN = document.getElementById('countdownN');

    const timer = setInterval(() => {
      segundos--;
      if (countN) countN.textContent = segundos;

      if (segundos <= 0) {
        clearInterval(timer);
        window.location.href = '/src/totem/pages/index.php';
      }
    }, 1000);

    /* Cancela a contagem só se o usuário começar a avaliar (idle timer é o fallback) */
    document.getElementById('avaliacaoEmojis')?.addEventListener('click', () => {
      clearInterval(timer);
      document.getElementById('countdown').style.display = 'none';
    }, { once: true });
  </script>
</body>
</html>
