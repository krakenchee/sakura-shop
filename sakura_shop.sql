-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Мар 30 2026 г., 18:11
-- Версия сервера: 10.3.22-MariaDB
-- Версия PHP: 7.1.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `sakura_shop`
--

-- --------------------------------------------------------

--
-- Структура таблицы `banners`
--

CREATE TABLE `banners` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtitle` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `link` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `banners`
--

INSERT INTO `banners` (`id`, `title`, `subtitle`, `image_path`, `link`, `is_active`) VALUES
(1, 'Неделя Сакуры', 'Скидки до 30% на всю косметику и уход', 'assets/uploads/banners/banner-sakura2.jpg', 'catalog.php?cat=kosmetika', 1),
(2, 'Вкус Японии', 'Новая коллекция снэков и сладостей', 'assets/uploads/banners/banner-food.jpg', 'catalog.php?cat=produkty-pitaniya', 1),
(3, 'Искусство письма', 'Наборы для каллиграфии от Kuretake', 'assets/uploads/banners/banner-calligraphy.jpg', 'catalog.php?cat=kancrelyariya', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `added_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `cart_items`
--

INSERT INTO `cart_items` (`id`, `user_id`, `product_id`, `quantity`, `added_at`) VALUES
(8, 3, 13, 2, '2026-03-05 09:15:36'),
(9, 3, 23, 1, '2026-03-05 09:15:52');

-- --------------------------------------------------------

--
-- Структура таблицы `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `main_category_id` int(10) UNSIGNED NOT NULL,
  `image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `main_category_id`, `image`) VALUES
(6, 'Лапша быстрого приготовления', 'lapsha', 1, NULL),
(7, 'Вагаси (традиционные сладости)', 'vagasi', 1, NULL),
(8, 'Японские закуски и снэки', 'sneki', 1, NULL),
(9, 'Уход за лицом', 'uhod-lico', 2, NULL),
(10, 'Уход за телом', 'uhod-telo', 2, NULL),
(11, 'Косметика для ванн', 'vanna', 2, NULL),
(12, 'Канцелярские товары', 'kanctovar', 3, NULL),
(13, 'Товары для рисования', 'risovanie', 3, NULL),
(14, 'Каллиграфия', 'kalligrafiya', 3, NULL),
(15, 'Оригами', 'origami', 3, NULL),
(16, 'Ранобэ (лёгкие романы)', 'ranobe', 5, NULL),
(17, 'Настольные игры', 'nastolnye-igry', 5, NULL),
(18, 'Брелоки и значки', 'breloki', 4, NULL),
(19, 'Тематические наборы', 'nabory', 4, NULL),
(20, 'Куклы (Кокэси, Дарума)', 'kukly', 4, NULL),
(21, 'Омамори (талисманы)', 'omamori', 4, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `feedback_messages`
--

CREATE TABLE `feedback_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_processed` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `feedback_messages`
--

INSERT INTO `feedback_messages` (`id`, `name`, `email`, `phone`, `message`, `created_at`, `is_processed`) VALUES
(1, 'Карина', 'karina@inbox.ru', NULL, 'jhucfvbevj', '2026-02-24 13:11:08', 0);

-- --------------------------------------------------------

--
-- Структура таблицы `main_categories`
--

CREATE TABLE `main_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `main_categories`
--

INSERT INTO `main_categories` (`id`, `name`, `slug`, `image`) VALUES
(1, 'Продукты питания и сладости', 'produkty-pitaniya', 'assets/images/cat-food.jpg'),
(2, 'Косметика и уход (J-Beauty)', 'kosmetika', 'assets/images/cat-beauty.jpg'),
(3, 'Канцелярия и творчество', 'kancrelyariya', 'assets/images/cat-stationery.jpg'),
(4, 'Сувениры и подарки', 'suveniry', 'assets/images/cat-souvenirs.jpg'),
(5, 'Другое', 'drugoe', 'assets/images/cat-other.jpg');

-- --------------------------------------------------------

--
-- Структура таблицы `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `order_date` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('новый','оплачен','отправлен','доставлен','отменён') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'новый',
  `total_amount` decimal(10,2) NOT NULL,
  `delivery_address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `delivery_method` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_method` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_date`, `status`, `total_amount`, `delivery_address`, `delivery_method`, `payment_method`) VALUES
(1, 3, '2026-02-24 13:02:28', 'оплачен', '4700.00', 'Омск', 'Бесплатная (от 3 000 ₽)', 'Карта онлайн (Visa, MasterCard, МИР)'),
(2, 3, '2026-02-24 13:12:26', 'отменён', '3599.00', 'Москва', 'СДЭК (до двери)', 'Карта онлайн (Visa, MasterCard, МИР)'),
(3, 3, '2026-02-28 11:26:52', 'новый', '3599.00', 'Москва', 'Почта России', 'СБП (Система быстрых платежей)'),
(4, 3, '2026-02-28 11:57:18', 'новый', '488.00', 'jksv', 'Boxberry (пункт выдачи)', 'Карта онлайн (Visa, MasterCard, МИР)');

--
-- Триггеры `orders`
--
DELIMITER $$
CREATE TRIGGER `update_popular_on_order_update` AFTER UPDATE ON `orders` FOR EACH ROW BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE prod_id INT;
    DECLARE cur CURSOR FOR 
        SELECT DISTINCT product_id 
        FROM order_items 
        WHERE order_id = NEW.id 
        AND product_id IS NOT NULL;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    IF OLD.status != NEW.status THEN
        OPEN cur;
        read_loop: LOOP
            FETCH cur INTO prod_id;
            IF done THEN
                LEAVE read_loop;
            END IF;
            
            UPDATE products p
            SET order_count_30d = (
                SELECT COALESCE(SUM(oi.quantity), 0)
                FROM order_items oi
                LEFT JOIN orders o ON o.id = oi.order_id
                WHERE oi.product_id = p.id
                AND o.order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND o.status != 'отменён'
            ),
            is_popular = IF((
                SELECT COALESCE(SUM(oi.quantity), 0)
                FROM order_items oi
                LEFT JOIN orders o ON o.id = oi.order_id
                WHERE oi.product_id = p.id
                AND o.order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND o.status != 'отменён'
            ) >= 10, 1, 0)
            WHERE p.id = prod_id;
        END LOOP;
        CLOSE cur;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price_at_purchase` decimal(10,2) NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `price_at_purchase`, `quantity`) VALUES
