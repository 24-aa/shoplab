-- 星海商城 ShopLab 建库脚本（手工导入用）
-- 用法：mysql -uroot -proot < shoplab.sql
-- 或直接在 phpMyAdmin 中导入。install.php 会自动完成同样的初始化。

CREATE DATABASE IF NOT EXISTS `shoplab` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `shoplab`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `nickname` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL DEFAULT '',
  `avatar` VARCHAR(255) NOT NULL DEFAULT '',
  `role` ENUM('user','admin') NOT NULL DEFAULT 'user',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `name` VARCHAR(100) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `stock` INT NOT NULL DEFAULT 0,
  `description` VARCHAR(255) NOT NULL DEFAULT '',
  `icon` VARCHAR(20) NOT NULL DEFAULT '',
  `sales` INT NOT NULL DEFAULT 0,
  `status` TINYINT NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_no` VARCHAR(32) NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` VARCHAR(20) NOT NULL DEFAULT '待发货',
  `receiver` VARCHAR(50) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `address` VARCHAR(200) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  UNIQUE KEY `uk_order_no` (`order_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `qty` INT NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 种子账号，密码均为 123456
INSERT IGNORE INTO `users` (`username`, `password`, `nickname`, `email`, `role`) VALUES
('admin', '$2y$10$lBNpqmisHiZ9Ix6XhDwMv.Zg9luL0cNZ5yArM5HQyhrDXrcy1WGEe', '管理员', 'admin@shoplab.local', 'admin'),
('alice', '$2y$10$lBNpqmisHiZ9Ix6XhDwMv.Zg9luL0cNZ5yArM5HQyhrDXrcy1WGEe', '爱丽丝', 'alice@shoplab.local', 'user'),
('bob',   '$2y$10$lBNpqmisHiZ9Ix6XhDwMv.Zg9luL0cNZ5yArM5HQyhrDXrcy1WGEe', '鲍勃', 'bob@shoplab.local', 'user');

INSERT IGNORE INTO `categories` (`id`, `name`) VALUES (1, '数码影音'), (2, '家居生活'), (3, '办公外设');

INSERT IGNORE INTO `products` (`id`, `category_id`, `name`, `price`, `stock`, `description`, `icon`, `sales`) VALUES
(1, 1, '星海无线降噪耳机', 699.00, 20, '主动降噪，续航 30 小时', '耳', 120),
(2, 3, '星海机械键盘', 399.00, 35, '87 键青轴，RGB 背光', '键', 86),
(3, 1, '桌面蓝牙音箱', 259.00, 40, '双单元立体声，支持 TF 卡', '音', 64),
(4, 2, '护眼台灯', 129.00, 50, '无频闪，三档色温', '灯', 152),
(5, 1, '便携充电宝', 199.00, 60, '20000mAh，双向快充', '电', 210),
(6, 3, '人体工学鼠标', 159.00, 45, '垂直握持，静音按键', '鼠', 97),
(7, 2, '香薰加湿器', 189.00, 30, '静音雾化，七彩夜灯', '湿', 41),
(8, 3, '双肩电脑包', 229.00, 25, '15.6 英寸，防泼水面料', '包', 73);
