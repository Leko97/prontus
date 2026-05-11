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

    <!-- Filtros -->
    <div class="filter-bar">
      <div class="filter-bar-title">Filtrar por categoria</div>
      <div class="category-filters" id="categoryFilters">

        <!-- Filtro de restrição alimentar -->
        <div class="restricao-filter-wrapper">
          <button class="filter-btn" id="btnRestricao">
            <span class="filter-icon">⚠️</span>
            <span class="filter-label">Restrições</span>
          </button>
          <div class="restricao-dropdown hidden" id="restricaoDropdown">
            <div class="restricao-option" data-value="sem-gluten">
              <input type="checkbox"> <span class="tag-restricao sem-gluten">Sem Glúten</span>
            </div>
            <div class="restricao-option" data-value="vegetariano">
              <input type="checkbox"> <span class="tag-restricao vegetariano">Vegetariano</span>
            </div>
            <div class="restricao-option" data-value="vegano">
              <input type="checkbox"> <span class="tag-restricao vegano">Vegano</span>
            </div>
            <div class="restricao-option" data-value="sem-lactose">
              <input type="checkbox"> <span class="tag-restricao sem-lactose">Sem Lactose</span>
            </div>
            <div class="restricao-option" data-value="sem-amendoim">
              <input type="checkbox"> <span class="tag-restricao sem-amendoim">Sem Amendoim</span>
            </div>
          </div>
        </div>

      </div>
    </div>

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
</body>
</html>
