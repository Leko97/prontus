-- =============================================================
-- SEED DE DADOS - Prontus App
-- Adaptado para banco existente (categorias 1-3, produtos 1-4)
-- Senhas: admin@prontus.com → admin123 | cozinha@prontus.com → cozinha123
-- =============================================================

-- -------------------------------------------------------------
-- CATEGORIAS (existentes: 1=Hamburguer, 2=Sobremesas, 3=Bebidas)
-- Novas: Lanches(4), Porções(5), Combos(6)
-- -------------------------------------------------------------
INSERT INTO categorias (nome, icone) VALUES
  ('Lanches', '🍔'),
  ('Porções', '🍟'),
  ('Combos',  '🎁');

-- -------------------------------------------------------------
-- PRODUTOS
-- Existentes: ids 1-4 (X-Caio, X-Burguer, Sorvete, Coca Zero)
-- Novos começam em id=5
-- -------------------------------------------------------------

-- Lanches (categoria_id=4)
INSERT INTO produtos (categoria_id, nome, descricao, preco) VALUES
  (4, 'X-Bacon',         'Hambúrguer 180g com bacon crocante, queijo cheddar e cebola caramelizada', 27.90),
  (4, 'X-Frango',        'Frango grelhado temperado com maionese de ervas e alface crespa',          24.90),
  (4, 'X-Salada',        'Veggie burguer de grão-de-bico com mix de folhas e molho especial',        26.90),
  (4, 'Cachorro-Quente', 'Salsicha grelhada com purê de batata, vinagrete e milho',                  14.90);

-- Bebidas (categoria_id=3, já existente)
INSERT INTO produtos (categoria_id, nome, descricao, preco) VALUES
  (3, 'Coca-Cola Lata',      'Refrigerante gelado 350ml',                              6.90),
  (3, 'Suco de Laranja',     'Suco natural espremido na hora 400ml',                   9.90),
  (3, 'Milk Shake Chocolate','Milk shake cremoso de chocolate 400ml',                 18.90),
  (3, 'Água Mineral',        'Água sem gás 500ml',                                     4.50),
  (3, 'Limonada Suíça',      'Limonada cremosa com leite condensado 400ml',           14.90);

-- Sobremesas (categoria_id=2, já existente)
INSERT INTO produtos (categoria_id, nome, descricao, preco) VALUES
  (2, 'Brownie com Sorvete', 'Brownie quentinho de chocolate com sorvete e calda',    19.90),
  (2, 'Açaí 300ml',          'Açaí batido na hora com granola e banana',              16.90),
  (2, 'Petit Gateau',        'Bolinho quente de chocolate com sorvete de baunilha',   22.90);

-- Porções (categoria_id=5)
INSERT INTO produtos (categoria_id, nome, descricao, preco) VALUES
  (5, 'Batata Frita', 'Porção crocante 300g com sal e ervas',                         18.90),
  (5, 'Onion Rings',  'Anéis de cebola empanados 200g com molho barbecue',            19.90),
  (5, 'Nuggets',      '10 unidades de frango empanado com molho mostarda-mel',        21.90);

-- Combos (categoria_id=6)
INSERT INTO produtos (categoria_id, nome, descricao, preco) VALUES
  (6, 'Combo X-Burguer', 'X-Burguer + Batata Frita + Coca-Cola Lata', 39.90),
  (6, 'Combo Frango',    'X-Frango + Batata Frita + Suco de Laranja',  42.90);

-- -------------------------------------------------------------
-- ADICIONAIS (existentes: ids 1-6; novos começam em id=7)
-- produto_id 5=X-Bacon, 6=X-Frango, 7=X-Salada, 11=Milk Shake
-- produto_id 14=Batata Frita, 15=Açaí
-- -------------------------------------------------------------
INSERT INTO adicionais (produto_id, nome, preco, ativo) VALUES
  (5,  'Queijo extra',      2.50, 1),
  (5,  'Alface extra',      1.00, 1),
  (5,  'Tomate extra',      1.00, 1),
  (6,  'Bacon',             4.00, 1),
  (6,  'Queijo',            2.50, 1),
  (7,  'Queijo vegano',     4.00, 1),
  (7,  'Avocado',           5.00, 1),
  (11, 'Chantilly extra',   3.00, 1),
  (14, 'Cheddar',           4.00, 1),
  (14, 'Bacon',             4.00, 1),
  (14, 'Catupiry',          4.00, 1),
  (15, 'Leite condensado',  2.00, 1),
  (15, 'Mel',               2.00, 1),
  (15, 'Morango',           3.00, 1);

