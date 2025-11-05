<?php
session_start();
require_once("../../db_connect.php");

/**
 * ログイン処理
 * @param string $email    入力されたメールアドレス
 * @param string $password 入力されたパスワード
 * @return bool 成功なら true、失敗なら false
 */
function login($email, $password) {
    global $pdo;

    $USE_HASH = false; // 開発中はfalse（本番ではtrueに）

    try {
        if ($USE_HASH) {
            $stmt = $pdo->prepare("SELECT user_id, password_hash FROM users WHERE email = :email");
        } else {
            $stmt = $pdo->prepare("SELECT user_id, password FROM users WHERE email = :email");
        }

        $stmt->bindValue(":email", $email, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo "⚠️ ユーザーが見つかりません。<br>";
            return false;
        }

        if ($USE_HASH) {
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['user_id'];
                return true;
            } else {
                echo "❌ パスワードが違います。<br>";
                return false;
            }
        } else {
            if ($user['password'] === $password) {
                $_SESSION['user_id'] = $user['user_id'];
                return true;
            } else {
                echo "❌ パスワードが違います。<br>";
                return false;
            }
        }

    } catch (PDOException $e) {
        echo "データベースエラー: " . htmlspecialchars($e->getMessage()) . "<br>";
        return false;
    }
}

// --- ログイン試行（例）---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (login($email, $password)) {
        header("Location: ../home_screen_group/php/home.php");
        exit;
    } else {
        echo "<p>ログインに失敗しました。</p>";
    }
}

/**
 * ログイン済みチェック
 * @param string $redirect_path ログインしていない場合に飛ばす先
 */
function require_login($redirect_path) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: $redirect_path");
        exit();
    }
}
?>
