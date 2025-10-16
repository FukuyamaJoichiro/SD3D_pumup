<?php
 
error_reporting(E_ALL);

ini_set('display_errors', 1);

 
/** 接続情報の設定 */
 
$host = 'localhost';
$dbname = 'gorifit'; 
$user = 'root';
$password = ''; 
 
// データソース名 (DSN) の設定
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
 
try {
    // 1. PDOインスタンスの作成（接続処理）

    $pdo = new PDO($dsn, $user, $password);

    // 2. エラー処理方法の設定（必須）
    // 開発時には「例外」としてエラーを投げる設定（PDO::ERRMODE_EXCEPTION）が最も推奨されます。
    // これにより、SQLエラーが発生したときにスクリプトが停止し、エラーメッセージが表示されます。
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 
    // 3. プリペアドステートメントのエミュレーションを無効にする（セキュリティのため推奨）
 
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
 
    // 接続成功メッセージ（本番環境では削除）
    //echo "データベースに接続しました！";
 
} catch (PDOException $e) {
 
    // 接続失敗時の処理
    // 致命的なエラーとして処理を中断
 
    header('Content-Type: text/plain; charset=UTF-8', true, 500);
 
    // die() はスクリプトの実行を停止させます
 
    exit("データベース接続エラー: " . $e->getMessage());
 
}
 
?>
 
 