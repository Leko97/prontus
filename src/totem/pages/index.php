<!DOCTYPE html>
<html lang="pt-BR">
<?php $pageTitle = 'Cardápio — Prontus'; require_once __DIR__ . '/inc/head.php'; ?>
<body>

  <!-- Header -->
  <header class="totem-header">
    <a href="/src/totem/pages/index.php" class="totem-logo">
      <div class="totem-logo-icon">🍽️</div>
      <div>
        <div class="totem-logo-name">Prontus</div>
        <div class="totem-logo-sub">Faça seu pedido</div>
      </div>
    </a>
    <div class="totem-header-right">
      <a href="/src/totem/pages/carrinho.php" class="cart-btn" id="headerCartBtn">
        <span class="cart-btn-icon">🛒</span>
        <span>Carrinho</span>
        <span class="cart-badge hidden" id="headerCartBadge">0</span>
      </a>
    </div>
  </header>

  <!-- Conteúdo -->
  <div class="totem-container">

    <!-- Busca -->
    <div style="padding:0 0 12px 0;position:relative">
      <div style="display:flex;gap:8px;align-items:center">
        <span style="position:absolute;left:14px;font-size:18px;pointer-events:none">🔍</span>
        <input
          type="search"
          id="buscaCardapio"
          placeholder="Buscar no cardápio..."
          autocomplete="off"
          style="width:100%;padding:12px 14px 12px 42px;
                 border:2px solid var(--color-border);border-radius:12px;
                 font-size:var(--text-base);background:var(--color-surface);outline:none"
        >
      </div>
      <div id="buscaVazio" class="hidden"
           style="text-align:center;padding:32px 0;color:var(--color-text-muted)"></div>
    </div>

    <!-- Filtros -->
    <div class="filter-bar" id="filtroBar">
      <div class="filter-bar-title">Filtrar por categoria</div>
      <div class="category-filters" id="categoryFilters">

        <!-- Filtro de restrição alimentar -->
        <div class="restricao-filter-wrapper">
          <button class="filter-btn" id="btnRestricao">
            <span class="filter-icon">⚠️</span>
            <span class="filter-label">Restrições</span>
          </button>
          <div class="restricao-dropdown hidden" id="restricaoDropdown"></div>
        </div>

      </div>
    </div>

    <!-- Combos em Destaque (renderizado via JS se existirem) -->
    <div id="combosSection" class="hidden"></div>

    <!-- Grid de produtos -->
    <div class="produtos-section-title">Nosso cardápio</div>
    <div class="produtos-grid" id="produtosGrid">
      <div class="empty-state">
        <div class="spinner" style="width:48px;height:48px;border-width:4px;margin:0 auto"></div>
      </div>
    </div>

  </div>

  <!-- Barra flutuante do carrinho -->
  <div class="cart-float" id="cartFloat">
    <div class="cart-float-info">
      <span class="cart-float-count" id="cartFloatCount">0 itens no pedido</span>
      <span class="cart-float-total" id="cartFloatTotal">R$ 0,00</span>
    </div>
    <a href="/src/totem/pages/carrinho.php" class="cart-float-btn">
      Ver pedido →
    </a>
  </div>

  <script type="module" src="/src/totem/assets/js/cardapio.js"></script>
  <script type="module">
    import { escapeHtml } from '/src/shared/js/utils.js';

    /* Carregar combos em destaque */
    try {
      const res = await fetch('/api/combos');
      if (res.ok) {
        const combos = await res.json();
        if (combos.length) {
          const section = document.getElementById('combosSection');
          section.classList.remove('hidden');
          section.innerHTML = `
            <div class="produtos-section-title" style="color:var(--color-primary)">🎁 Combos em Destaque</div>
            <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:24px">
              ${combos.map(c => `
                <div style="
                  background:var(--color-surface);
                  border:2px solid var(--color-primary);
                  border-radius:14px;
                  padding:16px;
                  display:flex;
                  align-items:center;
                  justify-content:space-between;
                  gap:12px
                ">
                  <div style="flex:1">
                    <div style="font-weight:700;font-size:var(--text-lg)">${escapeHtml(c.nome)}</div>
                    ${c.descricao ? `<div style="color:var(--color-text-muted);font-size:var(--text-sm);margin-top:2px">${escapeHtml(c.descricao)}</div>` : ''}
                    <div style="font-size:var(--text-xs);color:var(--color-text-muted);margin-top:6px">
                      ${(c.itens || []).map(i => `${i.quantidade}× ${escapeHtml(i.produto_nome)}`).join(' + ')}
                    </div>
                  </div>
                  <div style="text-align:right;flex-shrink:0">
                    <div style="font-size:var(--text-xl);font-weight:700;color:var(--color-primary)">
                      R$ ${Number(c.preco).toFixed(2).replace('.', ',')}
                    </div>
                    <button
                      class="btn btn-primary btn-sm"
                      style="margin-top:8px"
                      data-combo='${JSON.stringify(c)}'
                      onclick="window._adicionarCombo(this)"
                    >
                      + Adicionar
                    </button>
                  </div>
                </div>
              `).join('')}
            </div>
          `;
        }
      }
    } catch { /* combos não disponíveis */ }

    /* Adicionar todos os itens de um combo ao carrinho */
    window._adicionarCombo = function(btn) {
      const c = JSON.parse(btn.dataset.combo);
      Promise.all([
        import('/src/totem/assets/js/carrinho.js'),
        import('/src/totem/assets/js/cardapio.js'),
      ]).then(([{ adicionarItem }, { atualizarBarraCarrinho }]) => {
        (c.itens || []).forEach(item => {
          adicionarItem(
            { id: item.produto_id, nome: item.produto_nome, preco: item.produto_preco, restricoes: [] },
            [],
            [],
            item.quantidade
          );
        });
        atualizarBarraCarrinho();
        btn.textContent = '✓ Adicionado';
        btn.disabled = true;
        setTimeout(() => { btn.textContent = '+ Adicionar'; btn.disabled = false; }, 2000);
      });
    };
  </script>
</body>
</html>