(1, 1, 21, 'Ранобэ «Меч без названия», том 1', '750.00', 2),
(2, 1, 22, 'Сёги (японские шахматы) премиум', '3200.00', 1),
(3, 2, 22, 'Сёги (японские шахматы) премиум', '3200.00', 1),
(4, 3, 22, 'Сёги (японские шахматы) премиум', '3200.00', 1),
(5, 3, 13, 'Рамэн Nissin Cup Noodles (говядина)', '149.00', 1),
(6, 4, 14, 'Удон Maruchan (дashi)', '189.00', 1);

--
-- Триггеры `order_items`
--
DELIMITER $$
CREATE TRIGGER `update_popular_on_order_insert` AFTER INSERT ON `order_items` FOR EACH ROW BEGIN
    DECLARE order_count INT;
    
    IF NEW.product_id IS NOT NULL THEN
        SELECT COALESCE(SUM(oi.quantity), 0) INTO order_count
        FROM order_items oi
        LEFT JOIN orders o ON o.id = oi.order_id
        WHERE oi.product_id = NEW.product_id
        AND o.order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND o.status != 'отменён';
        
        UPDATE products 
        SET order_count_30d = order_count,
            is_popular = IF(order_count >= 10, 1, 0)
        WHERE id = NEW.product_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_popular_on_order_item_delete` AFTER DELETE ON `order_items` FOR EACH ROW BEGIN
    DECLARE order_count INT;
    
    IF OLD.product_id IS NOT NULL THEN
        SELECT COALESCE(SUM(oi.quantity), 0) INTO order_count
        FROM order_items oi
        LEFT JOIN orders o ON o.id = oi.order_id
        WHERE oi.product_id = OLD.product_id
        AND o.order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND o.status != 'отменён';
        
        UPDATE products 
        SET order_count_30d = order_count,
            is_popular = IF(order_count >= 10, 1, 0)
        WHERE id = OLD.product_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `old_price` decimal(10,2) DEFAULT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_new` tinyint(1) NOT NULL DEFAULT 0,
  `order_count_30d` int(11) NOT NULL DEFAULT 0,
  `is_popular` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `description`, `price`, `old_price`, `stock_quantity`, `created_at`, `is_new`, `order_count_30d`, `is_popular`) VALUES
