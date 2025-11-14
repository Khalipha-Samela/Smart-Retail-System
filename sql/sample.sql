USE smartretail_db;

/* 1 Insert roles */
INSERT INTO roles (role_name, description) VALUES
('admin', 'Administrator with full system access'),
('customer', 'Regular customer'),
('staff', 'Store staff with limited admin access');

/* 2 Insert test users */
INSERT INTO users (username, email, password_hash, full_name, role_id, is_active) VALUES
(
    'admin', 
    'admin@smartretail.com', 
    '$2y$10$2SxC6X8KiwiEVLZERSm7D.yKWtYQOlfxwYosXbjLhM8pdO5WKyKIu', -- password: admin123
    'System Administrator', 
    (SELECT role_id FROM roles WHERE role_name = 'admin'), 
    TRUE
),
(
    'customer', 
    'customer@smartretail.com', 
    '$2y$10$BPO3vi9y2dnYQpuDPLX1N.ym.QO5Z5G4ZMNWPMt6PlfRvVLC5W//6', -- password: customer123
    'John Customer', 
    (SELECT role_id FROM roles WHERE role_name = 'customer'), 
    TRUE
),
(
    'staff', 
    'staff@smartretail.com', 
    '$2y$10$bCjfSr.kMbK.byVf6qMNVupv3vPlJeHLk05YP4f0/1GXzSrDJSbtC', -- password: staff123
    'Jane Staff', 
    (SELECT role_id FROM roles WHERE role_name = 'staff'), 
    TRUE
);

/* 3 Insert categories */
INSERT INTO categories (name, description) VALUES
('Books & Media', 'Books, movies, music, and educational media.'),
('Electronics', 'Smart devices, gadgets, and electronic accessories.'),
('Fashion', 'Clothing, shoes, and accessories for all styles.'),
('Home & Garden', 'Furniture, decor, and outdoor equipment.'),
('Sports & Outdoors', 'Sports gear and outdoor adventure items.');

/* 4 Insert sample products */
-- Books & Media
INSERT INTO products (name, description, price, image, category_id, stock_quantity, rating)
VALUES
('The Great Gatsby', 'Classic novel by F. Scott Fitzgerald.', 14.99, 'images/gatsby.jpg', 1, 50, 4.8),
('Noise-Cancelling Headphones Guide', 'A comprehensive guide to top headphone technologies.', 9.99, 'images/headphone_guide.jpg', 1, 30, 4.6),
('Vinyl Record – The Beatles: Abbey Road', 'Limited edition remastered vinyl record.', 29.99, 'images/abbey_road.jpg', 1, 20, 4.9);

-- Electronics
INSERT INTO products (name, description, price, image, category_id, stock_quantity, rating)
VALUES
('Smartphone X200', '6.5-inch AMOLED display, 128GB storage, 5G ready.', 699.00, 'images/smartphone_x200.jpg', 2, 40, 4.7),
('Wireless Earbuds Pro', 'Noise-cancelling Bluetooth earbuds with long battery life.', 129.99, 'images/earbuds_pro.jpg', 2, 80, 4.5),
('4K Smart TV 55"', 'Ultra HD television with streaming apps built-in.', 549.99, 'images/4k_tv.jpg', 2, 25, 4.8),
('Gaming Laptop G15', 'High-performance laptop with RTX graphics.', 1199.00, 'images/gaming_laptop_g15.jpg', 2, 15, 4.6),
('Portable Power Bank 20000mAh', 'Fast-charging USB-C power bank for all devices.', 39.99, 'images/powerbank_20k.jpg', 2, 100, 4.4);

-- Fashion
INSERT INTO products (name, description, price, image, category_id, stock_quantity, rating)
VALUES
('Men''s Denim Jacket', 'Classic blue denim jacket for casual wear.', 59.99, 'images/denim_jacket.jpg', 3, 45, 4.5),
('Women''s Leather Handbag', 'Stylish brown leather handbag with compartments.', 79.99, 'images/leather_handbag.jpg', 3, 30, 4.7);

-- Home & Garden
INSERT INTO products (name, description, price, image, category_id, stock_quantity, rating)
VALUES
('LED Table Lamp', 'Adjustable brightness lamp for study or bedroom.', 34.99, 'images/table_lamp.jpg', 4, 60, 4.6),
('Wooden Coffee Table', 'Modern minimalist coffee table made from oak wood.', 199.99, 'images/coffee_table.jpg', 4, 15, 4.8),
('Cotton Bed Sheet Set', 'Soft, breathable 4-piece bed sheet set.', 49.99, 'images/bed_sheets.jpg', 4, 70, 4.5),
('Ceramic Flower Pot', 'Elegant ceramic pot for indoor plants.', 24.99, 'images/flower_pot.jpg', 4, 40, 4.3),
('Outdoor Garden Chair', 'Weather-resistant chair for patios and gardens.', 89.99, 'images/garden_chair.jpg', 4, 25, 4.7);

-- Sports & Outdoors
INSERT INTO products (name, description, price, image, category_id, stock_quantity, rating)
VALUES
('Mountain Bike XTR', 'Durable mountain bike with 21-speed gear system.', 499.99, 'images/mountain_bike.jpg', 5, 10, 4.9),
('Yoga Mat Pro', 'Non-slip, eco-friendly yoga mat for all exercises.', 29.99, 'images/yoga_mat.jpg', 5, 60, 4.6),
('Camping Tent 4-Person', 'Waterproof and lightweight tent for outdoor adventures.', 149.99, 'images/camping_tent.jpg', 5, 20, 4.8);

/* 5 Insert sample orders - ALL THREE ORDERS */
INSERT INTO orders (user_id, total, status, created_at) VALUES
(
    (SELECT user_id FROM users WHERE username = 'customer'),
    888.98,
    'completed',
    DATE_SUB(NOW(), INTERVAL 5 DAY)
),
(
    (SELECT user_id FROM users WHERE username = 'customer'),
    314.97,
    'completed', 
    DATE_SUB(NOW(), INTERVAL 2 DAY)
),
(
    (SELECT user_id FROM users WHERE username = 'customer'),
    14.99,
    'pending',
    DATE_SUB(NOW(), INTERVAL 1 DAY)
);

/* 6 Insert sample order items - AFTER ALL ORDERS EXIST */
INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES
-- Order 1: Electronics + Fashion
(1, 4, 1, 699.00),   -- Smartphone X200
(1, 5, 1, 129.99),   -- Wireless Earbuds Pro
(1, 9, 1, 59.99),    -- Men's Denim Jacket

-- Order 2: Home & Garden
(2, 12, 1, 199.99),  -- Wooden Coffee Table
(2, 14, 1, 24.99),   -- Ceramic Flower Pot
(2, 15, 1, 89.99),   -- Outdoor Garden Chair

-- Order 3: Books
(3, 1, 1, 14.99);    -- The Great Gatsby

/* 7 Verification queries (optional) */
-- Verify orders
SELECT 'Orders verification:' AS '';
SELECT o.id, o.total, o.status, u.username 
FROM orders o 
JOIN users u ON o.user_id = u.user_id 
ORDER BY o.id;

-- Verify order items
SELECT 'Order items verification:' AS '';
SELECT oi.order_id, p.name, oi.quantity, oi.unit_price 
FROM order_items oi 
JOIN products p ON oi.product_id = p.id 
ORDER BY oi.order_id, oi.item_id;