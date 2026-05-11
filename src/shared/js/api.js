const USE_MOCK = true;
const API_BASE = window.PRONTUS_API_URL || '/api';

async function fetchAPI(endpoint, options = {}) {
  const res = await fetch(`${API_BASE}${endpoint}`, {
    headers: { 'Content-Type': 'application/json', ...options.headers },
    ...options,
  });
  if (!res.ok) throw new Error(`HTTP ${res.status}: ${endpoint}`);
  return res.json();
}

async function getMock(name) {
  const res = await fetch(`/src/shared/mock/${name}.json`);
  if (!res.ok) throw new Error(`Mock não encontrado: ${name}.json`);
  return res.json();
}

/* -------- Leitura -------- */

export async function getCardapio() {
  return USE_MOCK ? getMock('cardapio') : fetchAPI('/cardapio');
}

export async function getPedidos() {
  return USE_MOCK ? getMock('pedidos') : fetchAPI('/pedidos');
}

export async function getSenhas() {
  return USE_MOCK ? getMock('senhas') : fetchAPI('/senhas');
}

export async function getMetricas() {
  return USE_MOCK ? getMock('metricas') : fetchAPI('/metricas');
}

export async function getProdutos() {
  if (USE_MOCK) {
    const data = await getMock('cardapio');
    return data.produtos;
  }
  return fetchAPI('/produtos');
}

export async function getProdutoById(id) {
  if (USE_MOCK) {
    const data = await getMock('cardapio');
    const produto = data.produtos.find(p => p.id === Number(id));
    if (!produto) throw new Error(`Produto ${id} não encontrado`);
    return produto;
  }
  return fetchAPI(`/produtos/${id}`);
}

export async function getCategorias() {
  if (USE_MOCK) {
    const data = await getMock('cardapio');
    return data.categorias;
  }
  return fetchAPI('/categorias');
}

export async function getAdicionais() {
  if (USE_MOCK) {
    const data = await getMock('cardapio');
    const extras = [];
    data.produtos.forEach(p => {
      (p.extras || []).forEach(e => {
        if (!extras.find(x => x.id === e.id)) {
          extras.push({ ...e, produtoId: p.id, produtoNome: p.nome });
        }
      });
    });
    return extras;
  }
  return fetchAPI('/adicionais');
}

/* -------- Mutações -------- */

export async function criarPedido(dados) {
  if (USE_MOCK) {
    const { generateSenha } = await import('./utils.js');
    await delay(600);
    return { id: Date.now(), senha: generateSenha(), status: 'recebido', ...dados };
  }
  return fetchAPI('/pedidos', { method: 'POST', body: JSON.stringify(dados) });
}

export async function atualizarStatusPedido(id, status) {
  if (USE_MOCK) {
    await delay(300);
    return { id, status };
  }
  return fetchAPI(`/pedidos/${id}/status`, {
    method: 'PATCH',
    body: JSON.stringify({ status }),
  });
}

export async function salvarProduto(dados) {
  if (USE_MOCK) {
    await delay(400);
    return { ...dados, id: dados.id || Date.now() };
  }
  const method = dados.id ? 'PUT' : 'POST';
  const endpoint = dados.id ? `/produtos/${dados.id}` : '/produtos';
  return fetchAPI(endpoint, { method, body: JSON.stringify(dados) });
}

export async function deletarProduto(id) {
  if (USE_MOCK) {
    await delay(300);
    return { ok: true };
  }
  return fetchAPI(`/produtos/${id}`, { method: 'DELETE' });
}

export async function salvarCategoria(dados) {
  if (USE_MOCK) {
    await delay(400);
    return { ...dados, id: dados.id || Date.now() };
  }
  const method = dados.id ? 'PUT' : 'POST';
  const endpoint = dados.id ? `/categorias/${dados.id}` : '/categorias';
  return fetchAPI(endpoint, { method, body: JSON.stringify(dados) });
}

export async function deletarCategoria(id) {
  if (USE_MOCK) {
    await delay(300);
    return { ok: true };
  }
  return fetchAPI(`/categorias/${id}`, { method: 'DELETE' });
}

export async function salvarAdicional(dados) {
  if (USE_MOCK) {
    await delay(400);
    return { ...dados, id: dados.id || Date.now() };
  }
  const method = dados.id ? 'PUT' : 'POST';
  const endpoint = dados.id ? `/adicionais/${dados.id}` : '/adicionais';
  return fetchAPI(endpoint, { method, body: JSON.stringify(dados) });
}

/* -------- Auth (mock passthrough) -------- */

export async function checkAuth() {
  if (USE_MOCK) return { logado: true, usuario: { nome: 'Admin Mock', email: 'admin@prontus.app' } };
  return fetchAPI('/auth/me');
}

export async function logout() {
  if (USE_MOCK) return { ok: true };
  return fetchAPI('/auth/logout', { method: 'POST' });
}

/* -------- Auxiliar -------- */

function delay(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}
