-- TrendShop MySQL schema and initial data
-- Target: MySQL 8+ / MariaDB 10.6+
-- Demo accounts use the password: password

CREATE DATABASE IF NOT EXISTS `system_trendshop`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `system_trendshop`;

SET NAMES utf8mb4;
SET time_zone = '+07:00';

-- Store the three locales used by the storefront and future admin tabs.
CREATE TABLE IF NOT EXISTS `languages` (
    `code` CHAR(2) NOT NULL,
    `name` VARCHAR(50) NOT NULL,
    `native_name` VARCHAR(50) NOT NULL,
    `flag_path` VARCHAR(500) NULL,
    `is_default` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`code`),
    KEY `languages_active_sort_index` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Keep customers and administrators in one Laravel-compatible user table.
CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `role` ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(255) NULL,
    `phone` VARCHAR(30) NULL,
    `password` VARCHAR(255) NULL,
    `profile_image_path` VARCHAR(500) NULL,
    `locale` CHAR(2) NOT NULL DEFAULT 'km',
    `status` ENUM('active', 'inactive', 'blocked') NOT NULL DEFAULT 'active',
    `email_verified_at` TIMESTAMP NULL,
    `last_login_at` TIMESTAMP NULL,
    `remember_token` VARCHAR(100) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_email_unique` (`email`),
    UNIQUE KEY `users_phone_unique` (`phone`),
    KEY `users_role_status_index` (`role`, `status`),
    CONSTRAINT `users_locale_foreign` FOREIGN KEY (`locale`) REFERENCES `languages` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Support Laravel password recovery and database sessions.
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
    `email` VARCHAR(255) NOT NULL,
    `token` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NULL,
    PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sessions` (
    `id` VARCHAR(255) NOT NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `payload` LONGTEXT NOT NULL,
    `last_activity` INT NOT NULL,
    PRIMARY KEY (`id`),
    KEY `sessions_user_id_index` (`user_id`),
    KEY `sessions_last_activity_index` (`last_activity`),
    CONSTRAINT `sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Keep social provider display settings separate from secrets stored in .env.
CREATE TABLE IF NOT EXISTS `social_auth_providers` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `provider` ENUM('google', 'telegram', 'facebook') NOT NULL,
    `display_name` VARCHAR(100) NOT NULL,
    `icon_path` VARCHAR(500) NULL,
    `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `social_auth_providers_provider_unique` (`provider`),
    KEY `social_auth_providers_enabled_sort_index` (`is_enabled`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_social_accounts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `provider` ENUM('google', 'telegram', 'facebook') NOT NULL,
    `provider_user_id` VARCHAR(191) NOT NULL,
    `provider_email` VARCHAR(255) NULL,
    `provider_avatar_url` VARCHAR(2048) NULL,
    `metadata` JSON NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_social_accounts_provider_user_unique` (`provider`, `provider_user_id`),
    UNIQUE KEY `user_social_accounts_user_provider_unique` (`user_id`, `provider`),
    CONSTRAINT `user_social_accounts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Separate shared category fields from Khmer, English and Chinese content.
CREATE TABLE IF NOT EXISTS `categories` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `parent_id` BIGINT UNSIGNED NULL,
    `slug` VARCHAR(191) NOT NULL,
    `image_path` VARCHAR(2048) NULL,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `categories_slug_unique` (`slug`),
    KEY `categories_parent_active_sort_index` (`parent_id`, `is_active`, `sort_order`),
    CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `category_translations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id` BIGINT UNSIGNED NOT NULL,
    `locale` CHAR(2) NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `meta_title` VARCHAR(255) NULL,
    `meta_description` VARCHAR(500) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `category_translations_category_locale_unique` (`category_id`, `locale`),
    KEY `category_translations_locale_name_index` (`locale`, `name`),
    CONSTRAINT `category_translations_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
    CONSTRAINT `category_translations_locale_foreign` FOREIGN KEY (`locale`) REFERENCES `languages` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Store operational product values once and localized text in a child table.
CREATE TABLE IF NOT EXISTS `products` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id` BIGINT UNSIGNED NULL,
    `sku` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(191) NOT NULL,
    `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `compare_at_price` DECIMAL(12,2) NULL,
    `cost_price` DECIMAL(12,2) NULL,
    `stock_quantity` INT UNSIGNED NOT NULL DEFAULT 0,
    `low_stock_threshold` INT UNSIGNED NOT NULL DEFAULT 5,
    `sold_quantity` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Cached completed-order quantity for bestseller sorting',
    `weight_grams` INT UNSIGNED NULL,
    `track_stock` TINYINT(1) NOT NULL DEFAULT 1,
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `status` ENUM('draft', 'active', 'inactive', 'out_of_stock') NOT NULL DEFAULT 'draft',
    `published_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `products_sku_unique` (`sku`),
    UNIQUE KEY `products_slug_unique` (`slug`),
    KEY `products_category_status_index` (`category_id`, `status`),
    KEY `products_status_sold_index` (`status`, `sold_quantity`),
    KEY `products_featured_status_index` (`is_featured`, `status`),
    CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_translations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `locale` CHAR(2) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `short_description` VARCHAR(500) NULL,
    `description` LONGTEXT NULL,
    `specifications` JSON NULL,
    `meta_title` VARCHAR(255) NULL,
    `meta_description` VARCHAR(500) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `product_translations_product_locale_unique` (`product_id`, `locale`),
    KEY `product_translations_locale_name_index` (`locale`, `name`),
    CONSTRAINT `product_translations_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
    CONSTRAINT `product_translations_locale_foreign` FOREIGN KEY (`locale`) REFERENCES `languages` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_images` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `image_path` VARCHAR(2048) NOT NULL,
    `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `product_images_product_sort_index` (`product_id`, `sort_order`),
    KEY `product_images_product_primary_index` (`product_id`, `is_primary`),
    CONSTRAINT `product_images_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_image_translations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_image_id` BIGINT UNSIGNED NOT NULL,
    `locale` CHAR(2) NOT NULL,
    `alt_text` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `product_image_translations_image_locale_unique` (`product_image_id`, `locale`),
    CONSTRAINT `product_image_translations_image_id_foreign` FOREIGN KEY (`product_image_id`) REFERENCES `product_images` (`id`) ON DELETE CASCADE,
    CONSTRAINT `product_image_translations_locale_foreign` FOREIGN KEY (`locale`) REFERENCES `languages` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Save reusable customer delivery addresses without tying order history to later edits.
CREATE TABLE IF NOT EXISTS `user_addresses` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `label` VARCHAR(100) NOT NULL DEFAULT 'Home',
    `recipient_name` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `address_line_1` VARCHAR(255) NOT NULL,
    `address_line_2` VARCHAR(255) NULL,
    `commune` VARCHAR(150) NULL,
    `district` VARCHAR(150) NULL,
    `city_province` VARCHAR(150) NOT NULL,
    `postal_code` VARCHAR(20) NULL,
    `country_code` CHAR(2) NOT NULL DEFAULT 'KH',
    `latitude` DECIMAL(10,7) NULL,
    `longitude` DECIMAL(10,7) NULL,
    `is_default` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `user_addresses_user_default_index` (`user_id`, `is_default`),
    CONSTRAINT `user_addresses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Support authenticated and guest carts before they become orders.
CREATE TABLE IF NOT EXISTS `carts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NULL,
    `session_token` VARCHAR(191) NULL,
    `status` ENUM('active', 'converted', 'abandoned') NOT NULL DEFAULT 'active',
    `currency` CHAR(3) NOT NULL DEFAULT 'USD',
    `expires_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `carts_user_status_index` (`user_id`, `status`),
    KEY `carts_session_status_index` (`session_token`, `status`),
    CONSTRAINT `carts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cart_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `cart_id` BIGINT UNSIGNED NOT NULL,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
    `unit_price` DECIMAL(12,2) NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `cart_items_cart_product_unique` (`cart_id`, `product_id`),
    KEY `cart_items_product_id_index` (`product_id`),
    CONSTRAINT `cart_items_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `cart_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wishlist_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `wishlist_items_user_product_unique` (`user_id`, `product_id`),
    KEY `wishlist_items_product_id_index` (`product_id`),
    CONSTRAINT `wishlist_items_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `wishlist_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Preserve immutable pricing and delivery snapshots for customer order history.
CREATE TABLE IF NOT EXISTS `orders` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_number` VARCHAR(50) NOT NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `user_address_id` BIGINT UNSIGNED NULL,
    `handled_by` BIGINT UNSIGNED NULL,
    `status` ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending',
    `payment_status` ENUM('unpaid', 'pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'unpaid',
    `currency` CHAR(3) NOT NULL DEFAULT 'USD',
    `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `discount_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `delivery_fee` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `grand_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `recipient_name` VARCHAR(150) NOT NULL,
    `recipient_phone` VARCHAR(30) NOT NULL,
    `delivery_address_line_1` VARCHAR(255) NOT NULL,
    `delivery_address_line_2` VARCHAR(255) NULL,
    `delivery_commune` VARCHAR(150) NULL,
    `delivery_district` VARCHAR(150) NULL,
    `delivery_city_province` VARCHAR(150) NOT NULL,
    `delivery_postal_code` VARCHAR(20) NULL,
    `delivery_country_code` CHAR(2) NOT NULL DEFAULT 'KH',
    `customer_note` TEXT NULL,
    `admin_note` TEXT NULL,
    `placed_at` TIMESTAMP NULL,
    `confirmed_at` TIMESTAMP NULL,
    `shipped_at` TIMESTAMP NULL,
    `delivered_at` TIMESTAMP NULL,
    `cancelled_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `orders_order_number_unique` (`order_number`),
    KEY `orders_user_created_index` (`user_id`, `created_at`),
    KEY `orders_status_created_index` (`status`, `created_at`),
    KEY `orders_payment_status_index` (`payment_status`),
    CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `orders_user_address_id_foreign` FOREIGN KEY (`user_address_id`) REFERENCES `user_addresses` (`id`) ON DELETE SET NULL,
    CONSTRAINT `orders_handled_by_foreign` FOREIGN KEY (`handled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `product_id` BIGINT UNSIGNED NULL,
    `product_sku` VARCHAR(100) NOT NULL,
    `product_name` VARCHAR(255) NOT NULL,
    `product_image_path` VARCHAR(2048) NULL,
    `unit_price` DECIMAL(12,2) NOT NULL,
    `quantity` INT UNSIGNED NOT NULL,
    `line_total` DECIMAL(12,2) NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `order_items_order_id_index` (`order_id`),
    KEY `order_items_product_id_index` (`product_id`),
    CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `provider` ENUM('cash_on_delivery', 'bank_transfer', 'card', 'other') NOT NULL DEFAULT 'cash_on_delivery',
    `transaction_reference` VARCHAR(191) NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `currency` CHAR(3) NOT NULL DEFAULT 'USD',
    `status` ENUM('pending', 'paid', 'failed', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending',
    `provider_payload` JSON NULL,
    `paid_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `payments_transaction_reference_unique` (`transaction_reference`),
    KEY `payments_order_status_index` (`order_id`, `status`),
    CONSTRAINT `payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_status_histories` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `changed_by` BIGINT UNSIGNED NULL,
    `from_status` ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded') NULL,
    `to_status` ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded') NOT NULL,
    `note` VARCHAR(500) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `order_status_histories_order_created_index` (`order_id`, `created_at`),
    CONSTRAINT `order_status_histories_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `order_status_histories_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notify one administrator or every administrator when a customer places an order.
CREATE TABLE IF NOT EXISTS `admin_notifications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `admin_user_id` BIGINT UNSIGNED NULL,
    `order_id` BIGINT UNSIGNED NULL,
    `type` VARCHAR(100) NOT NULL DEFAULT 'new_order',
    `title` VARCHAR(255) NOT NULL,
    `message` VARCHAR(500) NULL,
    `data` JSON NULL,
    `read_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `admin_notifications_admin_read_index` (`admin_user_id`, `read_at`),
    KEY `admin_notifications_order_id_index` (`order_id`),
    CONSTRAINT `admin_notifications_admin_user_id_foreign` FOREIGN KEY (`admin_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `admin_notifications_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Store delivery prices and other administrator-controlled system values.
CREATE TABLE IF NOT EXISTS `settings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_key` VARCHAR(100) NOT NULL DEFAULT 'general',
    `setting_key` VARCHAR(191) NOT NULL,
    `value_type` ENUM('string', 'integer', 'decimal', 'boolean', 'json') NOT NULL DEFAULT 'string',
    `value` LONGTEXT NULL,
    `is_translatable` TINYINT(1) NOT NULL DEFAULT 0,
    `is_public` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `settings_setting_key_unique` (`setting_key`),
    KEY `settings_group_public_index` (`group_key`, `is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `setting_translations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_id` BIGINT UNSIGNED NOT NULL,
    `locale` CHAR(2) NOT NULL,
    `value` LONGTEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `setting_translations_setting_locale_unique` (`setting_id`, `locale`),
    CONSTRAINT `setting_translations_setting_id_foreign` FOREIGN KEY (`setting_id`) REFERENCES `settings` (`id`) ON DELETE CASCADE,
    CONSTRAINT `setting_translations_locale_foreign` FOREIGN KEY (`locale`) REFERENCES `languages` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Let administrators manage footer and contact social destinations without UI changes.
CREATE TABLE IF NOT EXISTS `social_links` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `platform` VARCHAR(100) NOT NULL,
    `label` VARCHAR(100) NOT NULL,
    `url` VARCHAR(2048) NOT NULL,
    `icon_path` VARCHAR(500) NULL,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `social_links_platform_unique` (`platform`),
    KEY `social_links_active_sort_index` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Power About, Privacy and future pages with three-language dashboard tabs.
CREATE TABLE IF NOT EXISTS `content_pages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug` VARCHAR(191) NOT NULL,
    `status` ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    `updated_by` BIGINT UNSIGNED NULL,
    `published_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `content_pages_slug_unique` (`slug`),
    KEY `content_pages_status_published_index` (`status`, `published_at`),
    CONSTRAINT `content_pages_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `content_page_translations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `content_page_id` BIGINT UNSIGNED NOT NULL,
    `locale` CHAR(2) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `summary` VARCHAR(500) NULL,
    `content` LONGTEXT NULL,
    `meta_title` VARCHAR(255) NULL,
    `meta_description` VARCHAR(500) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `content_page_translations_page_locale_unique` (`content_page_id`, `locale`),
    KEY `content_page_translations_locale_title_index` (`locale`, `title`),
    CONSTRAINT `content_page_translations_page_id_foreign` FOREIGN KEY (`content_page_id`) REFERENCES `content_pages` (`id`) ON DELETE CASCADE,
    CONSTRAINT `content_page_translations_locale_foreign` FOREIGN KEY (`locale`) REFERENCES `languages` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `contact_messages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NULL,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(30) NULL,
    `subject` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('new', 'read', 'replied', 'closed') NOT NULL DEFAULT 'new',
    `replied_by` BIGINT UNSIGNED NULL,
    `replied_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `contact_messages_status_created_index` (`status`, `created_at`),
    KEY `contact_messages_user_id_index` (`user_id`),
    CONSTRAINT `contact_messages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `contact_messages_replied_by_foreign` FOREIGN KEY (`replied_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Match Laravel's database cache and queue infrastructure.
CREATE TABLE IF NOT EXISTS `cache` (
    `key` VARCHAR(255) NOT NULL,
    `value` MEDIUMTEXT NOT NULL,
    `expiration` BIGINT NOT NULL,
    PRIMARY KEY (`key`),
    KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache_locks` (
    `key` VARCHAR(255) NOT NULL,
    `owner` VARCHAR(255) NOT NULL,
    `expiration` BIGINT NOT NULL,
    PRIMARY KEY (`key`),
    KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `jobs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `queue` VARCHAR(255) NOT NULL,
    `payload` LONGTEXT NOT NULL,
    `attempts` SMALLINT UNSIGNED NOT NULL,
    `reserved_at` INT UNSIGNED NULL,
    `available_at` INT UNSIGNED NOT NULL,
    `created_at` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `job_batches` (
    `id` VARCHAR(255) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `total_jobs` INT NOT NULL,
    `pending_jobs` INT NOT NULL,
    `failed_jobs` INT NOT NULL,
    `failed_job_ids` LONGTEXT NOT NULL,
    `options` MEDIUMTEXT NULL,
    `cancelled_at` INT NULL,
    `created_at` INT NOT NULL,
    `finished_at` INT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `failed_jobs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` VARCHAR(255) NOT NULL,
    `connection` VARCHAR(255) NOT NULL,
    `queue` VARCHAR(255) NOT NULL,
    `payload` LONGTEXT NOT NULL,
    `exception` LONGTEXT NOT NULL,
    `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
    KEY `failed_jobs_connection_queue_failed_index` (`connection`, `queue`, `failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `migrations` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `migration` VARCHAR(255) NOT NULL,
    `batch` INT NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `migrations_migration_unique` (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

START TRANSACTION;

-- Seed the supported language tabs and flag assets.
INSERT IGNORE INTO `languages` (`code`, `name`, `native_name`, `flag_path`, `is_default`, `is_active`, `sort_order`) VALUES
    ('km', 'Khmer', 'ខ្មែរ', 'flag/khmer.png', 1, 1, 1),
    ('en', 'English', 'English', 'flag/us.png', 0, 1, 2),
    ('zh', 'Chinese', '中文', 'flag/china.png', 0, 1, 3);

-- Seed one administrator and the customer shown by the static profile UI.
INSERT IGNORE INTO `users` (`id`, `role`, `name`, `email`, `phone`, `password`, `profile_image_path`, `locale`, `status`, `email_verified_at`) VALUES
    (1, 'admin', 'TrendShop Admin', 'admin@trendshop.test', '+855975786200', '$2y$12$SJrCDIOkHx.OpRAiA9aeMO5x4zxoBqjYsahaLTj45mlVnlIRDbs4q', NULL, 'en', 'active', CURRENT_TIMESTAMP),
    (2, 'customer', 'TrendShop Customer', 'customer@trendshop.test', '+85512345678', '$2y$12$SJrCDIOkHx.OpRAiA9aeMO5x4zxoBqjYsahaLTj45mlVnlIRDbs4q', 'logo_web/me.jpg', 'km', 'active', CURRENT_TIMESTAMP);

-- Seed social login providers while keeping API credentials outside the database.
INSERT IGNORE INTO `social_auth_providers` (`id`, `provider`, `display_name`, `icon_path`, `is_enabled`, `sort_order`) VALUES
    (1, 'google', 'Google', 'Logo-Socail/google.png', 1, 1),
    (2, 'telegram', 'Telegram', 'Logo-Socail/telegram.png', 1, 2),
    (3, 'facebook', 'Facebook', 'Logo-Socail/communication.png', 1, 3);

-- Seed the four current storefront categories.
INSERT IGNORE INTO `categories` (`id`, `parent_id`, `slug`, `sort_order`, `is_active`) VALUES
    (1, NULL, 'electronics', 1, 1),
    (2, NULL, 'fashion', 2, 1),
    (3, NULL, 'accessories', 3, 1),
    (4, NULL, 'beauty', 4, 1);

INSERT IGNORE INTO `category_translations` (`category_id`, `locale`, `name`, `description`) VALUES
    (1, 'km', 'អេឡិចត្រូនិក', 'ឧបករណ៍អេឡិចត្រូនិកសម្រាប់ប្រើប្រាស់ប្រចាំថ្ងៃ។'),
    (1, 'en', 'Electronics', 'Useful electronics for everyday life.'),
    (1, 'zh', '电子产品', '适合日常生活的实用电子产品。'),
    (2, 'km', 'ម៉ូដ', 'សម្លៀកបំពាក់ និងសម្ភារៈម៉ូដ។'),
    (2, 'en', 'Fashion', 'Clothing and everyday fashion essentials.'),
    (2, 'zh', '时尚', '服装与日常时尚用品。'),
    (3, 'km', 'គ្រឿងបន្ថែម', 'គ្រឿងបន្ថែមសម្រាប់ការងារ និងជីវិតប្រចាំថ្ងៃ។'),
    (3, 'en', 'Accessories', 'Accessories for work and everyday life.'),
    (3, 'zh', '配件', '适合工作与日常生活的配件。'),
    (4, 'km', 'សម្រស់', 'ផលិតផលថែរក្សាសម្រស់ និងស្បែក។'),
    (4, 'en', 'Beauty', 'Beauty and skincare products.'),
    (4, 'zh', '美容护理', '美容与护肤产品。');

-- Seed current products; sold_quantity supports the bestseller banner.
INSERT IGNORE INTO `products` (`id`, `category_id`, `sku`, `slug`, `price`, `compare_at_price`, `stock_quantity`, `sold_quantity`, `is_featured`, `status`, `published_at`) VALUES
    (1, 1, 'TS-EL-HEADPHONE-001', 'studio-wireless-headphones', 89.00, 119.00, 12, 286, 1, 'active', CURRENT_TIMESTAMP),
    (2, 3, 'TS-AC-WATCH-001', 'minimal-smart-watch', 64.00, 82.00, 8, 198, 1, 'active', CURRENT_TIMESTAMP),
    (3, 2, 'TS-FA-SNEAKER-001', 'everyday-street-sneakers', 48.50, 69.00, 5, 174, 0, 'active', CURRENT_TIMESTAMP),
    (4, 1, 'TS-EL-CAMERA-001', 'compact-travel-camera', 329.00, 389.00, 3, 121, 0, 'active', CURRENT_TIMESTAMP),
    (5, 2, 'TS-FA-BACKPACK-001', 'city-carry-backpack', 39.00, 55.00, 14, 156, 0, 'active', CURRENT_TIMESTAMP),
    (6, 1, 'TS-EL-PHONE-001', 'modern-smartphone', 449.00, 499.00, 7, 109, 0, 'active', CURRENT_TIMESTAMP),
    (7, 3, 'TS-AC-KEYBOARD-001', 'mechanical-keyboard', 72.00, 95.00, 10, 145, 0, 'active', CURRENT_TIMESTAMP),
    (8, 4, 'TS-BE-SKINCARE-001', 'daily-skincare-set', 35.00, 45.00, 9, 132, 0, 'active', CURRENT_TIMESTAMP),
    (9, 1, 'TS-EL-LAPTOP-001', 'ultralight-work-laptop', 749.00, 829.00, 6, 96, 1, 'active', CURRENT_TIMESTAMP),
    (10, 1, 'TS-EL-SPEAKER-001', 'portable-bluetooth-speaker', 44.00, 59.00, 18, 184, 0, 'active', CURRENT_TIMESTAMP),
    (11, 3, 'TS-AC-MOUSE-001', 'wireless-precision-mouse', 28.00, 36.00, 24, 167, 0, 'active', CURRENT_TIMESTAMP),
    (12, 2, 'TS-FA-SHIRT-001', 'classic-cotton-shirt', 29.00, 39.00, 16, 118, 0, 'active', CURRENT_TIMESTAMP),
    (13, 2, 'TS-FA-JACKET-001', 'lightweight-city-jacket', 58.00, 75.00, 11, 87, 0, 'active', CURRENT_TIMESTAMP),
    (14, 3, 'TS-AC-BOTTLE-001', 'insulated-travel-bottle', 22.00, 29.00, 30, 213, 0, 'active', CURRENT_TIMESTAMP),
    (15, 3, 'TS-AC-CHARGER-001', 'fast-usb-c-charger', 24.50, 32.00, 22, 229, 1, 'active', CURRENT_TIMESTAMP),
    (16, 4, 'TS-BE-SERUM-001', 'hydrating-face-serum', 26.00, 34.00, 20, 151, 0, 'active', CURRENT_TIMESTAMP),
    (17, 4, 'TS-BE-PERFUME-001', 'fresh-everyday-perfume', 42.00, 55.00, 13, 103, 0, 'active', CURRENT_TIMESTAMP),
    (18, 1, 'TS-EL-MONITOR-001', 'full-hd-office-monitor', 169.00, 199.00, 8, 79, 0, 'active', CURRENT_TIMESTAMP),
    (19, 3, 'TS-AC-SUNGLASS-001', 'polarized-sunglasses', 31.00, 42.00, 17, 139, 0, 'active', CURRENT_TIMESTAMP),
    (20, 2, 'TS-FA-TOTE-001', 'canvas-everyday-tote', 19.00, 27.00, 26, 192, 0, 'active', CURRENT_TIMESTAMP);

INSERT IGNORE INTO `product_translations` (`product_id`, `locale`, `name`, `short_description`, `description`) VALUES
    (1, 'km', 'កាសឥតខ្សែ Studio', 'កាសឥតខ្សែមានផាសុកភាព និងសំឡេងច្បាស់។', 'កាសឥតខ្សែមានទ្រនាប់ទន់ សំឡេងច្បាស់ និងថាមពលថ្មប្រើបានយូរសម្រាប់ជីវិតប្រចាំថ្ងៃ។'),
    (1, 'en', 'Studio Wireless Headphones', 'Comfortable wireless headphones with clear sound.', 'Comfortable wireless headphones with soft cushions, clear sound and dependable battery life for everyday listening.'),
    (1, 'zh', 'Studio 无线耳机', '舒适且音质清晰的无线耳机。', '配备柔软耳垫、清晰音质和可靠续航，适合日常聆听。'),
    (2, 'km', 'នាឡិកាឆ្លាតវៃ Minimal', 'នាឡិកាឆ្លាតវៃសាមញ្ញសម្រាប់រាល់ថ្ងៃ។', 'តាមដានពេលវេលា សកម្មភាព និងការជូនដំណឹងក្នុងការរចនាសាមញ្ញ។'),
    (2, 'en', 'Minimal Smart Watch', 'A simple smart watch for every day.', 'Track time, activity and notifications in a clean everyday design.'),
    (2, 'zh', '简约智能手表', '适合日常佩戴的简约智能手表。', '以简洁设计查看时间、活动与通知。'),
    (3, 'km', 'ស្បែកជើងប្រើប្រាស់រាល់ថ្ងៃ', 'ស្បែកជើងស្រាល និងងាយស្រួលពាក់។', 'ស្បែកជើងសម្រាប់ដើរ និងប្រើប្រាស់ប្រចាំថ្ងៃដោយផាសុកភាព។'),
    (3, 'en', 'Everyday Street Sneakers', 'Lightweight and comfortable street sneakers.', 'Comfortable sneakers designed for walking and daily use.'),
    (3, 'zh', '日常休闲鞋', '轻便舒适的休闲鞋。', '专为步行与日常穿着设计的舒适运动鞋。'),
    (4, 'km', 'កាមេរ៉ាសម្រាប់ដំណើរ', 'កាមេរ៉ាតូចងាយស្រួលយកតាមខ្លួន។', 'ថតរូបភាព និងវីដេអូក្នុងដំណើររបស់អ្នកបានងាយស្រួល។'),
    (4, 'en', 'Compact Travel Camera', 'A compact camera made for travel.', 'Capture photos and videos easily wherever your trip takes you.'),
    (4, 'zh', '便携旅行相机', '适合旅行携带的紧凑相机。', '随时轻松记录旅途中的照片与视频。'),
    (5, 'km', 'កាបូបស្ពាយ City Carry', 'កាបូបស្ពាយសម្រាប់ការងារ និងដំណើរ។', 'កាបូបស្ពាយមានផ្នែកទុកដាក់ងាយស្រួលសម្រាប់ប្រើប្រាស់ប្រចាំថ្ងៃ។'),
    (5, 'en', 'City Carry Backpack', 'A practical backpack for work and travel.', 'An organized everyday backpack with practical storage.'),
    (5, 'zh', '城市通勤背包', '适合工作与旅行的实用背包。', '拥有实用收纳空间的日常通勤背包。'),
    (6, 'km', 'ទូរស័ព្ទទំនើប', 'ទូរស័ព្ទទំនើបសម្រាប់ការងារ និងកម្សាន្ត។', 'អេក្រង់ច្បាស់ កាមេរ៉ាល្អ និងថាមពលថ្មសម្រាប់ប្រើបានពេញមួយថ្ងៃ។'),
    (6, 'en', 'Modern Smartphone', 'A modern phone for work and entertainment.', 'A clear display, capable camera and all-day battery for daily use.'),
    (6, 'zh', '现代智能手机', '适合工作与娱乐的现代手机。', '清晰屏幕、实用相机与全天候电池续航。'),
    (7, 'km', 'ក្តារចុច Mechanical', 'ក្តារចុចមានអារម្មណ៍វាយល្អ។', 'ក្តារចុច Mechanical សម្រាប់ការងារ និងលេងហ្គេមប្រចាំថ្ងៃ។'),
    (7, 'en', 'Mechanical Keyboard', 'A responsive keyboard with a satisfying feel.', 'A mechanical keyboard suited to everyday work and gaming.'),
    (7, 'zh', '机械键盘', '手感清晰灵敏的机械键盘。', '适合日常办公与游戏使用的机械键盘。'),
    (8, 'km', 'ឈុតថែរក្សាស្បែកប្រចាំថ្ងៃ', 'ឈុតថែរក្សាស្បែកសម្រាប់ប្រើជាប្រចាំ។', 'ផលិតផលសំខាន់ៗសម្រាប់ការថែរក្សាស្បែកពេលព្រឹក និងពេលល្ងាច។'),
    (8, 'en', 'Daily Skincare Set', 'A simple set for an everyday skincare routine.', 'Daily essentials for a straightforward morning and evening skincare routine.'),
    (8, 'zh', '日常护肤套装', '适合每日使用的简约护肤套装。', '满足早晚基础护肤需求的日常用品。'),
    (9, 'km', 'កុំព្យូទ័រយួរដៃស្រាល', 'Laptop ស្រាលសម្រាប់ការងារ និងសិក្សា។', 'អេក្រង់ច្បាស់ ថ្មប្រើបានយូរ និងងាយស្រួលយកតាមខ្លួន។'),
    (9, 'en', 'Ultralight Work Laptop', 'A light laptop for work and study.', 'Clear display, dependable battery and portable design for productive days.'),
    (9, 'zh', '轻薄办公笔记本', '适合办公和学习的轻薄笔记本。', '清晰屏幕、可靠续航与便携设计。'),
    (10, 'km', 'ឧបករណ៍បំពងសំឡេង Bluetooth', 'ឧបករណ៍បំពងសំឡេងតូច ងាយយកតាមខ្លួន។', 'សំឡេងច្បាស់ និងថ្មប្រើបានយូរសម្រាប់ក្នុងផ្ទះ ឬដំណើរ។'),
    (10, 'en', 'Portable Bluetooth Speaker', 'Compact wireless sound wherever you go.', 'Clear sound and long battery life for home, picnics and travel.'),
    (10, 'zh', '便携蓝牙音箱', '随身携带的紧凑无线音箱。', '清晰音质与持久续航，适合居家和旅行。'),
    (11, 'km', 'Mouse ឥតខ្សែ', 'Mouse មានភាពជាក់លាក់សម្រាប់ការងារ។', 'ការតាមដានរលូន និងការរចនាផាសុកភាពសម្រាប់ប្រើរាល់ថ្ងៃ។'),
    (11, 'en', 'Wireless Precision Mouse', 'Smooth, precise control for everyday work.', 'Comfortable wireless mouse with responsive tracking and quiet clicks.'),
    (11, 'zh', '无线精准鼠标', '日常办公的流畅精准控制。', '舒适设计、灵敏追踪与安静按键。'),
    (12, 'km', 'អាវ Cotton បែប Classic', 'អាវទន់ និងងាយស្រួលពាក់ប្រចាំថ្ងៃ។', 'ក្រណាត់ Cotton មានខ្យល់ចេញចូល និងរចនាបែបសាមញ្ញ។'),
    (12, 'en', 'Classic Cotton Shirt', 'A breathable shirt for everyday comfort.', 'Soft cotton fabric and a timeless cut for work or weekends.'),
    (12, 'zh', '经典棉质衬衫', '舒适透气的日常衬衫。', '柔软棉料与经典剪裁，适合通勤和周末。'),
    (13, 'km', 'អាវក្រៅស្រាល City', 'អាវក្រៅស្រាលសម្រាប់អាកាសធាតុប្រែប្រួល។', 'ការរចនាស្អាត មានហោប៉ៅ និងងាយស្រួលផ្គូផ្គង។'),
    (13, 'en', 'Lightweight City Jacket', 'A versatile layer for changing weather.', 'Clean styling, practical pockets and lightweight everyday comfort.'),
    (13, 'zh', '轻便城市夹克', '适合多变天气的轻便外套。', '简洁造型、实用口袋与轻盈舒适体验。'),
    (14, 'km', 'ដបទឹករក្សាកម្ដៅ', 'រក្សាភេសជ្ជៈត្រជាក់ ឬក្តៅបានយូរ។', 'ដបទឹកដែកអ៊ីណុកដែលមិនជ្រាបទឹកសម្រាប់ការងារ និងដំណើរ។'),
    (14, 'en', 'Insulated Travel Bottle', 'Keeps drinks cold or hot for hours.', 'Leak-resistant stainless bottle made for commutes and travel.'),
    (14, 'zh', '保温旅行水杯', '长时间保持饮品冷热。', '防漏不锈钢水杯，适合通勤与旅行。'),
    (15, 'km', 'ឆ្នាំងសាក USB-C ល្បឿនលឿន', 'ឆ្នាំងសាកតូច និងមានប្រសិទ្ធភាពខ្ពស់។', 'សាកទូរស័ព្ទ Tablet និងឧបករណ៍ USB-C បានលឿន។'),
    (15, 'en', 'Fast USB-C Charger', 'Compact, efficient fast charging.', 'Power phones, tablets and compatible USB-C devices quickly.'),
    (15, 'zh', 'USB-C 快速充电器', '小巧高效的快速充电器。', '快速为手机、平板与 USB-C 设备充电。'),
    (16, 'km', 'Serum ផ្តល់សំណើម', 'Serum ស្រាលសម្រាប់ស្បែកមានសំណើម។', 'រូបមន្តស្រាលស្រូបចូលលឿនសម្រាប់ការថែរក្សាស្បែកប្រចាំថ្ងៃ។'),
    (16, 'en', 'Hydrating Face Serum', 'Light daily moisture for comfortable skin.', 'A fast-absorbing serum designed for a simple daily routine.'),
    (16, 'zh', '保湿面部精华', '轻盈日常保湿，令肌肤舒适。', '快速吸收配方，适合简单日常护肤。'),
    (17, 'km', 'ទឹកអប់ Fresh Everyday', 'ក្លិនស្រស់ស្រាយសម្រាប់ប្រើរាល់ថ្ងៃ។', 'ក្លិនស្រាល ស្អាត និងងាយស្រួលប្រើសម្រាប់ការងារ ឬដំណើរ។'),
    (17, 'en', 'Fresh Everyday Perfume', 'A clean, easy fragrance for every day.', 'A balanced fresh scent suited to work, weekends and travel.'),
    (17, 'zh', '清新日常香水', '适合每天使用的清新香气。', '清爽均衡，适合办公、周末与旅行。'),
    (18, 'km', 'Monitor Full HD សម្រាប់ការងារ', 'អេក្រង់ច្បាស់សម្រាប់ការងារ និងកម្សាន្ត។', 'Monitor Full HD មានពណ៌ច្បាស់ និងរចនាស្តើងសម្រាប់តុការងារ។'),
    (18, 'en', 'Full HD Office Monitor', 'A clear display for work and entertainment.', 'Slim Full HD monitor with vivid color for a productive desk.'),
    (18, 'zh', '全高清办公显示器', '适合办公与娱乐的清晰显示器。', '纤薄全高清屏幕，色彩清晰，提升桌面效率。'),
    (19, 'km', 'វ៉ែនតាការពារពន្លឺ Polarized', 'វ៉ែនតាស្រាលកាត់បន្ថយពន្លឺចាំង។', 'កញ្ចក់ Polarized និងស៊ុមមានផាសុកភាពសម្រាប់ប្រើក្រៅផ្ទះ។'),
    (19, 'en', 'Polarized Sunglasses', 'Lightweight shades that reduce glare.', 'Polarized lenses and a comfortable frame for everyday outdoor use.'),
    (19, 'zh', '偏光太阳镜', '轻盈镜框，有效减少眩光。', '偏光镜片与舒适镜框，适合日常户外使用。'),
    (20, 'km', 'កាបូប Canvas ប្រចាំថ្ងៃ', 'កាបូបធំទូលាយសម្រាប់ទិញទំនិញ និងការងារ។', 'កាបូប Canvas រឹងមាំ ជាមួយទំហំងាយស្រួលប្រើរាល់ថ្ងៃ។'),
    (20, 'en', 'Canvas Everyday Tote', 'A roomy carryall for errands and work.', 'Durable canvas construction with practical everyday capacity.'),
    (20, 'zh', '帆布日常托特包', '适合通勤与购物的大容量包。', '耐用帆布材质与实用日常容量。');

-- Seed primary and supporting product images.
INSERT IGNORE INTO `product_images` (`id`, `product_id`, `image_path`, `is_primary`, `sort_order`) VALUES
    (1, 1, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=1200&q=88', 1, 1),
    (2, 1, 'https://images.unsplash.com/photo-1484704849700-f032a568e944?auto=format&fit=crop&w=1200&q=88', 0, 2),
    (3, 1, 'https://images.unsplash.com/photo-1546435770-a3e426bf472b?auto=format&fit=crop&w=1200&q=88', 0, 3),
    (4, 1, 'https://images.unsplash.com/photo-1583394838336-acd977736f90?auto=format&fit=crop&w=1200&q=88', 0, 4),
    (5, 2, 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=900&q=85', 1, 1),
    (6, 3, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=900&q=85', 1, 1),
    (7, 4, 'https://images.unsplash.com/photo-1502920917128-1aa500764cbd?auto=format&fit=crop&w=900&q=85', 1, 1),
    (8, 5, 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=900&q=85', 1, 1),
    (9, 6, 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=900&q=85', 1, 1),
    (10, 7, 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=900&q=85', 1, 1),
    (11, 8, 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?auto=format&fit=crop&w=900&q=85', 1, 1),
    (12, 9, 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=900&q=85', 1, 1),
    (13, 10, 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?auto=format&fit=crop&w=900&q=85', 1, 1),
    (14, 11, 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?auto=format&fit=crop&w=900&q=85', 1, 1),
    (15, 12, 'https://images.unsplash.com/photo-1603252109303-2751441dd157?auto=format&fit=crop&w=900&q=85', 1, 1),
    (16, 13, 'https://images.unsplash.com/photo-1551028719-00167b16eac5?auto=format&fit=crop&w=900&q=85', 1, 1),
    (17, 14, 'https://images.unsplash.com/photo-1602143407151-7111542de6e8?auto=format&fit=crop&w=900&q=85', 1, 1),
    (18, 15, 'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?auto=format&fit=crop&w=900&q=85', 1, 1),
    (19, 16, 'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?auto=format&fit=crop&w=900&q=85', 1, 1),
    (20, 17, 'https://images.unsplash.com/photo-1541643600914-78b084683601?auto=format&fit=crop&w=900&q=85', 1, 1),
    (21, 18, 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?auto=format&fit=crop&w=900&q=85', 1, 1),
    (22, 19, 'https://images.unsplash.com/photo-1511499767150-a48a237f0083?auto=format&fit=crop&w=900&q=85', 1, 1),
    (23, 20, 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=900&q=85', 1, 1);

INSERT IGNORE INTO `product_image_translations` (`product_image_id`, `locale`, `alt_text`) VALUES
    (1, 'km', 'កាសឥតខ្សែ Studio ផ្ទៃពណ៌លឿង'), (1, 'en', 'Studio wireless headphones on yellow background'), (1, 'zh', '黄色背景上的 Studio 无线耳机'),
    (2, 'km', 'ទិដ្ឋភាពចំហៀងនៃកាស Studio'), (2, 'en', 'Side view of Studio headphones'), (2, 'zh', 'Studio 耳机侧面图'),
    (3, 'km', 'កាស Studio នៅលើតុ'), (3, 'en', 'Studio headphones on a desk'), (3, 'zh', '桌面上的 Studio 耳机'),
    (4, 'km', 'រូបពេញនៃកាស Studio'), (4, 'en', 'Full view of Studio headphones'), (4, 'zh', 'Studio 耳机完整视图');

-- Seed the customer address, cart and wishlist used by the profile flow.
INSERT IGNORE INTO `user_addresses` (`id`, `user_id`, `label`, `recipient_name`, `phone`, `address_line_1`, `commune`, `district`, `city_province`, `country_code`, `is_default`) VALUES
    (1, 2, 'Home', 'TrendShop Customer', '+85512345678', '#24, Street 271', 'Boeung Tumpun', 'Mean Chey', 'Phnom Penh', 'KH', 1);

INSERT IGNORE INTO `carts` (`id`, `user_id`, `status`, `currency`) VALUES
    (1, 2, 'active', 'USD');

INSERT IGNORE INTO `cart_items` (`cart_id`, `product_id`, `quantity`, `unit_price`) VALUES
    (1, 3, 1, 48.50);

INSERT IGNORE INTO `wishlist_items` (`user_id`, `product_id`) VALUES
    (2, 2);

-- Seed representative order history and one unread administrator notification.
INSERT IGNORE INTO `orders` (`id`, `order_number`, `user_id`, `user_address_id`, `handled_by`, `status`, `payment_status`, `currency`, `subtotal`, `discount_total`, `delivery_fee`, `grand_total`, `recipient_name`, `recipient_phone`, `delivery_address_line_1`, `delivery_commune`, `delivery_district`, `delivery_city_province`, `delivery_country_code`, `placed_at`, `confirmed_at`, `shipped_at`, `delivered_at`) VALUES
    (1, 'TS-2026-0018', 2, 1, 1, 'delivered', 'paid', 'USD', 89.00, 0.00, 2.00, 91.00, 'TrendShop Customer', '+85512345678', '#24, Street 271', 'Boeung Tumpun', 'Mean Chey', 'Phnom Penh', 'KH', '2026-09-12 09:00:00', '2026-09-12 09:30:00', '2026-09-12 14:00:00', '2026-09-13 11:00:00'),
    (2, 'TS-2026-0012', 2, 1, NULL, 'processing', 'pending', 'USD', 64.00, 0.00, 2.00, 66.00, 'TrendShop Customer', '+85512345678', '#24, Street 271', 'Boeung Tumpun', 'Mean Chey', 'Phnom Penh', 'KH', '2026-09-10 10:00:00', '2026-09-10 10:20:00', NULL, NULL);

INSERT IGNORE INTO `order_items` (`id`, `order_id`, `product_id`, `product_sku`, `product_name`, `product_image_path`, `unit_price`, `quantity`, `line_total`) VALUES
    (1, 1, 1, 'TS-EL-HEADPHONE-001', 'Studio Wireless Headphones', 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=1200&q=88', 89.00, 1, 89.00),
    (2, 2, 2, 'TS-AC-WATCH-001', 'Minimal Smart Watch', 'https://images.unsplash.com/photo-1523275335684-37898b6baf30e?auto=format&fit=crop&w=900&q=85', 64.00, 1, 64.00);

INSERT IGNORE INTO `payments` (`id`, `order_id`, `provider`, `amount`, `currency`, `status`, `paid_at`) VALUES
    (1, 1, 'cash_on_delivery', 91.00, 'USD', 'paid', '2026-09-13 11:00:00'),
    (2, 2, 'cash_on_delivery', 66.00, 'USD', 'pending', NULL);

INSERT IGNORE INTO `order_status_histories` (`id`, `order_id`, `changed_by`, `from_status`, `to_status`, `note`, `created_at`) VALUES
    (1, 1, 2, NULL, 'pending', 'Order placed by customer.', '2026-09-12 09:00:00'),
    (2, 1, 1, 'pending', 'confirmed', 'Order confirmed by administrator.', '2026-09-12 09:30:00'),
    (3, 1, 1, 'confirmed', 'shipped', 'Order sent for delivery.', '2026-09-12 14:00:00'),
    (4, 1, 1, 'shipped', 'delivered', 'Order delivered successfully.', '2026-09-13 11:00:00'),
    (5, 2, 2, NULL, 'pending', 'Order placed by customer.', '2026-09-10 10:00:00'),
    (6, 2, 1, 'pending', 'processing', 'Order is being prepared.', '2026-09-10 10:20:00');

INSERT IGNORE INTO `admin_notifications` (`id`, `admin_user_id`, `order_id`, `type`, `title`, `message`, `data`, `read_at`) VALUES
    (1, 1, 2, 'new_order', 'New order received', 'Customer placed order TS-2026-0012.', '{"order_number":"TS-2026-0012","grand_total":"66.00"}', NULL);

-- Seed editable delivery, contact and storefront settings.
INSERT IGNORE INTO `settings` (`id`, `group_key`, `setting_key`, `value_type`, `value`, `is_translatable`, `is_public`) VALUES
    (1, 'store', 'store_name', 'string', 'TrendShop', 0, 1),
    (2, 'store', 'default_currency', 'string', 'USD', 0, 1),
    (3, 'delivery', 'delivery_fee', 'decimal', '2.00', 0, 1),
    (4, 'delivery', 'free_delivery_minimum', 'decimal', '100.00', 0, 1),
    (5, 'order', 'order_number_prefix', 'string', 'TS', 0, 0),
    (6, 'inventory', 'default_low_stock_threshold', 'integer', '5', 0, 0),
    (7, 'contact', 'support_email', 'string', 'support@trendshop.test', 0, 1),
    (8, 'contact', 'support_phone', 'string', '+855 97 578 6200', 0, 1),
    (9, 'store', 'store_tagline', 'string', NULL, 1, 1),
    (10, 'footer', 'footer_summary', 'string', NULL, 1, 1),
    (11, 'contact', 'store_location', 'string', NULL, 1, 1);

INSERT IGNORE INTO `setting_translations` (`setting_id`, `locale`, `value`) VALUES
    (9, 'km', 'ការទិញទំនិញសាមញ្ញ សម្រាប់ជីវិតកាន់តែប្រសើរ។'),
    (9, 'en', 'Simple shopping, better living.'),
    (9, 'zh', '简单购物，美好生活。'),
    (10, 'km', 'ហាងអនឡាញសម្រាប់ផលិតផលប្រើប្រាស់ប្រចាំថ្ងៃ ដែលរៀបចំឡើងឱ្យការទិញមានភាពងាយស្រួល និងទុកចិត្តបាន។'),
    (10, 'en', 'A friendly online store for useful everyday products, designed for simple and confident shopping.'),
    (10, 'zh', '一家友好的日常用品在线商店，为您带来简单、安心的购物体验。'),
    (11, 'km', 'ភ្នំពេញ ប្រទេសកម្ពុជា'),
    (11, 'en', 'Phnom Penh, Cambodia'),
    (11, 'zh', '柬埔寨金边');

-- Seed administrator-managed social media destinations.
INSERT IGNORE INTO `social_links` (`id`, `platform`, `label`, `url`, `icon_path`, `sort_order`, `is_active`) VALUES
    (1, 'facebook', 'Facebook', 'https://www.facebook.com/', NULL, 1, 1),
    (2, 'youtube', 'YouTube', 'https://www.youtube.com/', NULL, 2, 1),
    (3, 'telegram', 'Telegram', 'https://t.me/', 'Logo-Socail/telegram.png', 3, 1),
    (4, 'tiktok', 'TikTok', 'https://www.tiktok.com/', NULL, 4, 1),
    (5, 'x', 'X', 'https://x.com/', NULL, 5, 1);

-- Seed About, Privacy and Contact content for three-language admin tabs.
INSERT IGNORE INTO `content_pages` (`id`, `slug`, `status`, `updated_by`, `published_at`) VALUES
    (1, 'about-us', 'published', 1, CURRENT_TIMESTAMP),
    (2, 'privacy-policy', 'published', 1, CURRENT_TIMESTAMP),
    (3, 'contact-us', 'published', 1, CURRENT_TIMESTAMP);

INSERT IGNORE INTO `content_page_translations` (`content_page_id`, `locale`, `title`, `summary`, `content`, `meta_title`, `meta_description`) VALUES
    (1, 'km', 'អំពី TrendShop', 'ផលិតផលប្រចាំថ្ងៃ ដែលបានជ្រើសរើសយ៉ាងយកចិត្តទុកដាក់។', 'TrendShop ជាហាងអនឡាញសាមញ្ញ ដែលជួយឱ្យការស្វែងរក និងទិញផលិតផលមានប្រយោជន៍កាន់តែងាយស្រួល។', 'អំពី TrendShop', 'ស្វែងយល់អំពីហាងអនឡាញ TrendShop។'),
    (1, 'en', 'About TrendShop', 'Everyday products, chosen with care.', 'TrendShop is a simple online store created to make useful products easier to discover and buy.', 'About TrendShop', 'Learn about the TrendShop online store.'),
    (1, 'zh', '关于 TrendShop', '用心挑选日常好物。', 'TrendShop 是一家简洁的在线商店，让实用商品更容易被发现和购买。', '关于 TrendShop', '了解 TrendShop 在线商店。'),
    (2, 'km', 'គោលការណ៍ឯកជនភាព', 'យើងគោរព និងការពារព័ត៌មានផ្ទាល់ខ្លួនរបស់អ្នក។', 'យើងប្រមូលតែព័ត៌មានចាំបាច់សម្រាប់គ្រប់គ្រងគណនី អាសយដ្ឋានដឹកជញ្ជូន និងការបញ្ជាទិញ ហើយមិនលក់ព័ត៌មានផ្ទាល់ខ្លួនរបស់អ្នកឡើយ។', 'គោលការណ៍ឯកជនភាព', 'របៀបដែល TrendShop ប្រមូល ប្រើ និងការពារព័ត៌មាន។'),
    (2, 'en', 'Privacy Policy', 'We respect and protect your personal information.', 'We collect only the information needed to manage accounts, delivery addresses and orders, and we do not sell your personal information.', 'Privacy Policy', 'How TrendShop collects, uses and protects information.'),
    (2, 'zh', '隐私政策', '我们尊重并保护您的个人信息。', '我们仅收集管理账号、收货地址和订单所需的信息，并且不会出售您的个人信息。', '隐私政策', 'TrendShop 如何收集、使用和保护信息。'),
    (3, 'km', 'ទំនាក់ទំនងយើង', 'ទាក់ទងក្រុមការងារ TrendShop។', 'សូមផ្ញើសារ អ៊ីមែល ឬទូរស័ព្ទមកយើង ប្រសិនបើអ្នកត្រូវការជំនួយ។', 'ទំនាក់ទំនង TrendShop', 'ទាក់ទងក្រុមជំនួយ TrendShop។'),
    (3, 'en', 'Contact us', 'Contact the TrendShop team.', 'Send us a message, email or call when you need help with your account or order.', 'Contact TrendShop', 'Contact the TrendShop support team.'),
    (3, 'zh', '联系我们', '联系 TrendShop 团队。', '如果您需要账号或订单方面的帮助，请发送消息、电子邮件或致电我们。', '联系 TrendShop', '联系 TrendShop 支持团队。');

-- Mark Laravel's existing framework migrations as represented by this import.
INSERT IGNORE INTO `migrations` (`migration`, `batch`) VALUES
    ('0001_01_01_000000_create_users_table', 1),
    ('0001_01_01_000001_create_cache_table', 1),
    ('0001_01_01_000002_create_jobs_table', 1);

COMMIT;
