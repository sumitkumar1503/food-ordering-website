CREATE DATABASE IF NOT EXISTS food_ordering CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE food_ordering;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS review_helpful;
DROP TABLE IF EXISTS favorites;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS coupons;
DROP TABLE IF EXISTS menu_items;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS addresses;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS settings;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    role ENUM('customer','admin','kitchen','delivery','staff') NOT NULL DEFAULT 'customer',
    avatar VARCHAR(255) DEFAULT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE addresses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    label VARCHAR(40) NOT NULL DEFAULT 'Home',
    address_line VARCHAR(255) NOT NULL,
    city VARCHAR(80) NOT NULL,
    pincode VARCHAR(10) NOT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_address_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    icon VARCHAR(50) NOT NULL DEFAULT 'bi-grid',
    image VARCHAR(500) DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    status TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE menu_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    compare_price DECIMAL(10,2) DEFAULT NULL,
    image VARCHAR(500) NOT NULL,
    is_veg TINYINT(1) NOT NULL DEFAULT 1,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_bestseller TINYINT(1) NOT NULL DEFAULT 0,
    spice_level TINYINT UNSIGNED NOT NULL DEFAULT 1,
    preparation_time INT UNSIGNED NOT NULL DEFAULT 20,
    rating DECIMAL(2,1) NOT NULL DEFAULT 4.5,
    stock INT NOT NULL DEFAULT 100,
    status TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_menu_category (category_id),
    INDEX idx_menu_flags (status, is_featured),
    CONSTRAINT fk_menu_category FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

