const USE_MOCK = false;
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

/* -------- Usuários -------- */

export async function getUsuarios() {
  return fetchAPI('/usuarios');
}

export async function getUsuarioById(id) {
  return fetchAPI(`/usuarios/${id}`);
}

export async function criarUsuario(dados) {
  return fetchAPI('/usuarios', { method: 'POST', body: JSON.stringify(dados) });
}

export async function atualizarUsuario(id, dados) {
  return fetchAPI(`/usuarios/${id}`, { method: 'PUT', body: JSON.stringify(dados) });
}

export async function deletarUsuario(id) {
  return fetchAPI(`/usuarios/${id}`, { method: 'DELETE' });
}

/* -------- Histórico de pedidos -------- */

export async function getPedidosHistorico(params = {}) {
  const qs = new URLSearchParams(params).toString();
  return fetchAPI(`/pedidos/historico${qs ? '?' + qs : ''}`);
}

/* -------- Configurações -------- */

export async function getConfiguracoes() {
  return fetchAPI('/configuracoes');
}

export async function salvarConfiguracoes(dados) {
  return fetchAPI('/configuracoes', { method: 'PUT', body: JSON.stringify(dados) });
}

/* -------- Restrições -------- */

export async function getRestricoes() {
  return fetchAPI('/restricoes');
}

export async function getRestricoesAdmin() {
  return fetchAPI('/restricoes?todas=1');
}

export async function criarRestricao(dados) {
  return fetchAPI('/restricoes', { method: 'POST', body: JSON.stringify(dados) });
}

export async function atualizarRestricao(slug, dados) {
  return fetchAPI(`/restricoes/${slug}`, { method: 'PUT', body: JSON.stringify(dados) });
}

/* -------- Remoções -------- */

export async function getRemocoes(produtoId) {
  return fetchAPI(`/remocoes?produto_id=${produtoId}`);
}

export async function criarRemocao(dados) {
  return fetchAPI('/remocoes', { method: 'POST', body: JSON.stringify(dados) });
}

export async function deletarRemocao(id) {
  return fetchAPI(`/remocoes/${id}`, { method: 'DELETE' });
}

/* -------- Upload de imagem -------- */

export async function uploadImagemProduto(produtoId, file) {
  const form = new FormData();
  form.append('imagem', file);
  const res = await fetch(`${API_BASE}/produtos/${produtoId}/imagem`, {
    method: 'POST',
    body: form,
  });
  if (!res.ok) throw new Error(`HTTP ${res.status}: upload imagem`);
  return res.json();
}

/* -------- Relatórios -------- */

export async function getRelatorio(params = {}) {
  const qs = new URLSearchParams(params).toString();
  return fetchAPI(`/relatorios${qs ? '?' + qs : ''}`);
}

export function getRelatorioCSVUrl(params = {}) {
  const qs = new URLSearchParams({ ...params, formato: 'csv' }).toString();
  return `${API_BASE}/relatorios?${qs}`;
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
