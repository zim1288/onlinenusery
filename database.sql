-- Online Nursery Database Setup
CREATE DATABASE IF NOT EXISTS nursery CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nursery;

-- Drop tables in reverse dependency order
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS cart;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS plants;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer','admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT
);

-- Plants table
CREATE TABLE plants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255) DEFAULT 'default.jpg',
    category_id INT,
    stock INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    total_amount DECIMAL(10,2),
    status ENUM('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Order items table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    plant_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (plant_id) REFERENCES plants(id) ON DELETE SET NULL
);

-- Reviews table
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    plant_id INT,
    rating INT CHECK(rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (plant_id) REFERENCES plants(id) ON DELETE CASCADE
);

-- Cart table
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plant_id INT NOT NULL,
    quantity INT DEFAULT 1,
    UNIQUE KEY unique_cart (user_id, plant_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plant_id) REFERENCES plants(id) ON DELETE CASCADE
);

-- Sample admin user (password: 'password')
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@nursery.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Sample categories
INSERT INTO categories (name, description) VALUES
('Indoor Plants', 'Beautiful plants perfect for indoor spaces, requiring minimal sunlight'),
('Outdoor Plants', 'Hardy plants ideal for gardens, patios, and outdoor spaces'),
('Succulents & Cacti', 'Low-maintenance drought-tolerant plants, great for beginners'),
('Flowering Plants', 'Vibrant blooming plants to brighten any space'),
('Herbs & Vegetables', 'Edible plants for your kitchen garden and cooking needs');

-- Sample plants
INSERT INTO plants (name, description, price, image, category_id, stock) VALUES
('Monstera Deliciosa', 'The iconic Swiss Cheese Plant with dramatic split leaves. Easy to care for and great for brightening up any room. Thrives in indirect light and needs watering once a week.', 24.99, 'monstera.jpg', 1, 15),
('Snake Plant', 'One of the hardiest houseplants available. Excellent air purifier that tolerates low light and infrequent watering. Perfect for beginners and busy plant lovers.', 14.99, 'snake_plant.jpg', 1, 25),
('Rose Bush', 'Classic fragrant roses in deep red. This vigorous bush produces large blooms from spring through fall. Ideal for garden beds and borders with full sun exposure.', 19.99, 'rose.jpg', 4, 10),
('Barrel Cactus', 'A striking cylindrical cactus with prominent ribs and sharp spines. Virtually indestructible, needs watering only once a month. Great conversation starter.', 12.99, 'cactus.jpg', 3, 20),
('Lavender', 'Fragrant purple flowering herb that repels insects and promotes relaxation. Drought tolerant once established. Perfect for borders, pots, and herb gardens.', 9.99, 'lavender.jpg', 5, 18),
('Fiddle Leaf Fig', 'Trendy large-leafed statement plant perfect for bright living spaces. Grows tall and adds architectural interest. Needs bright indirect light and consistent watering.', 45.99, 'fiddle_fig.jpg', 1, 8),
('Aloe Vera', 'Practical and beautiful succulent with thick gel-filled leaves. Soothing properties for skin burns and irritations. Thrives in bright light with minimal watering.', 8.99, 'aloe.jpg', 3, 30),
('Basil', 'Fresh culinary herb essential for Italian cooking. Grows quickly and produces abundant aromatic leaves. Keep on a sunny windowsill and harvest regularly for bushy growth.', 5.99, 'basil.jpg', 5, 40);