CREATE TABLE coupons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    type ENUM('fixed','percent') NOT NULL DEFAULT 'fixed',
    value DECIMAL(10,2) NOT NULL,
    minimum_order DECIMAL(10,2) NOT NULL DEFAULT 0,
    max_discount DECIMAL(10,2) DEFAULT NULL,
    valid_until DATE NOT NULL,
    usage_limit INT NOT NULL DEFAULT 100,
    used_count INT NOT NULL DEFAULT 0,
    status TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(30) NOT NULL UNIQUE,
    user_id INT UNSIGNED DEFAULT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    delivery_address VARCHAR(300) NOT NULL,
    order_type ENUM('delivery','pickup','dine_in') NOT NULL DEFAULT 'delivery',
    table_number VARCHAR(20) DEFAULT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    discount DECIMAL(10,2) NOT NULL DEFAULT 0,
    delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    tax DECIMAL(10,2) NOT NULL DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    coupon_code VARCHAR(30) DEFAULT NULL,
    payment_method ENUM('cod','card','upi','cash') NOT NULL DEFAULT 'cod',
    payment_status ENUM('pending','paid','refunded') NOT NULL DEFAULT 'pending',
    status ENUM('pending','confirmed','preparing','ready','out_for_delivery','delivered','cancelled') NOT NULL DEFAULT 'pending',
    notes VARCHAR(500) DEFAULT NULL,
    delivery_user_id INT UNSIGNED DEFAULT NULL,
    estimated_minutes INT UNSIGNED NOT NULL DEFAULT 35,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order_status (status, created_at),
    CONSTRAINT fk_order_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_order_delivery FOREIGN KEY (delivery_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    menu_item_id INT UNSIGNED DEFAULT NULL,
    item_name VARCHAR(120) NOT NULL,
    item_price DECIMAL(10,2) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    instructions VARCHAR(255) DEFAULT NULL,
    CONSTRAINT fk_item_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_item_menu FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    menu_item_id INT UNSIGNED NOT NULL,
    order_id INT UNSIGNED DEFAULT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comment VARCHAR(500) NOT NULL,
    admin_reply VARCHAR(500) DEFAULT NULL,
    admin_replied_at TIMESTAMP NULL DEFAULT NULL,
    helpful_count INT UNSIGNED NOT NULL DEFAULT 0,
    status TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_review_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_review_menu FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    CONSTRAINT fk_review_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE favorites (
    user_id INT UNSIGNED NOT NULL,
    menu_item_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, menu_item_id),
    CONSTRAINT fk_favorite_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_favorite_menu FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE review_helpful (
    user_id INT UNSIGNED NOT NULL,
    review_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, review_id),
    CONSTRAINT fk_helpful_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_helpful_review FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(120) NOT NULL,
    message VARCHAR(300) NOT NULL,
    icon VARCHAR(50) NOT NULL DEFAULT 'bi-bell',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE activity_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    action VARCHAR(180) NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE settings (
    setting_key VARCHAR(80) PRIMARY KEY,
    setting_value TEXT NOT NULL
) ENGINE=InnoDB;

INSERT INTO settings VALUES
('restaurant_name','Cafe'),
('restaurant_phone','+91 98765 43210'),
('restaurant_address','24 Food Street, Bengaluru'),
('delivery_fee','49'),
('tax_rate','5'),
('minimum_order','149'),
('free_delivery_threshold','499'),
('restaurant_email','hello@cafe.test'),
('opening_hours','10:00 AM – 11:30 PM');

-- Password for every demo account is: password
INSERT INTO users (name,email,password,phone,role) VALUES
('Aarav Sharma','customer@demo.com','$2y$12$FvPSRdsRFGJ5CpLEYmoXjOqfKB.LZEpJN.BhV0QyhkG9xN4drQRPq','9876500010','customer'),
('Riya Admin','admin@demo.com','$2y$12$FvPSRdsRFGJ5CpLEYmoXjOqfKB.LZEpJN.BhV0QyhkG9xN4drQRPq','9876500020','admin'),
('Chef Kabir','kitchen@demo.com','$2y$12$FvPSRdsRFGJ5CpLEYmoXjOqfKB.LZEpJN.BhV0QyhkG9xN4drQRPq','9876500030','kitchen'),
('Dev Rider','delivery@demo.com','$2y$12$FvPSRdsRFGJ5CpLEYmoXjOqfKB.LZEpJN.BhV0QyhkG9xN4drQRPq','9876500040','delivery'),
('Meera Cashier','staff@demo.com','$2y$12$FvPSRdsRFGJ5CpLEYmoXjOqfKB.LZEpJN.BhV0QyhkG9xN4drQRPq','9876500050','staff');

INSERT INTO addresses (user_id,label,address_line,city,pincode,is_default) VALUES
(1,'Home','42 Lake View Road, Indiranagar','Bengaluru','560038',1);

INSERT INTO categories (name,icon,image,sort_order) VALUES
('Biryani','bi-fire','https://images.unsplash.com/photo-1563379926898-05f4575a45d8?auto=format&fit=crop&w=600&q=85',1),
('Pizza','bi-circle','https://images.unsplash.com/photo-1579751626657-72bc17010498?auto=format&fit=crop&w=600&q=85',2),
('Burgers','bi-emoji-smile','https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=600&q=85',3),
('Indian','bi-stars','https://images.unsplash.com/photo-1585937421612-70a008356fbe?auto=format&fit=crop&w=600&q=85',4),
('Desserts','bi-cake2','https://images.unsplash.com/photo-1551024506-0bccd828d307?auto=format&fit=crop&w=600&q=85',5),
('Beverages','bi-cup-straw','https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=600&q=85',6);

INSERT INTO menu_items (category_id,name,slug,description,price,compare_price,image,is_veg,is_featured,is_bestseller,spice_level,preparation_time,rating,stock) VALUES
(1,'Royal Chicken Dum Biryani','royal-chicken-dum-biryani','Long-grain basmati rice layered with slow-cooked chicken, saffron and secret spices.',329,399,'https://images.unsplash.com/photo-1563379926898-05f4575a45d8?auto=format&fit=crop&w=900&q=88',0,1,1,2,28,4.9,42),
(1,'Paneer Tikka Biryani','paneer-tikka-biryani','Smoky paneer tikka and aromatic rice with mint raita.',279,329,'https://images.unsplash.com/photo-1630409346824-4f0e7b080087?auto=format&fit=crop&w=900&q=88',1,1,0,2,25,4.7,28),
(2,'Farmhouse Loaded Pizza','farmhouse-loaded-pizza','Stone-baked crust, mozzarella, peppers, mushrooms, olives and sweet corn.',349,449,'https://images.unsplash.com/photo-1579751626657-72bc17010498?auto=format&fit=crop&w=900&q=88',1,1,1,1,20,4.8,35),
(2,'Fiery Chicken Pizza','fiery-chicken-pizza','Spicy grilled chicken, jalapeños, onions and double cheese.',399,479,'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?auto=format&fit=crop&w=900&q=88',0,0,1,3,22,4.7,31),
(3,'Smash House Burger','smash-house-burger','Double chicken patty, caramelized onions, cheddar and signature sauce.',299,349,'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=900&q=88',0,1,1,1,16,4.9,50),
(3,'Crispy Veg Supreme','crispy-veg-supreme','Crunchy vegetable patty, fresh lettuce, tomato and chilli mayo.',219,259,'https://images.unsplash.com/photo-1520072959219-c595dc870360?auto=format&fit=crop&w=900&q=88',1,0,0,1,14,4.5,45),
(4,'Butter Chicken Signature','butter-chicken-signature','Tandoori chicken simmered in a silky tomato, butter and cream gravy.',389,449,'https://images.unsplash.com/photo-1603894584373-5ac82b2ae398?auto=format&fit=crop&w=900&q=88',0,1,1,1,24,4.9,24),
(4,'Paneer Lababdar','paneer-lababdar','Cottage cheese in a rich tomato-cashew gravy, finished with cream.',319,369,'https://images.unsplash.com/photo-1631452180519-c014fe946bc7?auto=format&fit=crop&w=900&q=88',1,1,0,2,22,4.7,20),
(4,'Amritsari Chole Kulche','amritsari-chole-kulche','Tangy chickpea curry with soft, buttered kulcha and onion salad.',229,269,'https://images.unsplash.com/photo-1626132647523-66f5bf380027?auto=format&fit=crop&w=900&q=88',1,0,0,2,18,4.6,32),
(5,'Lotus Biscoff Cheesecake','lotus-biscoff-cheesecake','Creamy baked cheesecake with Biscoff spread and biscuit crumble.',249,299,'https://images.unsplash.com/photo-1578985545062-69928b1d9587?auto=format&fit=crop&w=900&q=88',1,1,1,0,8,4.9,15),
(5,'Chocolate Lava Cake','chocolate-lava-cake','Warm chocolate cake with a molten centre and vanilla cream.',189,219,'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?auto=format&fit=crop&w=900&q=88',1,0,1,0,12,4.8,18),
(6,'Mango Mint Cooler','mango-mint-cooler','Fresh mango, lime, mint and crushed ice.',149,179,'https://images.unsplash.com/photo-1546173159-315724a31696?auto=format&fit=crop&w=900&q=88',1,1,0,0,5,4.6,60);

INSERT INTO coupons (code,type,value,minimum_order,max_discount,valid_until,usage_limit) VALUES
('WELCOME100','fixed',100,399,100,'2030-12-31',500),
('FEAST20','percent',20,599,200,'2030-12-31',500),
('SWEET50','fixed',50,249,50,'2030-12-31',500);

INSERT INTO orders (order_number,user_id,customer_name,customer_phone,delivery_address,subtotal,discount,delivery_fee,tax,total,payment_method,payment_status,status,estimated_minutes,created_at) VALUES
('FF260701A1B2C3',1,'Aarav Sharma','9876500010','42 Lake View Road, Bengaluru',628,100,0,26.4,554.4,'upi','paid','delivered',35,DATE_SUB(NOW(),INTERVAL 3 DAY)),
('FF260710D4E5F6',1,'Aarav Sharma','9876500010','42 Lake View Road, Bengaluru',598,0,0,29.9,627.9,'cod','pending','preparing',28,DATE_SUB(NOW(),INTERVAL 18 MINUTE)),
('FF260710G7H8I9',NULL,'Nisha Verma','9876500099','18 MG Road, Bengaluru',389,0,49,19.45,457.45,'cod','pending','confirmed',35,DATE_SUB(NOW(),INTERVAL 9 MINUTE));

INSERT INTO order_items (order_id,menu_item_id,item_name,item_price,quantity) VALUES
(1,1,'Royal Chicken Dum Biryani',329,1),(1,5,'Smash House Burger',299,1),
(2,3,'Farmhouse Loaded Pizza',349,1),(2,10,'Lotus Biscoff Cheesecake',249,1),
(3,7,'Butter Chicken Signature',389,1);

INSERT INTO reviews (user_id,menu_item_id,order_id,rating,comment,admin_reply,admin_replied_at,helpful_count) VALUES
(1,1,1,5,'The biryani was fragrant, hot and perfectly packed. Absolutely ordering again!','Thank you, Aarav! We are delighted you loved our dum biryani.',NOW(),1),
(1,5,1,5,'Juicy burger and the signature sauce is genuinely excellent.',NULL,NULL,0);

INSERT INTO notifications (user_id,title,message,icon) VALUES
(1,'Order is being prepared','Our chefs have started preparing order FF260710D4E5F6.','bi-fire'),
(2,'New order received','A new delivery order worth ₹457 has arrived.','bi-receipt');
