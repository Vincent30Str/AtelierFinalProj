CREATE DATABASE IF NOT EXISTS atelier CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE atelier;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'seller') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    seller_id BIGINT UNSIGNED NULL,
    name VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_seller FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    recipient_name VARCHAR(120) NOT NULL,
    shipping_address VARCHAR(1000) NOT NULL,
    courier VARCHAR(80) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    shipping_fee DECIMAL(10,2) NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    status ENUM('awaiting_payment', 'payment_confirmed', 'in_delivery') NOT NULL DEFAULT 'awaiting_payment',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    product_name VARCHAR(180) NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO products (seller_id, name, description, price, image_path)
SELECT NULL, '1995 Diamond-Quilted CC Heart Handbag', 'A rare vintage heart-shaped handbag with diamond quilting and elegant gold chain details.', 39033.00, '/images/1995%20Diamond-Quilted%20CC%20Heart%20Handbag%20(Rare%20Vintage)_%20$39,033.jpg'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = '1995 Diamond-Quilted CC Heart Handbag');
INSERT INTO products (seller_id, name, description, price, image_path)
SELECT NULL, 'Crocodile Lady Bag PM', 'A structured luxury handbag in rich crocodile-pattern leather with refined hardware.', 54000.00, '/images/Crocodile%20Lady%20Bag%20PM%20$54,000.jpg'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Crocodile Lady Bag PM');
INSERT INTO products (seller_id, name, description, price, image_path)
SELECT NULL, 'Buonasera Film Projector Minaudiere Clutch', 'A cinema-inspired minaudiere clutch with a distinctive film-projector silhouette.', 39304.00, '/images/Buonasera%20Film%20Projector%20Minaudiere%20Clutch_$39,304_.jpg'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Buonasera Film Projector Minaudiere Clutch');
INSERT INTO products (seller_id, name, description, price, image_path)
SELECT NULL, 'Gondola Lift Clutch Bag', 'A limited-edition white clutch with striking architectural details and a polished chain strap.', 51692.00, '/images/Gondola%20Lift%20Clutch%20Bag%20(2019%20Limited%20Edition)_%20$51,692_.jpg'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Gondola Lift Clutch Bag');
INSERT INTO products (seller_id, name, description, price, image_path)
SELECT NULL, 'Hermes Constance', 'An elegant shoulder bag with a clean silhouette, crafted for timeless everyday style.', 187000.00, '/images/Hermes%20Constance%20$187,000.jpg'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Hermes Constance');
INSERT INTO products (seller_id, name, description, price, image_path)
SELECT NULL, 'Louis Vuitton Millionaire Speedy', 'A vibrant collector-style Speedy bag with premium leather details.', 45000.00, '/images/Louis%20Vuitton%20Millionaire%20Speedy%20$45,000.jpeg'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Louis Vuitton Millionaire Speedy');
