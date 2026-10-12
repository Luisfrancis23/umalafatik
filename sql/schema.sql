-- ============================================================
-- Uma Lafatik — Database schema & seed data
-- Run once:  mysql -u root < sql/schema.sql
-- ============================================================
CREATE DATABASE IF NOT EXISTS `umalafatik`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `umalafatik`;

-- ---------------------------------------------------------- admins
CREATE TABLE `admins` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100) NOT NULL,
  `username`   VARCHAR(50)  NOT NULL UNIQUE,
  `email`      VARCHAR(120) NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,
  `role`       ENUM('super_admin','manager') NOT NULL DEFAULT 'manager',
  `last_login` DATETIME NULL,
  `status`     ENUM('active','blocked') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------- users (customers)
CREATE TABLE `users` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `first_name`       VARCHAR(60)  NOT NULL,
  `last_name`        VARCHAR(60)  NOT NULL,
  `email`            VARCHAR(120) NOT NULL UNIQUE,
  `phone`            VARCHAR(25)  NOT NULL,
  `password`         VARCHAR(255) NOT NULL,
  `avatar`           VARCHAR(255) NULL,
  `default_address`  TEXT NULL,
  `city`             VARCHAR(80)  NULL,
  `postal_code`      VARCHAR(20)  NULL,
  `status`           ENUM('active','blocked') NOT NULL DEFAULT 'active',
  `email_verified_at`DATETIME NULL,
  `last_login`       DATETIME NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------- categories
