<?php
// admin_header.php
if (!isset($pageTitle)) $pageTitle = 'Панель администратора — Sakura Shop';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  
  <!-- Только общие стили и стили админки -->
  <link rel="stylesheet" href="<?= BASE_URL ?>css/main.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>css/admin.css">
  
  <style>
    /* Дополнительные сбросы для админки */
    body {
      background: var(--ivory);
      margin: 0;
      padding: 0;
      overflow-x: hidden;
    }
    
    * {
      box-sizing: border-box;
    }
  </style>
</head>
<body>