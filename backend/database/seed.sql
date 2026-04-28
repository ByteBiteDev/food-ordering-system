-- Seed data (optional)

INSERT INTO categories (name, status) VALUES
('Burgers', 1),
('Pizza', 1),
('Drinks', 1),
('Desserts', 1)
ON DUPLICATE KEY UPDATE status=VALUES(status);

-- Foods (images empty by default; upload via admin)
INSERT INTO foods (category_id, name, price, description, image, status)
SELECT c.category_id, 'Classic Beef Burger', 450.00, 'Juicy beef patty with lettuce and house sauce.', '', 1
FROM categories c WHERE c.name='Burgers'
ON DUPLICATE KEY UPDATE price=VALUES(price), description=VALUES(description), status=VALUES(status);

INSERT INTO foods (category_id, name, price, description, image, status)
SELECT c.category_id, 'Margherita Pizza', 800.00, 'Tomato, mozzarella, and basil.', '', 1
FROM categories c WHERE c.name='Pizza'
ON DUPLICATE KEY UPDATE price=VALUES(price), description=VALUES(description), status=VALUES(status);

INSERT INTO foods (category_id, name, price, description, image, status)
SELECT c.category_id, 'Fresh Mango Juice', 200.00, 'Cold-pressed mango juice.', '', 1
FROM categories c WHERE c.name='Drinks'
ON DUPLICATE KEY UPDATE price=VALUES(price), description=VALUES(description), status=VALUES(status);

INSERT INTO foods (category_id, name, price, description, image, status)
SELECT c.category_id, 'Chocolate Brownie', 250.00, 'Rich chocolate brownie with a soft center.', '', 1
FROM categories c WHERE c.name='Desserts'
ON DUPLICATE KEY UPDATE price=VALUES(price), description=VALUES(description), status=VALUES(status);