-- -------------------------------------------------------------
-- REMOÇÕES (existentes: ids 1-7; novos começam em id=8)
-- produto_id 5=X-Bacon, 6=X-Frango, 7=X-Salada
-- produto_id 8=Cachorro-Quente, 14=Batata Frita
-- -------------------------------------------------------------
INSERT INTO remocoes (produto_id, nome) VALUES
  (5,  'Cebola caramelizada'),
  (5,  'Maionese'),
  (6,  'Alface'),
  (6,  'Maionese de ervas'),
  (7,  'Cebola'),
  (7,  'Molho especial'),
  (8,  'Purê de batata'),
  (8,  'Vinagrete'),
  (8,  'Milho'),
  (14, 'Sal'),
  (14, 'Ervas');

-- -------------------------------------------------------------
-- RESTRIÇÕES POR PRODUTO
-- restricoes já existem (ids 1-5); apenas novos vínculos abaixo
-- produto_id 7=X-Salada, 9=Coca-Cola, 10=Suco, 11=MilkShake
-- produto_id 12=Água, 13=Limonada, 14=Brownie, 15=Açaí, 17=Batata
-- -------------------------------------------------------------
INSERT INTO produto_restricoes (produto_id, restricao_slug) VALUES
  (7,  'vegetariano'),
  (7,  'vegano'),
  (9,  'sem-gluten'),
  (9,  'vegetariano'),
  (9,  'vegano'),
  (9,  'sem-lactose'),
  (10, 'sem-gluten'),
  (10, 'vegetariano'),
  (10, 'vegano'),
  (10, 'sem-lactose'),
  (11, 'vegetariano'),
  (11, 'sem-gluten'),
  (12, 'sem-gluten'),
  (12, 'vegetariano'),
  (12, 'vegano'),
  (12, 'sem-lactose'),
  (12, 'sem-amendoim'),
  (13, 'sem-gluten'),
  (13, 'vegetariano'),
  (14, 'vegetariano'),
  (15, 'sem-gluten'),
  (15, 'vegetariano'),
  (15, 'vegano'),
  (17, 'sem-gluten'),
  (17, 'vegano'),
  (17, 'vegetariano');

-- -------------------------------------------------------------
-- PEDIDOS (tabela vazia; começa em id=1)
-- -------------------------------------------------------------
INSERT INTO pedidos (senha, status, pagamento, total, horario, preparado_em) VALUES
  ('#001', 'finalizado', 'dinheiro', 39.90, '2026-05-11 10:05:00', '2026-05-11 10:22:00'),
  ('#002', 'finalizado', 'cartao',   53.70, '2026-05-11 10:18:00', '2026-05-11 10:35:00'),
  ('#003', 'finalizado', 'pix',      42.90, '2026-05-11 10:30:00', '2026-05-11 10:48:00'),
  ('#004', 'pronto',     'cartao',   32.80, '2026-05-11 11:00:00', '2026-05-11 11:17:00'),
  ('#005', 'em-preparo', 'pix',      69.70, '2026-05-11 11:15:00', NULL),
  ('#006', 'em-preparo', 'dinheiro', 27.90, '2026-05-11 11:20:00', NULL),
  ('#007', 'recebido',   'cartao',   58.70, '2026-05-11 11:28:00', NULL),
  ('#008', 'recebido',   NULL,       25.90, '2026-05-11 11:32:00', NULL);

-- -------------------------------------------------------------
-- ITENS DOS PEDIDOS
-- produto_id: 2=X-Burguer(exist), 5=X-Bacon, 7=X-Salada
-- produto_id: 9=Coca-Cola, 10=Suco, 13=Limonada, 15=Açaí
-- produto_id: 16=Petit Gateau, 17=Batata Frita
-- produto_id: 20=Combo X-Burguer, 21=Combo Frango
-- Mapa IDs: ped1→item1 | ped2→itens2,3,4 | ped3→item5
--           ped4→itens6,7 | ped5→itens8,9,10 | ped6→item11
--           ped7→itens12,13,14 | ped8→item15
-- -------------------------------------------------------------

