import { getConfiguracoes } from '/src/shared/js/api.js';
import { iniciarIdleTimer } from '/src/totem/assets/js/idle-timer.js';

try {
  const config = await getConfiguracoes();
  iniciarIdleTimer(parseInt(config.totem_idle_segundos, 10) || 60);
} catch {
  iniciarIdleTimer(60);
}
