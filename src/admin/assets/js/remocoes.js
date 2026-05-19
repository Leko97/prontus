import { getProdutos, getRemocoes, criarRemocao, deletarRemocao } from '/src/shared/js/api.js';
import { initAdminPage, showToast } from './admin.js';

await initAdminPage();

const selectProduto    = document.getElementById('selectProduto');
const cardRemocoes     = document.getElementById('cardRemocoes');
const listaRemocoes    = document.getElementById('listaRemocoes');
const tituloRemocoes   = document.getElementById('tituloRemocoes');
const inputNovaRemocao = document.getElementById('inputNovaRemocao');
const btnAddRemocao    = document.getElementById('btnAddRemocao');

let produtoAtual = null;

/* -------- Carregar produtos no select -------- */
async function carregarProdutos() {
  try {
    const produtos = await getProdutos();
    produtos.forEach(p => {
      const opt = document.createElement('option');
      opt.value = p.id;
      opt.textContent = p.nome;
      selectProduto.appendChild(opt);
    });
  } catch (err) {
    showToast('Erro ao carregar produtos.', 'error');
    console.error(err);
  }
}

/* -------- Carregar remoções do produto -------- */
async function carregarRemocoes(produtoId) {
  listaRemocoes.innerHTML = '<div class="spinner-center"><div class="spinner"></div></div>';
  try {
    const remocoes = await getRemocoes(produtoId);
    renderRemocoes(remocoes);
  } catch (err) {
    listaRemocoes.innerHTML = '<p style="color:var(--color-danger)">Erro ao carregar remoções.</p>';
    console.error(err);
  }
}

/* -------- Renderizar lista -------- */
function renderRemocoes(remocoes) {
  if (!remocoes.length) {
    listaRemocoes.innerHTML = `
      <p style="color:var(--color-text-muted);padding:8px 0">
        Nenhum ingrediente removível cadastrado para este produto.
      </p>`;
    return;
  }

  listaRemocoes.innerHTML = remocoes.map(r => `
    <div class="dynamic-item" style="margin-bottom:8px" data-id="${r.id}">
      <span style="flex:1;padding:0 8px">${r.nome}</span>
      <button type="button" class="btn-remove-item btn-excluir-remocao" data-id="${r.id}" title="Excluir">✕</button>
    </div>
  `).join('');

  listaRemocoes.querySelectorAll('.btn-excluir-remocao').forEach(btn => {
    btn.addEventListener('click', () => excluirRemocao(Number(btn.dataset.id)));
  });
}

/* -------- Excluir remoção -------- */
async function excluirRemocao(id) {
  try {
    await deletarRemocao(id);
    showToast('Ingrediente removido.');
    await carregarRemocoes(produtoAtual);
  } catch (err) {
    showToast('Erro ao excluir ingrediente.', 'error');
    console.error(err);
  }
}

/* -------- Adicionar remoção -------- */
async function adicionarRemocao() {
  const nome = inputNovaRemocao.value.trim();
  if (!nome) { showToast('Digite o nome do ingrediente.', 'error'); return; }
  if (!produtoAtual) { showToast('Selecione um produto primeiro.', 'error'); return; }

  btnAddRemocao.disabled = true;
  try {
    await criarRemocao({ produto_id: produtoAtual, nome });
    inputNovaRemocao.value = '';
    showToast('Ingrediente adicionado!');
    await carregarRemocoes(produtoAtual);
  } catch (err) {
    showToast('Erro ao adicionar ingrediente.', 'error');
    console.error(err);
  } finally {
    btnAddRemocao.disabled = false;
    inputNovaRemocao.focus();
  }
}

/* -------- Eventos -------- */
selectProduto.addEventListener('change', async () => {
  produtoAtual = Number(selectProduto.value) || null;
  if (!produtoAtual) {
    cardRemocoes.style.display = 'none';
    return;
  }

  const nomeOpt = selectProduto.options[selectProduto.selectedIndex].textContent;
  tituloRemocoes.textContent = `Ingredientes removíveis — ${nomeOpt}`;
  cardRemocoes.style.display = 'block';
  await carregarRemocoes(produtoAtual);
});

btnAddRemocao.addEventListener('click', adicionarRemocao);
inputNovaRemocao.addEventListener('keydown', (e) => {
  if (e.key === 'Enter') { e.preventDefault(); adicionarRemocao(); }
});

/* -------- Init -------- */
await carregarProdutos();