(13, 6, 'Рамэн Nissin Cup Noodles (говядина)', 'ramen-nissin-beef', 'Классический японский рамэн быстрого приготовления со вкусом говядины. Приготовление за 3 минуты — просто залейте кипятком и наслаждайтесь!', '149.00', '179.00', 119, '2026-02-24 11:20:55', 0, 0, 0),
(14, 6, 'Удон Maruchan (дashi)', 'udon-maruchan-dashi', 'Традиционная японская лапша удон в бульоне даши. Нежный вкус, идеальная текстура.', '189.00', NULL, 84, '2026-02-24 11:20:55', 0, 0, 0),
(15, 7, 'Моти с клубникой (набор 6 шт)', 'mochi-strawberry', 'Традиционные японские моти с начинкой из спелой клубники и нежного крема. Ручная работа.', '590.00', '690.00', 40, '2026-02-24 11:20:55', 0, 0, 0),
(16, 7, 'Дайфуку ассорти (12 шт)', 'daifuku-assort', 'Нежные рисовые шарики с различными начинками: красная фасоль, матча, вишня.', '890.00', NULL, 30, '2026-02-24 11:20:55', 0, 0, 0),
(17, 9, 'Японская тканевая маска Shiseido', 'shiseido-mask', 'Увлажняющая маска с экстрактом сакуры и гиалуроновой кислотой. 25 мл.', '450.00', '520.00', 60, '2026-02-24 11:20:55', 0, 0, 0),
(18, 10, 'Гель для душа с юдзу Kracie', 'kracie-yuzu-gel', 'Освежающий гель для душа с ароматом японского цитруса юдзу. 480 мл.', '680.00', NULL, 45, '2026-02-24 11:20:55', 0, 0, 0),
(19, 12, 'Ручка Pilot Juice 0.5 мм (набор 10 цв)', 'pilot-juice-10pack', 'Японские гелевые ручки премиум-класса с яркими, насыщенными чернилами. Мягкое письмо.', '990.00', '1200.00', 75, '2026-02-24 11:20:55', 0, 0, 0),
(20, 14, 'Набор для каллиграфии Kuretake', 'kuretake-calligraphy-set', 'Профессиональный набор: 5 кистей, тушь-суми, бумага васи 30 листов, подставка.', '2490.00', NULL, 20, '2026-02-24 11:20:55', 0, 0, 0),
(21, 16, 'Ранобэ «Меч без названия», том 1', 'sword-art-light-novel-1', 'Популярная японская легкая новелла. Твёрдая обложка, иллюстрации оригинального иллюстратора.', '750.00', NULL, 33, '2026-02-24 11:20:55', 0, 0, 0),
(22, 17, 'Сёги (японские шахматы) премиум', 'shogi-premium', 'Традиционные японские шахматы из натурального дерева. Доска 26×26 см, 40 фигур, инструкция.', '3200.00', '3800.00', 12, '2026-02-24 11:20:55', 0, 0, 0),
(23, 18, 'Набор брелоков «Токийская коллекция»', 'tokyo-keychains-set', 'Набор из 5 металлических брелоков: сакура, Фудзи, кот-нэко, дракон, иероглиф «удача».', '890.00', NULL, 50, '2026-02-24 11:20:55', 0, 0, 0),
(24, 20, 'Кукла Дарума (красная, большая)', 'daruma-red-large', 'Традиционная японская кукла-неваляшка Дарума. Символ удачи и достижения целей. Высота 15 см.', '1490.00', '1700.00', 25, '2026-02-24 11:20:55', 0, 0, 0);

--
-- Триггеры `products`
--
DELIMITER $$
CREATE TRIGGER `update_new_status_on_insert` BEFORE INSERT ON `products` FOR EACH ROW BEGIN
    SET NEW.is_new = IF(NEW.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY), 1, 0);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_new_status_on_update` BEFORE UPDATE ON `products` FOR EACH ROW BEGIN
    SET NEW.is_new = IF(NEW.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY), 1, 0);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `product_features`
--

CREATE TABLE `product_features` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `feature_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `feature_value` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `product_features`
--

INSERT INTO `product_features` (`id`, `product_id`, `feature_name`, `feature_value`) VALUES
(1, 13, 'Вес', '75 г'),
(2, 13, 'Страна производитель', 'Япония'),
(3, 13, 'Тип упаковки', 'Стакан'),
(4, 15, 'Состав', 'Рисовая мука, клубника, сливки'),
(5, 15, 'Количество', '6 шт'),
(6, 15, 'Хранение', 'Холодильник, до 5 дней'),
(7, 17, 'Объём', '25 мл'),
(8, 17, 'Тип кожи', 'Все типы'),
(9, 17, 'Активные компоненты', 'Экстракт сакуры, гиалуроновая кислота'),
(10, 19, 'Количество ручек', '10'),
(11, 19, 'Толщина линии', '0.5 мм'),
(12, 19, 'Тип чернил', 'Гелевые, водостойкие'),
(13, 22, 'Материал', 'Натуральное дерево'),
(14, 22, 'Комплектация', 'Доска, 40 фигур, инструкция на рус.');

