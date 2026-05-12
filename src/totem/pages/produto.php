<!DOCTYPE html>
<html lang="pt-BR">
<?php $pageTitle = 'Produto — Prontus'; require_once __DIR__ . '/inc/head.php'; ?>
<body>

  <!-- Header -->
  <header class="totem-header">
    <a href="/src/totem/pages/index.php" class="totem-logo">
      <div class="totem-logo-icon">🍽️</div>
      <div>
        <div class="totem-logo-name">Prontus</div>
      </div>
    </a>
    <div class="totem-header-right">
      <a href="/src/totem/pages/carrinho.php" class="cart-btn">
        <span class="cart-btn-icon">🛒</span>
        <span>Carrinho</span>
        <span class="cart-badge hidden" id="headerCartBadge">0</span>
      </a>
    </div>
  </header>

  <div class="totem-container">

    <div style="margin-bottom:var(--space-6)">
      <a href="/src/totem/pages/index.php" class="btn-back">← Voltar ao cardápio</a>
    </div>

    <!-- Skeleton enquanto carrega -->
    <div id="loadingState" style="text-align:center;padding:64px">
      <div class="spinner spinner-lg" style="margin:0 auto"></div>
    </div>

    <!-- Conteúdo do produto -->
    <div id="produtoContent" class="hidden">

      <div class="produto-detalhe">

        <!-- Imagem -->
        <div class="produto-detalhe-img" id="produtoImg">🍽️</div>

        <!-- Info + ações -->
        <div class="produto-detalhe-info">

          <!-- Badges de restrição -->
          <div id="produtoRestricoes" style="display:flex;flex-wrap:wrap;gap:6px"></div>

          <div>
            <h1 class="produto-detalhe-nome" id="produtoNome">—</h1>
            <p class="produto-detalhe-desc" id="produtoDesc"></p>
          </div>

          <div class="produto-detalhe-preco" id="produtoPrecoBase">R$ 0,00</div>

          <!-- Quantidade -->
          <div>
            <div class="opcoes-title">🔢 Quantidade</div>
            <div class="quantidade-wrapper">
              <button class="btn-qty-lg" id="btnMenos" disabled>−</button>
              <span class="qty-lg-display" id="qtyDisplay">1</span>
              <button class="btn-qty-lg" id="btnMais">+</button>
            </div>
          </div>

        </div>
      </div>

      <!-- Remoções -->
      <div class="opcoes-section" id="secaoRemocoes" style="margin-top:var(--space-8)">
        <div class="opcoes-title">🚫 Remover ingredientes</div>
        <div class="remocoes-grid" id="remocoesList"></div>
      </div>

      <!-- Extras -->
      <div class="opcoes-section" id="secaoExtras">
        <div class="opcoes-title">➕ Adicionar extras</div>
        <div class="extras-list" id="extrasList"></div>
      </div>

      <!-- Total em tempo real -->
      <div class="total-realtime" style="margin-top:var(--space-6)">
        <div>
          <div class="total-realtime-label">Total deste item</div>
        </div>
        <div class="total-realtime-valor" id="totalRealtime">R$ 0,00</div>
      </div>

      <!-- Botão adicionar -->
      <button class="btn btn-primary btn-full btn-lg" id="btnAdicionar"
        style="margin-top:var(--space-6)">
        🛒 Adicionar ao pedido
      </button>

    </div>

    <!-- Erro -->
    <div id="errorState" class="hidden" style="text-align:center;padding:64px">
      <div style="font-size:3rem;margin-bottom:16px">⚠️</div>
      <h2>Produto não encontrado</h2>
      <a href="/src/totem/pages/index.php" class="btn btn-primary" style="margin-top:24px">
        Voltar ao cardápio
      </a>
    </div>

  </div>

  <script type="module">
    import { getProdutoById } from '/src/shared/js/api.js';
    import { formatCurrency, getParam, restricaoLabel } from '/src/shared/js/utils.js';
    import { adicionarItem, atualizarItem, getCarrinho, totalItens } from '/src/totem/assets/js/carrinho.js';

    const produtoId = getParam('id');
    const editarIdx = getParam('editar');
    let produto = null;
    let quantidade = 1;
    const extrasQtd = {};
    const removidos = new Set();

    /* Badge header */
    function atualizarBadge() {
      const badge = document.getElementById('headerCartBadge');
      const n = totalItens();
      if (badge) { badge.textContent = n; badge.classList.toggle('hidden', n === 0); }
    }

    atualizarBadge();

    if (!produtoId) {
      mostrarErro();
    } else {
      try {
        produto = await getProdutoById(produtoId);
        renderProduto(produto);
        document.getElementById('loadingState').classList.add('hidden');
        document.getElementById('produtoContent').classList.remove('hidden');
      } catch (err) {
        console.error(err);
        mostrarErro();
      }
    }

    function mostrarErro() {
      document.getElementById('loadingState').classList.add('hidden');
      document.getElementById('errorState').classList.remove('hidden');
    }

    function renderProduto(p) {
      /* Ícone / imagem */
      const icons = { 1: '🍔', 2: '🥤', 3: '🍨', 4: '🍟' };
      document.getElementById('produtoImg').textContent = icons[p.categoriaId] ?? '🍽️';

      document.getElementById('produtoNome').textContent     = p.nome;
      document.getElementById('produtoDesc').textContent     = p.descricao || '';
      document.getElementById('produtoPrecoBase').textContent = formatCurrency(p.preco);

      /* Restrições */
      const restricoesEl = document.getElementById('produtoRestricoes');
      restricoesEl.innerHTML = (p.restricoes || []).map(r =>
        `<span class="tag-restricao ${r}">${restricaoLabel(r)}</span>`
      ).join('');

      /* Remoções */
      const secaoRem = document.getElementById('secaoRemocoes');
      if (p.remocoes?.length) {
        const grid = document.getElementById('remocoesList');
        grid.innerHTML = p.remocoes.map(r => `
          <button class="chip-remocao" data-remocao="${r}">${r}</button>
        `).join('');

        grid.querySelectorAll('.chip-remocao').forEach(chip => {
          chip.addEventListener('click', () => {
            const r = chip.dataset.remocao;
            if (removidos.has(r)) { removidos.delete(r); chip.classList.remove('removido'); chip.textContent = r; }
            else                  { removidos.add(r);    chip.classList.add('removido');    chip.textContent = `✕ ${r}`; }
            atualizarTotal();
          });
        });
      } else {
        secaoRem.style.display = 'none';
      }

      /* Extras */
      const secaoExt = document.getElementById('secaoExtras');
      if (p.extras?.length) {
        const list = document.getElementById('extrasList');
        list.innerHTML = p.extras.map(e => {
          extrasQtd[e.id] = 0;
          return `
            <div class="extra-item" data-extra-id="${e.id}">
              <div>
                <div class="extra-nome">${e.nome}</div>
                <div class="extra-preco">+ ${formatCurrency(e.preco)}</div>
              </div>
              <div class="extra-controles">
                <button class="btn-qty btn-extra-menos" data-id="${e.id}" disabled>−</button>
                <span class="qty-display" id="extra-qty-${e.id}">0</span>
                <button class="btn-qty btn-extra-mais" data-id="${e.id}">+</button>
              </div>
            </div>
          `;
        }).join('');

        list.querySelectorAll('.btn-extra-mais').forEach(btn => {
          btn.addEventListener('click', () => {
            const id = Number(btn.dataset.id);
            extrasQtd[id] = (extrasQtd[id] || 0) + 1;
            document.getElementById(`extra-qty-${id}`).textContent = extrasQtd[id];
            list.querySelector(`.btn-extra-menos[data-id="${id}"]`).disabled = false;
            list.querySelector(`.extra-item[data-extra-id="${id}"]`).classList.add('selecionado');
            atualizarTotal();
          });
        });

        list.querySelectorAll('.btn-extra-menos').forEach(btn => {
          btn.addEventListener('click', () => {
            const id = Number(btn.dataset.id);
            if (extrasQtd[id] <= 0) return;
            extrasQtd[id]--;
            document.getElementById(`extra-qty-${id}`).textContent = extrasQtd[id];
            btn.disabled = extrasQtd[id] === 0;
            if (extrasQtd[id] === 0) list.querySelector(`.extra-item[data-extra-id="${id}"]`).classList.remove('selecionado');
            atualizarTotal();
          });
        });
      } else {
        secaoExt.style.display = 'none';
      }

      /* Pré-preencher se edição */
      if (editarIdx !== null) {
        const carrinho = getCarrinho();
        const item = carrinho[Number(editarIdx)];
        if (item) {
          quantidade = item.quantidade;
          document.getElementById('qtyDisplay').textContent = quantidade;
          document.getElementById('btnMenos').disabled = quantidade <= 1;

          (item.remocoes || []).forEach(r => {
            removidos.add(r);
            const chip = document.querySelector(`.chip-remocao[data-remocao="${r}"]`);
            if (chip) { chip.classList.add('removido'); chip.textContent = `✕ ${r}`; }
          });

          (item.extras || []).forEach(e => {
            extrasQtd[e.id] = e.quantidade || 1;
            const qtyEl = document.getElementById(`extra-qty-${e.id}`);
            if (qtyEl) qtyEl.textContent = extrasQtd[e.id];
            const menosBtn = document.querySelector(`.btn-extra-menos[data-id="${e.id}"]`);
            if (menosBtn) menosBtn.disabled = false;
            const item2 = document.querySelector(`.extra-item[data-extra-id="${e.id}"]`);
            if (item2 && extrasQtd[e.id] > 0) item2.classList.add('selecionado');
          });
        }
      }

      atualizarTotal();
    }

    /* Quantidade principal */
    document.getElementById('btnMais').addEventListener('click', () => {
      quantidade++;
      document.getElementById('qtyDisplay').textContent = quantidade;
      document.getElementById('btnMenos').disabled = false;
      atualizarTotal();
    });

    document.getElementById('btnMenos').addEventListener('click', () => {
      if (quantidade <= 1) return;
      quantidade--;
      document.getElementById('qtyDisplay').textContent = quantidade;
      document.getElementById('btnMenos').disabled = quantidade <= 1;
      atualizarTotal();
    });

    /* Total em tempo real */
    function atualizarTotal() {
      if (!produto) return;
      const somatorioExtras = (produto.extras || []).reduce((acc, e) => {
        return acc + (parseFloat(e.preco) || 0) * (extrasQtd[e.id] || 0);
      }, 0);
      const total = ((parseFloat(produto.preco) || 0) + somatorioExtras) * quantidade;
      document.getElementById('totalRealtime').textContent = formatCurrency(total);
    }

    /* Adicionar ao pedido */
    document.getElementById('btnAdicionar').addEventListener('click', () => {
      if (!produto) return;

      const extrasComQtd = (produto.extras || [])
        .filter(e => (extrasQtd[e.id] || 0) > 0)
        .map(e => ({ ...e, quantidade: extrasQtd[e.id] }));

      if (editarIdx !== null) {
        atualizarItem(Number(editarIdx), extrasComQtd, [...removidos], quantidade);
      } else {
        adicionarItem(produto, extrasComQtd, [...removidos], quantidade);
      }

      window.location.href = '/src/totem/pages/index.php';
    });
  </script>
</body>
</html>
