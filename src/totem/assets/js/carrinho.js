const STORAGE_KEY = 'prontus_carrinho';

export function getCarrinho() {
  try {
    return JSON.parse(sessionStorage.getItem(STORAGE_KEY)) || [];
  } catch {
    return [];
  }
}

function setCarrinho(itens) {
  sessionStorage.setItem(STORAGE_KEY, JSON.stringify(itens));
}

export function adicionarItem(produto, extras, remocoes, quantidade) {
  const carrinho = getCarrinho();

  const item = {
    produtoId:   produto.id,
    nome:        produto.nome,
    precoBase:   produto.preco,
    extras:      extras.map(e => ({ id: e.id, nome: e.nome, preco: e.preco, quantidade: e.quantidade })),
    remocoes,
    quantidade,
    restricoes:  produto.restricoes || [],
  };

  item.subtotal = calcularSubtotal(item);
  carrinho.push(item);
  setCarrinho(carrinho);
  return carrinho;
}

export function atualizarItem(index, extras, remocoes, quantidade) {
  const carrinho = getCarrinho();
  if (!carrinho[index]) return carrinho;

  carrinho[index].extras    = extras;
  carrinho[index].remocoes  = remocoes;
  carrinho[index].quantidade = quantidade;
  carrinho[index].subtotal   = calcularSubtotal(carrinho[index]);
  setCarrinho(carrinho);
  return carrinho;
}

export function removerItem(index) {
  const carrinho = getCarrinho();
  carrinho.splice(index, 1);
  setCarrinho(carrinho);
  return carrinho;
}

export function limparCarrinho() {
  sessionStorage.removeItem(STORAGE_KEY);
}

export function calcularTotal() {
  return getCarrinho().reduce((acc, item) => acc + calcularSubtotal(item), 0);
}

export function calcularSubtotal(item) {
  const precoBase = parseFloat(item.precoBase ?? item.preco) || 0;
  const somatorioExtras = (item.extras || []).reduce(
    (acc, e) => acc + (parseFloat(e.preco) || 0) * (e.quantidade || 1),
    0
  );
  return (precoBase + somatorioExtras) * (item.quantidade || 1);
}

export function totalItens() {
  return getCarrinho().reduce((acc, item) => acc + item.quantidade, 0);
}

export function carrinhoVazio() {
  return getCarrinho().length === 0;
}
