<?php
session_start();
require_once("../../db_connect.php");
require_once("db_connect.php");

/**
 * ログイン処理
 * @param string $email    入力されたメールアドレス
 * @param string $password 入力されたパスワード
 * @return bool 成功なら true、失敗なら false
 */
function login($email, $password) {
    global $pdo;

    // --- モード設定 ---
    // false = 開発テスト用（平文パスワード）
    // true  = 本番用（ハッシュ化されたパスワード）
    $USE_HASH = false;

    try {
        if ($USE_HASH) {
            // ✅ 本番用（ハッシュ化パスワード対応）
            $stmt = $pdo->prepare("SELECT user_id, password_hash FROM users WHERE email = :email");
        } else {
            // 🧪 開発用（平文パスワード対応）
            $stmt = $pdo->prepare("SELECT user_id, password FROM users WHERE email = :email");
        }

        $stmt->bindValue(":email", $email, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo "⚠️ ユーザーが見つかりません。<br>";
            return false;
        }

        // --- パスワードチェック ---
        if ($USE_HASH) {
            // ハッシュを使う場合
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['user_id'];
                return true;
            } else {
                echo "❌ パスワードが違います。<br>";
                return false;
            }
        } else {
            // 平文を使う場合
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
/**
 * ログインが必要なページで、ログイン状態をチェックし、未ログインならリダイレクトする
 * @param string $login_url ログインページへのパス（未ログイン時にリダイレクトされる場所）
 */
// ★ 修正 2: require_login関数を追加
function require_login($login_url) {
    if (!isset($_SESSION['user_id'])) {
        // 未ログインの場合、現在のURLをセッションに保存してからログインページへ
        $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . $login_url);
        exit;
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
?>
