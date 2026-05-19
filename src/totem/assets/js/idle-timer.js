let idleTimer = null;
let idleSegundos = 60;

export function iniciarIdleTimer(segundos = 60) {
  idleSegundos = segundos;
  resetTimer();

  const eventos = ['touchstart', 'click', 'keydown', 'mousemove'];
  eventos.forEach(ev => document.addEventListener(ev, resetTimer, { passive: true }));
}

export function pararIdleTimer() {
  if (idleTimer) { clearTimeout(idleTimer); idleTimer = null; }
}

function resetTimer() {
  if (idleTimer) clearTimeout(idleTimer);
  idleTimer = setTimeout(irParaIdle, idleSegundos * 1000);
}

function irParaIdle() {
  window.location.href = '/src/totem/pages/idle.php';
}
