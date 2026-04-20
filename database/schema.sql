CREATE DATABASE IF NOT EXISTS `mirabella_ceylon`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `mirabella_ceylon`;

-- 1. ADMIN USERS
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id`            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(100)     NOT NULL,
  `email`         VARCHAR(150)     NOT NULL UNIQUE,
  `password_hash` VARCHAR(255)     NOT NULL,
  `role`          ENUM('super_admin','admin','editor') NOT NULL DEFAULT 'admin',
  `is_active`     TINYINT(1)       NOT NULL DEFAULT 1,
  `last_login`    DATETIME                  DEFAULT NULL,
  `created_at`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default super admin  (password: Mirabella@2025)
INSERT IGNORE INTO `admin_users` (`name`, `email`, `password_hash`, `role`)
VALUES (
  'Admin',
  'admin@mirabelaceylon.com',
  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- bcrypt of "Mirabella@2025"
  'super_admin'
);


-- 2. ADMIN REMEMBER TOKENS  (for "Remember Me" login)
CREATE TABLE IF NOT EXISTS `admin_remember_tokens` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id`   INT UNSIGNED NOT NULL,
  `token_hash` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME     NOT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_token_hash` (`token_hash`),
  CONSTRAINT `fk_art_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 3. CATEGORIES
CREATE TABLE IF NOT EXISTS `categories` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100)  NOT NULL,
  `slug`        VARCHAR(110)  NOT NULL UNIQUE,
  `description` TEXT                   DEFAULT NULL,
  `image`       VARCHAR(255)           DEFAULT NULL,
  `sort_order`  SMALLINT      NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `categories` (`name`, `slug`, `sort_order`) VALUES
  ('Blue Sapphire',   'blue-sapphire',   1),
  ('Ruby',            'ruby',            2),
  ('Alexandrite',     'alexandrite',     3),
  ('Cat\'s Eye',      'cats-eye',        4),
  ('Padparadscha',    'padparadscha',    5),
  ('Spinel',          'spinel',          6),
  ('Jewellery',       'jewellery',       7),
  ('Loose Gemstones', 'loose-gemstones', 8);


