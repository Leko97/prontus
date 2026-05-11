import { getPedidos } from '/src/shared/js/api.js';

let intervalId   = null;
let ultimoHash   = null;

/**
 * Inicia polling de pedidos.
 * Só chama `callback` quando os dados mudarem (comparação por hash).
 */
export function iniciarPolling(callback, intervalMs = 2000) {
  async function tick() {
    try {
      const pedidos = await getPedidos();
      const hash    = JSON.stringify(pedidos);

      if (hash !== ultimoHash) {
        ultimoHash = hash;
        callback(pedidos);
      }
    } catch (err) {
      console.warn('[polling] Erro ao buscar pedidos:', err.message);
    }
  }

  tick(); // primeira chamada imediata
  intervalId = setInterval(tick, intervalMs);
  return intervalId;
}

export function pararPolling() {
  if (intervalId !== null) {
    clearInterval(intervalId);
    intervalId = null;
    ultimoHash = null;
  }
}
