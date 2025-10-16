<?php

/** 接続情報の設定 */

$host = 'mysql326.phy.lolipop.lan';

$dbname = 'LAA1684537-gorifit'; // 例: your_database_name

$user = 'LAA1684537'; 

$password = 'GoriFit'; // XAMPPのデフォルトは空
 
// データソース名 (DSN) の設定

// XAMPPのMariaDBでも'mysql'ドライバを使用します

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

    // echo "データベースに接続しました！";
 
} catch (PDOException $e) {

    // 接続失敗時の処理

    // 致命的なエラーとして処理を中断

    header('Content-Type: text/plain; charset=UTF-8', true, 500);

    // die() はスクリプトの実行を停止させます

    exit("データベース接続エラー: " . $e->getMessage()); 

}

?>
 