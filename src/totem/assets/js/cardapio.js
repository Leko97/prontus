import { getCardapio, getRestricoes } from '/src/shared/js/api.js';
import { formatCurrency, restricaoLabel, escapeHtml } from '/src/shared/js/utils.js';
import { totalItens, calcularTotal } from './carrinho.js';

let todosCategoria = [];
let todosProdutos  = [];
let categoriaAtiva = null;
let restricoesSelecionadas = new Set();
let termoBusca = '';
let debounceTimer = null;

/* -------- Init -------- */
document.addEventListener('DOMContentLoaded', async () => {
  atualizarBarraCarrinho();
  await carregarCardapio();
  await initRestricaoDropdown();
  initBusca();
});

async function carregarCardapio() {
  const grid = document.getElementById('produtosGrid');
  grid.innerHTML = `<div class="empty-state"><div class="spinner" style="width:48px;height:48px;border-width:4px"></div></div>`;

  try {
    const data = await getCardapio();
    todosCategoria = data.categorias;
    todosProdutos  = data.produtos;

    renderFiltros();
    renderProdutos(todosProdutos);
  } catch (err) {
    grid.innerHTML = `
      <div class="empty-state">
        <div class="empty-state-icon">⚠️</div>
        <h3>Erro ao carregar cardápio</h3>
        <p>Tente novamente em instantes.</p>
      </div>`;
    console.error(err);
  }
}

/* -------- Filtros -------- */
function renderFiltros() {
  const container = document.getElementById('categoryFilters');
  if (!container) return;

  const todosBtn = criarFiltroBtn('Todos', '🍽️', null, true);
  container.appendChild(todosBtn);

  todosCategoria.forEach(cat => {
    container.appendChild(criarFiltroBtn(cat.nome, cat.icone, cat.id));
  });
}

function criarFiltroBtn(nome, icone, catId, ativo = false) {
  const btn = document.createElement('button');
  btn.className = `filter-btn${ativo ? ' active' : ''}`;
  btn.dataset.catId = catId ?? '';
  btn.innerHTML = `<span class="filter-icon">${icone}</span> ${nome}`;

  btn.addEventListener('click', () => {
    document.querySelectorAll('.filter-btn[data-cat-id]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    categoriaAtiva = catId;
    aplicarFiltros();
  });

  return btn;
}

/* -------- Dropdown de restrições -------- */
async function initRestricaoDropdown() {
  const btn = document.getElementById('btnRestricao');
  const dropdown = document.getElementById('restricaoDropdown');
  if (!btn || !dropdown) return;

  // Popula opções a partir do banco
  try {
    const restricoes = await getRestricoes();
    dropdown.innerHTML = restricoes.map(r => `
      <div class="restricao-option" data-value="${escapeHtml(r.slug)}">
        <input type="checkbox"> <span class="tag-restricao ${escapeHtml(r.slug)}">${escapeHtml(r.nome)}</span>
      </div>
    `).join('');
  } catch {
    /* mantém o que estiver no DOM como fallback */
  }

  btn.addEventListener('click', (e) => {
    e.stopPropagation();
    dropdown.classList.toggle('hidden');
  });

  document.addEventListener('click', (e) => {
    if (!dropdown.contains(e.target) && e.target !== btn) {
      dropdown.classList.add('hidden');
    }
  });

  dropdown.querySelectorAll('.restricao-option').forEach(opt => {
    opt.addEventListener('click', () => {
      const val = opt.dataset.value;
      const cb  = opt.querySelector('input');

      if (restricoesSelecionadas.has(val)) {
        restricoesSelecionadas.delete(val);
        opt.classList.remove('selected');
        if (cb) cb.checked = false;
      } else {
        restricoesSelecionadas.add(val);
        opt.classList.add('selected');
        if (cb) cb.checked = true;
      }

      const total = restricoesSelecionadas.size;
      btn.querySelector('.filter-label').textContent =
        total > 0 ? `Restrições (${total})` : 'Restrições';
      btn.classList.toggle('active', total > 0);

      aplicarFiltros();
    });
  });
}

/* -------- Busca -------- */
function initBusca() {
  const input = document.getElementById('buscaCardapio');
  const filtroBar = document.getElementById('filtroBar');
  if (!input) return;

  input.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      termoBusca = input.value.trim().toLowerCase();
      if (termoBusca.length >= 2) {
        /* Em busca ativa: esconder filtros de categoria */
        if (filtroBar) filtroBar.style.display = 'none';
        filtrarPorBusca(termoBusca);
      } else {
        if (filtroBar) filtroBar.style.display = '';
        termoBusca = '';
        aplicarFiltros();
      }
    }, 200);
  });

  input.addEventListener('search', () => {
    if (!input.value) {
      termoBusca = '';
      if (filtroBar) filtroBar.style.display = '';
      aplicarFiltros();
    }
  });
}

