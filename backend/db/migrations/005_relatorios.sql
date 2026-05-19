ALTER TABLE pedidos ADD INDEX idx_status_horario (status, horario);
ALTER TABLE pedidos ADD INDEX idx_pagamento (pagamento);
