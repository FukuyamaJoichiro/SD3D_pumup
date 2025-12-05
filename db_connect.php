<?php
$hostName = $_SERVER['HTTP_HOST'] ?? '';



$isLocal =
    stripos(PHP_OS, 'WIN') === 0 ||
    $hostName === 'localhost' ||
    $hostName === '127.0.0.1';

if ($isLocal) {
    require __DIR__ . '/config/db_connect_local.php';
} else {
    require __DIR__ . '/config/db_connect_prod.php';
}
?>