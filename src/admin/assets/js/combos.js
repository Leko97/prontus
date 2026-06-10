import { initAdminPage, showToast, openModal, closeModal } from './admin.js';
import { getProdutos } from '/src/shared/js/api.js';
import { formatCurrency } from '/src/shared/js/utils.js';

const API_BASE = window.PRONTUS_API_URL || '/api';

let todosProdutos = [];

document.addEventListener('DOMContentLoaded', async () => {
  await initAdminPage();
  todosProdutos = await getProdutos().catch(() => []);
  await carregarCombos();

  document.getElementById('btnNovo')?.addEventListener('click', abrirModalNovo);
  document.getElementById('formCombo')?.addEventListener('submit', salvarCombo);
  document.getElementById('btnAdicionarItem')?.addEventListener('click', adicionarLinhaItem);
});

/* -------- Carregar lista -------- */
async function carregarCombos() {
  const tbody = document.getElementById('combosTbody');
  try {
    const res = await fetch(`${API_BASE}/combos`);
    const combos = await res.json();

    if (!combos.length) {
      tbody.innerHTML = `<tr><td colspan="5" class="table-empty">Nenhum combo cadastrado.</td></tr>`;
      return;
    }

    tbody.innerHTML = combos.map(c => `
      <tr>
        <td><strong>${c.nome}</strong>${c.descricao ? `<br><small style="color:var(--color-text-muted)">${c.descricao}</small>` : ''}</td>
        <td>${formatCurrency(c.preco)}</td>
        <td>${c.itens?.length ?? 0} item(s)</td>
        <td>
          <span class="badge ${c.ativo ? 'badge-success' : 'badge-muted'}">
            ${c.ativo ? 'Ativo' : 'Inativo'}
          </span>
        </td>
        <td>
          <button class="btn btn-ghost btn-sm" onclick="window._editarCombo(${c.id})">Editar</button>
          <button class="btn btn-danger btn-sm" onclick="window._excluirCombo(${c.id})">Excluir</button>
        </td>
      </tr>
    `).join('');
  } catch {
    tbody.innerHTML = `<tr><td colspan="5" class="table-empty">Erro ao carregar combos.</td></tr>`;
  }
}

/* -------- Abrir modal novo -------- */
function abrirModalNovo() {
  document.getElementById('modalTitle').textContent  = 'Novo Combo';
  document.getElementById('comboId').value           = '';
  document.getElementById('comboNome').value         = '';
  document.getElementById('comboDescricao').value    = '';
  document.getElementById('comboPreco').value        = '';
  document.getElementById('comboImagem').value       = '';
  document.getElementById('itensCombo').innerHTML    = '';
  adicionarLinhaItem();
  openModal('modalForm');
}

/* -------- Linha de item do combo -------- */
function adicionarLinhaItem(produto_id = '', quantidade = 1) {
  const container = document.getElementById('itensCombo');
  const div = document.createElement('div');
  div.style.cssText = 'display:flex;gap:8px;align-items:center';
  div.innerHTML = `
    <select class="form-control item-produto" style="flex:2">
      <option value="">Selecione um produto…</option>
      ${todosProdutos.map(p => `
        <option value="${p.id}" ${p.id == produto_id ? 'selected' : ''}>${p.nome}</option>
      `).join('')}
    </select>
    <input type="number" class="form-control item-qtd" value="${quantidade}" min="1" max="99"
      style="flex:0 0 72px;text-align:center" placeholder="Qtd">
    <button type="button" class="btn btn-ghost btn-sm" style="padding:8px"
      onclick="this.closest('div').remove()">✕</button>
  `;
  container.appendChild(div);
}

/* -------- Salvar combo -------- */
async function salvarCombo(e) {
  e.preventDefault();
  const btn = e.target.querySelector('[type=submit]');
  btn.disabled = true;
  btn.textContent = 'Salvando…';

  const id = document.getElementById('comboId').value;

  const itens = [...document.querySelectorAll('#itensCombo > div')].map(div => ({
    produto_id: Number(div.querySelector('.item-produto').value),
    quantidade: Number(div.querySelector('.item-qtd').value),
  })).filter(i => i.produto_id > 0);

  const payload = {
    nome:      document.getElementById('comboNome').value.trim(),
    descricao: document.getElementById('comboDescricao').value.trim(),
    preco:     parseFloat(document.getElementById('comboPreco').value),
    imagem:    document.getElementById('comboImagem').value.trim() || null,
    itens,
  };

  try {
    const method   = id ? 'PUT' : 'POST';
    const endpoint = id ? `${API_BASE}/combos/${id}` : `${API_BASE}/combos`;
    const res = await fetch(endpoint, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    if (!res.ok) throw new Error();
    closeModal('modalForm');
    showToast('Combo salvo com sucesso!');
    await carregarCombos();
  } catch {
    showToast('Erro ao salvar combo.', 'error');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Salvar Combo';
  }
}

/* -------- Editar -------- */
window._editarCombo = async function(id) {
  try {
    const res = await fetch(`${API_BASE}/combos`);
    const combos = await res.json();
    const combo = combos.find(c => c.id === id);
    if (!combo) return;

    document.getElementById('modalTitle').textContent  = 'Editar Combo';
    document.getElementById('comboId').value           = combo.id;
    document.getElementById('comboNome').value         = combo.nome;
    document.getElementById('comboDescricao').value    = combo.descricao || '';
    document.getElementById('comboPreco').value        = combo.preco;
    document.getElementById('comboImagem').value       = combo.imagem || '';

    document.getElementById('itensCombo').innerHTML = '';
    (combo.itens || []).forEach(i => adicionarLinhaItem(i.produto_id, i.quantidade));
    if (!combo.itens?.length) adicionarLinhaItem();

    openModal('modalForm');
  } catch {
    showToast('Erro ao carregar combo.', 'error');
  }
};

/* -------- Excluir (soft-delete) -------- */
window._excluirCombo = async function(id) {
  if (!confirm('Desativar este combo?')) return;
  try {
    const res = await fetch(`${API_BASE}/combos/${id}`, { method: 'DELETE' });
    if (!res.ok) throw new Error();
    showToast('Combo removido.');
    await carregarCombos();
  } catch {
    showToast('Erro ao remover combo.', 'error');
  }
};
