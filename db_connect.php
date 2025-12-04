<?php
/**
 * 環境によって自動で接続先 DB を切り替える
 */

$hostName = $_SERVER['HTTP_HOST'] ?? '';

if ($hostName === 'localhost' || $hostName === '127.0.0.1') {
    // ローカル環境（XAMPP）
    require __DIR__ . '/config/db_connect_local.php';
} else {
    // 本番環境（ロリポップ）
    require __DIR__ . '/config/db_connect_prod.php';
}