CREATE TABLE `categories` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(80)  NOT NULL,
  `slug`        VARCHAR(90)  NOT NULL UNIQUE,
  `description` VARCHAR(255) NULL,
  `image`       VARCHAR(255) NULL,
  `sort_order`  INT NOT NULL DEFAULT 0,
  `status`      ENUM('active','hidden') NOT NULL DEFAULT 'active',
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------- menu_items
CREATE TABLE `menu_items` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `category_id`  INT NOT NULL,
  `name`         VARCHAR(120) NOT NULL,
  `slug`         VARCHAR(130) NOT NULL UNIQUE,
  `description`  TEXT NULL,
  `price`        DECIMAL(10,2) NOT NULL,
  `image`        VARCHAR(255) NULL,
  `is_featured`  TINYINT(1) NOT NULL DEFAULT 0,
  `is_available` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order`   INT NOT NULL DEFAULT 0,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------- carts
CREATE TABLE `carts` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT NULL,
  `session_id` VARCHAR(128) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_cart_user` (`user_id`),
  KEY `idx_cart_session` (`session_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `cart_items` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `cart_id`     INT NOT NULL,
  `menu_item_id`INT NOT NULL,
  `quantity`    INT NOT NULL DEFAULT 1,
  `added_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_cart_item` (`cart_id`,`menu_item_id`),
  FOREIGN KEY (`cart_id`) REFERENCES `carts`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------- orders
CREATE TABLE `orders` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `order_number`    VARCHAR(20) NOT NULL UNIQUE,
  `user_id`         INT NOT NULL,
  `customer_name`   VARCHAR(120) NOT NULL,
  `email`           VARCHAR(120) NOT NULL,
  `phone`           VARCHAR(25)  NOT NULL,
  `delivery_type`   ENUM('delivery','pickup') NOT NULL DEFAULT 'delivery',
  `address`         TEXT NULL,
  `city`            VARCHAR(80) NULL,
  `postal_code`     VARCHAR(20) NULL,
  `notes`           TEXT NULL,
  `subtotal`        DECIMAL(10,2) NOT NULL,
  `delivery_fee`    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tax`             DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total`           DECIMAL(10,2) NOT NULL,
  `payment_method`  ENUM('cod','card','bank') NOT NULL DEFAULT 'cod',
  `payment_status`  ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `status`          ENUM('pending','confirmed','preparing','ready','out_for_delivery','completed','cancelled') NOT NULL DEFAULT 'pending',
  `status_updated_at` DATETIME NULL,
  `order_date`      DATETIME NOT NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `order_items` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `order_id`     INT NOT NULL,
  `menu_item_id` INT NULL,
  `item_name`    VARCHAR(120) NOT NULL,
  `quantity`     INT NOT NULL,
  `unit_price`   DECIMAL(10,2) NOT NULL,
  `total_price`  DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------- payments
CREATE TABLE `payments` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `order_id`       INT NOT NULL,
  `transaction_id` VARCHAR(100) NULL,
  `amount`         DECIMAL(10,2) NOT NULL,
  `method`         ENUM('cod','card','bank') NOT NULL DEFAULT 'cod',
  `status`         ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `paid_at`        DATETIME NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------- reservations
CREATE TABLE `reservations` (
  `id`                INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`           INT NULL,
  `name`              VARCHAR(120) NOT NULL,
  `email`             VARCHAR(120) NOT NULL,
  `phone`             VARCHAR(25)  NOT NULL,
  `guests`            INT NOT NULL,
  `reservation_date`  DATE NOT NULL,
  `reservation_time`  TIME NOT NULL,
  `seating`           VARCHAR(40) NULL,
  `special_requests`  TEXT NULL,
  `status`            ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
  `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------- reviews
CREATE TABLE `reviews` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT NULL,
  `menu_item_id`INT NULL,
  `author_name` VARCHAR(120) NULL,
  `rating`      TINYINT NOT NULL,
  `title`       VARCHAR(160) NULL,
  `comment`     TEXT NOT NULL,
  `status`      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------- notifications
CREATE TABLE `notifications` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`        INT NOT NULL,
  `order_id`       INT NULL,
  `reservation_id` INT NULL,
  `type`           VARCHAR(40) NOT NULL,
  `message`        TEXT NOT NULL,
  `is_read`        TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------- contact_messages
CREATE TABLE `contact_messages` (
  `id`        INT AUTO_INCREMENT PRIMARY KEY,
  `name`      VARCHAR(120) NOT NULL,
  `email`     VARCHAR(120) NOT NULL,
  `phone`     VARCHAR(25) NULL,
  `subject`   VARCHAR(160) NULL,
  `message`   TEXT NOT NULL,
  `is_read`   TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------- settings
CREATE TABLE `settings` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key`   VARCHAR(80) NOT NULL UNIQUE,
  `setting_value` TEXT NULL
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA
-- ============================================================

INSERT INTO `admins` (`name`, `username`, `email`, `password`, `role`, `status`) VALUES
('Amir Lafatik', 'admin', 'admin@umalafatik.com',
 '$2y$12$DxGeOfQ.4Ysx3S9uQPMOg.w8nEp5ZjLnLwoiWxDgAp5gsFowCI/pi', 'super_admin', 'active');

INSERT INTO `users` (`first_name`, `last_name`, `email`, `phone`, `password`, `default_address`, `city`, `postal_code`, `status`, `email_verified_at`) VALUES
('Sarah', 'Mansour', 'customer@demo.com', '+966 50 111 2233',
 '$2y$12$1hRec1torazNMI16B4fhbeUW4BoM.RNqRw8t8BKS30yPsMrGC940q',
 '14 Rose Street, Al Faisaliah', 'Riyadh', '11564', 'active', NOW());

INSERT INTO `categories` (`name`, `slug`, `description`, `image`, `sort_order`, `status`) VALUES
('Starters',     'starters',     'Light bites to begin your journey',  'assets/images/dishes/mezze.svg',       1, 'active'),
('Main Courses', 'main-courses', 'Signature plates, cooked to perfection', 'assets/images/dishes/kabsa.svg',   2, 'active'),
('Grill & Kebab','grill-kebab',  'Flame-kissed favourites',            'assets/images/dishes/mixed-grill.svg', 3, 'active'),
('Pasta & Rice', 'pasta-rice',   'Handmade pastas and fragrant rice',  'assets/images/dishes/risotto.svg',     4, 'active'),
('Desserts',     'desserts',     'Sweet finales',                      'assets/images/dishes/kunafa.svg',      5, 'active'),
('Beverages',    'beverages',    'Handcrafted drinks',                 'assets/images/dishes/mocktail.svg',    6, 'active');

INSERT INTO `menu_items` (`category_id`, `name`, `slug`, `description`, `price`, `image`, `is_featured`, `is_available`, `sort_order`) VALUES
(1, 'Garden Mezze',            'garden-mezze',            'A colourful spread of hummus, baba ghanoush, tabbouleh, pickled vegetables and warm flatbread.', 14.00, 'assets/images/dishes/mezze.svg',        1, 1, 1),
(1, 'Crispy Falafel',          'crispy-falafel',          'Golden herb falafel with tahini drizzle and fresh salad.',                                                        11.00, 'assets/images/dishes/falafel.svg',      0, 1, 2),
(1, 'Stuffed Vine Leaves',     'stuffed-vine-leaves',     'Tender vine leaves filled with aromatic rice, pine nuts and lemon.',                                              10.00, 'assets/images/dishes/vine-leaves.svg',  0, 1, 3),
(2, 'Royal Kabsa',             'royal-kabsa',             'Slow-cooked lamb over aromatic spiced rice with saffron, caramelised onions and toasted almonds.',                24.00, 'assets/images/dishes/kabsa.svg',        1, 1, 1),
(2, 'Mandi Chicken',           'mandi-chicken',           'Fragrant basmati rice with tender chicken, smoked with natural spices.',                                        21.00, 'assets/images/dishes/mandi.svg',        1, 1, 2),
(2, 'Grilled Sea Bass',        'grilled-sea-bass',        'Whole sea bass with garlic herb butter, lemon and seasonal greens.',                                            27.00, 'assets/images/dishes/sea-bass.svg',     0, 1, 3),
(2, 'Truffle Mushroom Risotto','truffle-risotto',         'Creamy arborio rice, wild mushrooms, white truffle and aged parmesan.',                                         22.00, 'assets/images/dishes/risotto.svg',      1, 1, 4),
(3, 'Mixed Grill Platter',     'mixed-grill-platter',     'Flame-kissed kebab, kofta and marinated chicken skewers with garlic sauce.',                                    28.00, 'assets/images/dishes/mixed-grill.svg',  1, 1, 1),
(3, 'Lamb Chops',              'lamb-chops',              'Char-grilled chops with rosemary jus and smoked mash.',                                                         29.00, 'assets/images/dishes/lamb-chops.svg',   0, 1, 2),
(3, 'Shish Tawook',            'shish-tawook',            'Marinated chicken skewers with toum and grilled tomatoes.',                                                     18.00, 'assets/images/dishes/tawook.svg',       0, 1, 3),
(4, 'Saffron Seafood Linguine','saffron-linguine',        'Handmade linguine with prawns, mussels and a saffron cream.',                                                   23.00, 'assets/images/dishes/linguine.svg',     0, 1, 1),
(4, 'Baked Fattoush Salad',    'baked-fattoush',          'Crisp greens, sumac onions, zaatar croutons and pomegranate.',                                                  12.00, 'assets/images/dishes/fattoush.svg',     0, 1, 2),
(5, 'Velvet Kunafa',           'velvet-kunafa',           'Golden shredded pastry filled with warm cheese, orange blossom syrup and pistachio.',                           12.00, 'assets/images/dishes/kunafa.svg',       1, 1, 1),
(5, 'Chocolate Lava Cake',     'chocolate-lava',          'Molten dark chocolate cake with vanilla bean ice cream.',                                                       13.00, 'assets/images/dishes/lava-cake.svg',    0, 1, 2),
(5, 'Baklava Trio',            'baklava-trio',            'Three artisanal baklava with clotted cream and honey.',                                                         10.00, 'assets/images/dishes/baklava.svg',      0, 0, 3),
(6, 'Saffron Mocktail',        'saffron-mocktail',        'Saffron, rose and citrus over crushed ice.',                                                                    9.00,  'assets/images/dishes/mocktail.svg',      1, 1, 1),
(6, 'Jasmine Mint Tea',        'jasmine-mint-tea',        'Fresh mint, jasmine and a touch of honey.',                                                                    6.00,  'assets/images/dishes/tea.svg',           0, 1, 2),
(6, 'Turkish Coffee',          'turkish-coffee',          'Finely ground, cardamom infused, served with dates.',                                                           7.00,  'assets/images/dishes/coffee.svg',        0, 1, 3);

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('delivery_fee', '5.00'),
('free_delivery_min', '50.00'),
('tax_rate', '0.08'),
('currency_symbol', '$'),
('site_status', 'open'),
('max_guests', '20'),
('opening_hours', 'Monday – Thursday: 12:00 – 23:00 | Friday – Saturday: 12:00 – 00:00 | Sunday: 13:00 – 22:00'),
('facebook_url', '#'),
('instagram_url', '#'),
('twitter_url', '#'),
('tiktok_url', '#');

INSERT INTO `reviews` (`user_id`, `author_name`, `rating`, `title`, `comment`, `status`, `created_at`) VALUES
(NULL, 'Sarah M.',       5, 'An unforgettable evening',  'The kabsa was divine and the staff made us feel like royalty. The kunafa alone is worth the visit!', 'approved', NOW() - INTERVAL 2 DAY),
(NULL, 'Ahmed K.',       5, 'Warm, elegant, exceptional','Warm, elegant, and the food is exceptional. Uma Lafatik is now our favourite spot in town.', 'approved', NOW() - INTERVAL 5 DAY),
(NULL, 'Layla & Omar',   5, 'Perfect for anniversaries', 'Perfect for our anniversary. Candlelight, soft music and plates that taste like memories.', 'approved', NOW() - INTERVAL 8 DAY),
(NULL, 'Priya R.',       4, 'Beautiful ambience',        'Beautiful room and genuinely kind service. The mezze platter is a must — we will be back soon.', 'approved', NOW() - INTERVAL 11 DAY),
(NULL, 'Daniel W.',      5, 'Outstanding lamb chops',    'The lamb chops were cooked to perfection and the truffle risotto is a masterpiece.', 'pending', NOW() - INTERVAL 1 DAY);

-- Demo reservation for the seed customer
INSERT INTO `reservations` (`user_id`, `name`, `email`, `phone`, `guests`, `reservation_date`, `reservation_time`, `seating`, `special_requests`, `status`, `created_at`) VALUES
(1, 'Sarah Mansour', 'customer@demo.com', '+966 50 111 2233', 4, DATE(NOW() + INTERVAL 3 DAY), '19:30:00', 'Indoor', 'Birthday celebration, if possible a quiet corner.', 'confirmed', NOW() - INTERVAL 1 DAY);

-- Demo orders for the seed customer
INSERT INTO `orders` (`order_number`, `user_id`, `customer_name`, `email`, `phone`, `delivery_type`, `address`, `city`, `postal_code`, `notes`, `subtotal`, `delivery_fee`, `tax`, `discount`, `total`, `payment_method`, `payment_status`, `status`, `status_updated_at`, `order_date`) VALUES
('UL-2610-A7K2M9', 1, 'Sarah Mansour', 'customer@demo.com', '+966 50 111 2233', 'delivery', '14 Rose Street, Al Faisaliah', 'Riyadh', '11564', 'Ring the bell twice, please.', 48.00, 5.00, 3.84, 0.00, 56.84, 'cod', 'pending', 'confirmed', NOW() - INTERVAL 2 HOUR, NOW() - INTERVAL 3 HOUR),
('UL-2609-X3P8Q2', 1, 'Sarah Mansour', 'customer@demo.com', '+966 50 111 2233', 'pickup', NULL, NULL, NULL, 'Extra napkins.', 35.00, 0.00, 2.80, 0.00, 37.80, 'card', 'paid', 'completed', NOW() - INTERVAL 6 DAY, NOW() - INTERVAL 6 DAY);

INSERT INTO `order_items` (`order_id`, `menu_item_id`, `item_name`, `quantity`, `unit_price`, `total_price`) VALUES
(1, 4,  'Royal Kabsa',         2, 24.00, 48.00),
(2, 1,  'Garden Mezze',        1, 14.00, 14.00),
(2, 8,  'Mixed Grill Platter', 1, 28.00, 28.00),
(2, 13, 'Velvet Kunafa',       1, 12.00, 12.00);

INSERT INTO `payments` (`order_id`, `transaction_id`, `amount`, `method`, `status`, `paid_at`) VALUES
(1, NULL, 56.84, 'cod', 'pending', NULL),
(2, 'PAY-DEMO-98234', 37.80, 'card', 'paid', NOW() - INTERVAL 6 DAY);

INSERT INTO `notifications` (`user_id`, `order_id`, `type`, `message`, `is_read`, `created_at`) VALUES
(1, 1, 'order_status', 'Your order UL-2610-A7K2M9 has been confirmed by the restaurant.', 0, NOW() - INTERVAL 2 HOUR),
(1, NULL, 'reservation', 'Your reservation for 4 guests has been confirmed.', 1, NOW() - INTERVAL 1 DAY);

CREATE INDEX `idx_orders_user` ON `orders` (`user_id`);
CREATE INDEX `idx_orders_status` ON `orders` (`status`);
CREATE INDEX `idx_orders_created` ON `orders` (`created_at`);
CREATE INDEX `idx_reservations_date` ON `reservations` (`reservation_date`);
CREATE INDEX `idx_notifications_user` ON `notifications` (`user_id`, `is_read`);