-- 4. PRODUCTS
CREATE TABLE IF NOT EXISTS `products` (
  `id`              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
  `category_id`     INT UNSIGNED                 DEFAULT NULL,
  `name`            VARCHAR(200)        NOT NULL,
  `slug`            VARCHAR(220)        NOT NULL UNIQUE,
  `sku`             VARCHAR(60)                  DEFAULT NULL UNIQUE,
  `description`     TEXT                         DEFAULT NULL,
  `short_desc`      VARCHAR(500)                 DEFAULT NULL,
  -- Gemstone specifics
  `gemstone_type`   VARCHAR(100)                 DEFAULT NULL,
  `origin`          VARCHAR(100)                 DEFAULT NULL,
  `weight_ct`       DECIMAL(8,3)                 DEFAULT NULL COMMENT 'Weight in carats',
  `dimensions`      VARCHAR(100)                 DEFAULT NULL COMMENT 'e.g. 8.2 x 6.1 x 4.3 mm',
  `colour`          VARCHAR(100)                 DEFAULT NULL,
  `clarity`         VARCHAR(100)                 DEFAULT NULL,
  `cut`             VARCHAR(100)                 DEFAULT NULL,
  `treatment`       VARCHAR(200)                 DEFAULT NULL,
  `certification`   VARCHAR(200)                 DEFAULT NULL COMMENT 'e.g. GIA, AGL, Gübelin',
  -- Pricing
  `price_usd`       DECIMAL(12,2)       NOT NULL DEFAULT 0.00,
  `compare_price`   DECIMAL(12,2)                DEFAULT NULL COMMENT 'Original/crossed-out price',
  -- Inventory
  `stock`           SMALLINT UNSIGNED   NOT NULL DEFAULT 1,
  `is_featured`     TINYINT(1)          NOT NULL DEFAULT 0,
  `is_active`       TINYINT(1)          NOT NULL DEFAULT 1,
  -- Images (primary)
  `image_main`      VARCHAR(255)                 DEFAULT NULL,
  `image_hover`     VARCHAR(255)                 DEFAULT NULL,
  `created_at`      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category`  (`category_id`),
  KEY `idx_active`    (`is_active`),
  KEY `idx_featured`  (`is_featured`),
  CONSTRAINT `fk_prod_cat` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 5. PRODUCT IMAGES  (gallery / additional photos)
CREATE TABLE IF NOT EXISTS `product_images` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `alt_text`   VARCHAR(200)          DEFAULT NULL,
  `sort_order` SMALLINT     NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_pi_product` (`product_id`),
  CONSTRAINT `fk_pi_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 6. CUSTOMERS
CREATE TABLE IF NOT EXISTS `customers` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name`    VARCHAR(80)  NOT NULL,
  `last_name`     VARCHAR(80)  NOT NULL,
  `email`         VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255)          DEFAULT NULL,
  `phone`         VARCHAR(30)           DEFAULT NULL,
  `country`       VARCHAR(80)           DEFAULT NULL,
  `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
  `email_verified`TINYINT(1)   NOT NULL DEFAULT 0,
  `verify_token`  VARCHAR(100)          DEFAULT NULL,
  `reset_token`   VARCHAR(100)          DEFAULT NULL,
  `reset_expires` DATETIME              DEFAULT NULL,
  `last_login`    DATETIME              DEFAULT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cust_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 7. ORDERS
CREATE TABLE IF NOT EXISTS `orders` (
  `id`               INT UNSIGNED        NOT NULL AUTO_INCREMENT,
  `order_number`     VARCHAR(30)         NOT NULL UNIQUE COMMENT 'e.g. MC-20250001',
  `customer_id`      INT UNSIGNED                 DEFAULT NULL COMMENT 'NULL = guest checkout',
  -- Snapshot of customer details at time of order
  `customer_name`    VARCHAR(160)        NOT NULL,
  `customer_email`   VARCHAR(150)        NOT NULL,
  `customer_phone`   VARCHAR(30)                  DEFAULT NULL,
  -- Shipping address
  `ship_address`     VARCHAR(255)                 DEFAULT NULL,
  `ship_city`        VARCHAR(100)                 DEFAULT NULL,
  `ship_state`       VARCHAR(100)                 DEFAULT NULL,
  `ship_zip`         VARCHAR(20)                  DEFAULT NULL,
  `ship_country`     VARCHAR(80)                  DEFAULT NULL,
  -- Financials
  `subtotal_usd`     DECIMAL(12,2)       NOT NULL DEFAULT 0.00,
  `shipping_usd`     DECIMAL(12,2)       NOT NULL DEFAULT 0.00,
  `tax_usd`          DECIMAL(12,2)       NOT NULL DEFAULT 0.00,
  `total_usd`        DECIMAL(12,2)       NOT NULL DEFAULT 0.00,
  -- Payment
  `payment_method`   VARCHAR(50)                  DEFAULT NULL COMMENT 'stripe, paypal, bank_transfer',
  `payment_status`   ENUM('unpaid','paid','refunded','partially_refunded') NOT NULL DEFAULT 'unpaid',
  `payment_ref`      VARCHAR(200)                 DEFAULT NULL COMMENT 'Gateway transaction ID',
  -- Order lifecycle
  `status`           ENUM('pending','confirmed','processing','shipped','delivered','cancelled','refunded')
                                         NOT NULL DEFAULT 'pending',
  `notes`            TEXT                         DEFAULT NULL,
  `shipped_at`       DATETIME                     DEFAULT NULL,
  `delivered_at`     DATETIME                     DEFAULT NULL,
  `created_at`       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_status`   (`status`),
  KEY `idx_order_customer` (`customer_id`),
  CONSTRAINT `fk_order_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 8. ORDER ITEMS
CREATE TABLE IF NOT EXISTS `order_items` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `order_id`    INT UNSIGNED  NOT NULL,
  `product_id`  INT UNSIGNED           DEFAULT NULL,
  -- Snapshot of product details at time of order
  `product_name`VARCHAR(200)  NOT NULL,
  `product_sku` VARCHAR(60)            DEFAULT NULL,
  `quantity`    SMALLINT      NOT NULL DEFAULT 1,
  `unit_price`  DECIMAL(12,2) NOT NULL,
  `line_total`  DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_oi_order`   (`order_id`),
  KEY `idx_oi_product` (`product_id`),
  CONSTRAINT `fk_oi_order`   FOREIGN KEY (`order_id`)   REFERENCES `orders`(`id`)   ON DELETE CASCADE,
  CONSTRAINT `fk_oi_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 9. CART SESSIONS  (server-side cart for guests & logged-in)
CREATE TABLE IF NOT EXISTS `cart_sessions` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_key` VARCHAR(64)  NOT NULL UNIQUE COMMENT 'PHP session ID or custom token',
  `customer_id` INT UNSIGNED          DEFAULT NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_cart_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cart_items` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `cart_id`    INT UNSIGNED  NOT NULL,
  `product_id` INT UNSIGNED  NOT NULL,
  `quantity`   SMALLINT      NOT NULL DEFAULT 1,
  `added_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cart_product` (`cart_id`, `product_id`),
  CONSTRAINT `fk_ci_cart`    FOREIGN KEY (`cart_id`)    REFERENCES `cart_sessions`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ci_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 10. MESSAGES  (contact form submissions)
CREATE TABLE IF NOT EXISTS `messages` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(160) NOT NULL,
  `email`      VARCHAR(150) NOT NULL,
  `phone`      VARCHAR(30)           DEFAULT NULL,
  `subject`    VARCHAR(200)          DEFAULT NULL,
  `message`    TEXT         NOT NULL,
  `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_msg_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 11. REVIEWS
CREATE TABLE IF NOT EXISTS `reviews` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id`  INT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED          DEFAULT NULL,
  `reviewer_name` VARCHAR(120) NOT NULL,
  `rating`      TINYINT      NOT NULL DEFAULT 5 COMMENT '1–5',
  `title`       VARCHAR(200)          DEFAULT NULL,
  `body`        TEXT                  DEFAULT NULL,
  `is_approved` TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rev_product`  (`product_id`),
  KEY `idx_rev_approved` (`is_approved`),
  CONSTRAINT `fk_rev_product`  FOREIGN KEY (`product_id`)  REFERENCES `products`(`id`)   ON DELETE CASCADE,
  CONSTRAINT `fk_rev_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 12. ACTIVITY LOG  (admin actions audit trail)
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id`    INT UNSIGNED          DEFAULT NULL,
  `type`        VARCHAR(50)  NOT NULL COMMENT 'order, product, message, customer, login …',
  `description` VARCHAR(500) NOT NULL,
  `ip_address`  VARCHAR(45)           DEFAULT NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_al_admin` (`admin_id`),
  KEY `idx_al_type`  (`type`),
  CONSTRAINT `fk_al_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin_users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 13. NEWSLETTER SUBSCRIBERS
CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`       VARCHAR(150) NOT NULL UNIQUE,
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  `subscribed_at` DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