-- --------------------------------------------------------

--
-- Структура таблицы `product_images`
--

CREATE TABLE `product_images` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `image_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_main` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `is_main`) VALUES
(13, 13, 'assets/uploads/products/ramen-nissin-1.jpg', 1),
(14, 14, 'assets/uploads/products/ramen-nissin-1.jpg', 1),
(15, 15, 'assets/uploads/products/ramen-nissin-1.jpg', 1),
(16, 16, 'assets/uploads/products/ramen-nissin-1.jpg', 1),
(17, 17, 'assets/uploads/products/ramen-nissin-1.jpg', 1),
(18, 18, 'assets/uploads/products/ramen-nissin-1.jpg', 1),
(19, 19, 'assets/uploads/products/ramen-nissin-1.jpg', 1),
(20, 20, 'assets/uploads/products/ramen-nissin-1.jpg', 1),
(21, 21, 'assets/uploads/products/ramen-nissin-1.jpg', 1),
(22, 22, 'assets/uploads/products/ramen-nissin-1.jpg', 1),
(23, 23, 'assets/uploads/products/ramen-nissin-1.jpg', 1),
(24, 24, 'assets/uploads/products/ramen-nissin-1.jpg', 1),
(25, 13, 'assets/uploads/products/ramen-nissin-2.jpg', 0);

-- --------------------------------------------------------

--
-- Структура таблицы `reviews`
--

CREATE TABLE `reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `reviews`
--

INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `comment`, `created_at`) VALUES
(1, 13, 2, 5, 'Обожаю этот рамэн! Покупаю уже третий раз, вкус совпадает с оригинальным японским. Рекомендую!', '2026-02-24 11:27:19'),
(2, 15, 2, 5, 'Моти просто таяли во рту. Клубника свежая, тесто нежнейшее. Упакованы отлично.', '2026-02-24 11:27:19'),
(3, 19, 2, 4, 'Ручки пишут шикарно, чернила яркие и не расплываются. Минус балл за долгую доставку.', '2026-02-24 11:27:19'),
(4, 19, 1, 4, ',j,j', '2026-02-24 13:11:27');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('client','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'client',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `email`, `phone`, `password_hash`, `full_name`, `role`, `created_at`) VALUES
(1, 'admin@sakura-shop.ru', '+7 (999) 000-00-00', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Администратор Сайта', 'admin', '2026-02-24 10:11:42'),
(2, 'user@example.com', '+7 (999) 111-22-33', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Мария Иванова', 'client', '2026-02-24 10:11:42'),
(3, 'karina@inbox.ru', '+7 (123) 456-78-90', '$2y$10$5bSX1lnh5RXZfpXPRNrLfO6WwD/hkUn4KannJmye44QEjOs.jLrpS', 'Карина', 'client', '2026-02-24 13:00:31'),
(4, 'dcscv@s.f', '+7 (233) 333-33-33', '$2y$10$Z96umo4QVEGFKyPUmyejc.RtsJ6TgIbzp6421mGLfjnx180YG2/Pi', 'яы', 'client', '2026-02-28 12:15:36'),
(5, 'acef@sf.d', '+7 (233) 333-33-33', '$2y$10$KgXCpy7/rGWQ3kCjIl0qYeHfphhrMDQMFGIxkOAEsW6mH09OKAZOW', '222', 'client', '2026-02-28 12:17:11');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_product` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Индексы таблицы `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `fk_category_main_category` (`main_category_id`);

--
-- Индексы таблицы `feedback_messages`
--
ALTER TABLE `feedback_messages`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `main_categories`
--
ALTER TABLE `main_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Индексы таблицы `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Индексы таблицы `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_popular` (`is_popular`),
  ADD KEY `idx_new` (`is_new`);

--
-- Индексы таблицы `product_features`
--
ALTER TABLE `product_features`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Индексы таблицы `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Индексы таблицы `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_product_review` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT для таблицы `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT для таблицы `feedback_messages`
--
ALTER TABLE `feedback_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `main_categories`
--
ALTER TABLE `main_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT для таблицы `product_features`
--
ALTER TABLE `product_features`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT для таблицы `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT для таблицы `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_category_main_category` FOREIGN KEY (`main_category_id`) REFERENCES `main_categories` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_oi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_cat` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `product_features`
--
ALTER TABLE `product_features`
  ADD CONSTRAINT `fk_feat_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `fk_img_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_rev_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rev_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
