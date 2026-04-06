<?php
/**
 * Функции для работы со статусами товаров
 * Все статусы обновляются автоматически через триггеры БД
 */

// Функция для получения статистики статусов (используется в админке)
function getProductStatusStats() {
    $db = getDB();
    
    $stats = [];
    
    // Количество новинок (автоматический статус)
    $stmt = $db->query("SELECT COUNT(*) FROM products WHERE is_new = 1");
    $stats['new_count'] = $stmt->fetchColumn();
    
    // Количество популярных товаров (автоматический статус)
    $stmt = $db->query("SELECT COUNT(*) FROM products WHERE is_popular = 1");
    $stats['popular_count'] = $stmt->fetchColumn();
    
    // Топ-5 популярных товаров для отображения в админке
    $stmt = $db->query("
        SELECT id, name, order_count_30d 
        FROM products 
        WHERE is_popular = 1 
        ORDER BY order_count_30d DESC 
        LIMIT 5
    ");
    $stats['top_popular'] = $stmt->fetchAll();
    
    return $stats;
}