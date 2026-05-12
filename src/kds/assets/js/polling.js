import { getPedidos } from '/src/shared/js/api.js';

let intervalId   = null;
let ultimoHash   = null;
let emAndamento  = false;

export function iniciarPolling(callback, intervalMs = 2000, onErro = null) {
  async function tick() {
    if (emAndamento) return;
    emAndamento = true;
    try {
      const pedidos = await getPedidos();
      const hash    = JSON.stringify(pedidos);

      if (hash !== ultimoHash) {
        ultimoHash = hash;
        callback(pedidos);
      }
    } catch (err) {
      console.warn('[polling] Erro ao buscar pedidos:', err.message);
      if (onErro) onErro(err);
    } finally {
      emAndamento = false;
    }
  }

  tick();
  intervalId = setInterval(tick, intervalMs);
  return intervalId;
}

export function pararPolling() {
  if (intervalId !== null) {
    clearInterval(intervalId);
    intervalId = null;
    ultimoHash = null;
    emAndamento = false;
  }
}
