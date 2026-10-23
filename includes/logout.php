<?php
require_once __DIR__ . '/auth.php';
$target = !empty($_GET['redirect']) && $_GET['redirect'] === 'login'
    ? (BASE_URL . '/login.php')
    : (BASE_URL . '/index.php');
logout($target);