function filtrarPorBusca(termo) {
  const resultado = todosProdutos.filter(p =>
    p.nome.toLowerCase().includes(termo) ||
    (p.descricao || '').toLowerCase().includes(termo)
  );

  const vazioBusca = document.getElementById('buscaVazio');
  if (vazioBusca) {
    if (!resultado.length) {
      vazioBusca.textContent = `Nenhum produto encontrado para "${termo}"`;
      vazioBusca.classList.remove('hidden');
    } else {
      vazioBusca.classList.add('hidden');
    }
  }

  renderProdutos(resultado);
}

/* -------- Aplicar filtros combinados -------- */
function aplicarFiltros() {
  let resultado = todosProdutos;

  if (categoriaAtiva !== null) {
    resultado = resultado.filter(p => p.categoriaId === categoriaAtiva);
  }

  if (restricoesSelecionadas.size > 0) {
    resultado = resultado.filter(p =>
      [...restricoesSelecionadas].every(r => (p.restricoes || []).includes(r))
    );
  }

  const vazioBusca = document.getElementById('buscaVazio');
  if (vazioBusca) vazioBusca.classList.add('hidden');

  renderProdutos(resultado);
}

/* -------- Render grid -------- */
function renderProdutos(produtos) {
  const grid = document.getElementById('produtosGrid');

  if (!produtos.length) {
    grid.innerHTML = `
      <div class="empty-state">
        <div class="empty-state-icon">🔍</div>
        <h3>Nenhum produto encontrado</h3>
        <p>Tente outro filtro ou restrição.</p>
      </div>`;
    return;
  }

  grid.innerHTML = produtos.map(p => {
    const icon = getCategoryIcon(p.categoriaId);
    const restricoesHtml = (p.restricoes || []).map(r =>
      `<span class="tag-restricao ${r}">${restricaoLabel(r)}</span>`
    ).join('');

    return `
      <a class="produto-card" href="/src/totem/pages/produto.php?id=${p.id}">
        <div class="produto-img">${icon}</div>
        <div class="produto-info">
          <div class="produto-nome">${escapeHtml(p.nome)}</div>
          <div class="produto-desc">${escapeHtml(p.descricao || '')}</div>
          ${restricoesHtml ? `<div class="produto-restricoes">${restricoesHtml}</div>` : ''}
        </div>
        <div class="produto-footer">
          <span class="produto-preco">${formatCurrency(p.preco)}</span>
          <button class="btn-add-produto" onclick="event.preventDefault();event.stopPropagation();location.href='/src/totem/pages/produto.php?id=${p.id}'">
            + Adicionar
          </button>
        </div>
      </a>
    `;
  }).join('');
}

function getCategoryIcon(catId) {
  const cat = todosCategoria.find(c => c.id === catId);
  return cat?.icone ?? '🍽️';
}

/* -------- Barra flutuante do carrinho -------- */
export function atualizarBarraCarrinho() {
  const barra   = document.getElementById('cartFloat');
  const count   = document.getElementById('cartFloatCount');
  const total   = document.getElementById('cartFloatTotal');
  const badge   = document.getElementById('headerCartBadge');
  const n       = totalItens();

  if (badge) {
    badge.textContent = n;
    badge.classList.toggle('hidden', n === 0);
  }

  if (!barra) return;

  if (n > 0) {
    if (count) count.textContent = `${n} ${n === 1 ? 'item' : 'itens'} no pedido`;
    if (total) total.textContent = formatCurrency(calcularTotal());
    barra.classList.add('visible');
  } else {
    barra.classList.remove('visible');
  }
}
