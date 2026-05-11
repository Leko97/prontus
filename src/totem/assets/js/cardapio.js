import { getCardapio } from '/src/shared/js/api.js';
import { formatCurrency, restricaoLabel } from '/src/shared/js/utils.js';
import { totalItens, calcularTotal } from './carrinho.js';

let todosCategoria = [];
let todosProdutos  = [];
let categoriaAtiva = null;
let restricoesSelecionadas = new Set();

/* -------- Init -------- */
document.addEventListener('DOMContentLoaded', async () => {
  atualizarBarraCarrinho();
  await carregarCardapio();
  initRestricaoDropdown();
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
function initRestricaoDropdown() {
  const btn = document.getElementById('btnRestricao');
  const dropdown = document.getElementById('restricaoDropdown');
  if (!btn || !dropdown) return;

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
          <div class="produto-nome">${p.nome}</div>
          <div class="produto-desc">${p.descricao || ''}</div>
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
