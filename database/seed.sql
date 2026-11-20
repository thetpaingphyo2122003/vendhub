INSERT INTO users (name, username, password_hash, role) VALUES
('Avery Admin', 'admin', '$2y$10$lJZQw4cML8MeVlsUhsHFNu/wjMUSQmXdPc0ErdD9lof12zvkvh81m', 'admin'),
('Riley Customer', 'user', '$2y$10$pG7rJCpdTbpwkWDOGi3OJOkJWnt80LZrfJPkc7WcNwNyN9LgrVO0O', 'user');

INSERT INTO categories (name, slug) VALUES
('Drink', 'drink'),
('Snack', 'snack');

INSERT INTO products (name, slug, price, quantity_available, reorder_point, category) VALUES
('Coke', 'coke', 3.990, 12, 5, 'Drink'),
('Pepsi', 'pepsi', 6.885, 12, 5, 'Drink'),
('Water', 'water', 0.500, 12, 5, 'Drink'),
('7UP', '7up', 2.750, 10, 5, 'Drink'),
('Fanta', 'fanta', 2.990, 10, 5, 'Drink'),
('KitKat', 'kitkat', 1.500, 15, 5, 'Snack'),
('Lays', 'lays', 1.990, 14, 5, 'Snack'),
('Oreo', 'oreo', 1.750, 14, 5, 'Snack');