-- Pedido 1: Combo X-Burguer
INSERT INTO pedido_itens (pedido_id, produto_id, produto_nome, preco_unitario, quantidade) VALUES
  (1, 20, 'Combo X-Burguer', 39.90, 1);

-- Pedido 2: X-Bacon + Batata Frita + Coca-Cola Lata
INSERT INTO pedido_itens (pedido_id, produto_id, produto_nome, preco_unitario, quantidade) VALUES
  (2, 5,  'X-Bacon',       27.90, 1),
  (2, 17, 'Batata Frita',  18.90, 1),
  (2, 9,  'Coca-Cola Lata', 6.90, 1);

-- Pedido 3: Combo Frango
INSERT INTO pedido_itens (pedido_id, produto_id, produto_nome, preco_unitario, quantidade) VALUES
  (3, 21, 'Combo Frango', 42.90, 1);

-- Pedido 4: X-Burguer + Suco de Laranja
INSERT INTO pedido_itens (pedido_id, produto_id, produto_nome, preco_unitario, quantidade) VALUES
  (4, 2,  'X-Burguer',      25.90, 1),
  (4, 10, 'Suco de Laranja',  9.90, 1);

-- Pedido 5: X-Bacon + Batata Frita + Petit Gateau
INSERT INTO pedido_itens (pedido_id, produto_id, produto_nome, preco_unitario, quantidade) VALUES
  (5, 5,  'X-Bacon',      27.90, 1),
  (5, 17, 'Batata Frita', 18.90, 1),
  (5, 16, 'Petit Gateau', 22.90, 1);

-- Pedido 6: X-Bacon
INSERT INTO pedido_itens (pedido_id, produto_id, produto_nome, preco_unitario, quantidade) VALUES
  (6, 5, 'X-Bacon', 27.90, 1);

-- Pedido 7: X-Salada + Limonada Suíça + Açaí 300ml
INSERT INTO pedido_itens (pedido_id, produto_id, produto_nome, preco_unitario, quantidade) VALUES
  (7, 7,  'X-Salada',       26.90, 1),
  (7, 13, 'Limonada Suíça', 14.90, 1),
  (7, 15, 'Açaí 300ml',     16.90, 1);

-- Pedido 8: X-Burguer
INSERT INTO pedido_itens (pedido_id, produto_id, produto_nome, preco_unitario, quantidade) VALUES
  (8, 2, 'X-Burguer', 25.90, 1);

-- -------------------------------------------------------------
-- EXTRAS DOS ITENS
-- item_id 2=X-Bacon(ped2) | item_id 6=X-Burguer(ped4)
-- item_id 9=Batata Frita(ped5)
-- -------------------------------------------------------------
INSERT INTO pedido_item_extras (pedido_item_id, nome) VALUES
  (2, 'Queijo extra'),
  (6, 'Bacon extra'),
  (6, 'Ovo frito'),
  (9, 'Cheddar'),
  (9, 'Bacon');

-- -------------------------------------------------------------
-- REMOÇÕES DOS ITENS
-- item_id 1=Combo X-Burguer(ped1) | item_id 5=Combo Frango(ped3)
-- item_id 15=X-Burguer(ped8)
-- -------------------------------------------------------------
INSERT INTO pedido_item_remocoes (pedido_item_id, nome) VALUES
  (1,  'Cebola'),
  (5,  'Maionese de ervas'),
  (15, 'Alface'),
  (15, 'Tomate');

-- -------------------------------------------------------------
-- RESTRIÇÕES DOS ITENS
-- item_id 12=X-Salada(ped7) | item_id 14=Açaí(ped7)
-- -------------------------------------------------------------
INSERT INTO pedido_item_restricoes (pedido_item_id, restricao_slug) VALUES
  (12, 'vegano'),
  (12, 'vegetariano'),
  (14, 'sem-gluten');

-- -------------------------------------------------------------
-- USUÁRIOS
-- admin@prontus.com já existe (id=1)
-- Inserindo apenas o usuário da cozinha
-- Senha: cozinha123
-- -------------------------------------------------------------
INSERT INTO usuarios (nome, email, senha_hash, perfil) VALUES
  ('João Cozinha', 'cozinha@prontus.com', '$2b$10$wqQFx3EReyeveiyZr3gyxOO/Ga/VkfHGt3aYs0BrfKS2Eygq6mTOG', 'cozinha');
