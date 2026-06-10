-- 008_totem_fixes.sql — correções da auditoria do totem

-- BUG-01/02: preço e quantidade dos extras
ALTER TABLE pedido_item_extras
  ADD COLUMN preco_unitario DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER nome,
  ADD COLUMN quantidade     INT           NOT NULL DEFAULT 1 AFTER preco_unitario;

-- BUG-03: senha única por dia (coluna gerada + unique key)
ALTER TABLE pedidos
  ADD COLUMN pedido_dia DATE GENERATED ALWAYS AS (DATE(horario)) STORED AFTER horario,
  ADD UNIQUE KEY uk_senha_dia (pedido_dia, senha);
