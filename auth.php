<?php
// ✅ セッション衝突を防ぐ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_connect.php';

/**
 * ログイン処理
 * @return array [成功(bool), メッセージ(string)]
 */
function login($email, $password) {
    global $pdo;

    $USE_HASH = false; // ←本番でハッシュに切り替える時は true

    try {
        if ($USE_HASH) {
            $stmt = $pdo->prepare(
                "SELECT user_id, password_hash FROM users WHERE email = :email"
            );
        } else {
            $stmt = $pdo->prepare(
                "SELECT user_id, password FROM users WHERE email = :email"
            );
        }

        $stmt->bindValue(":email", $email, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return [false, "ユーザーが見つかりません。"];
        }

        if ($USE_HASH) {
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['user_id'];
                return [true, ""];
            } else {
                return [false, "パスワードが違います。"];
            }
        } else {
            if ($user['password'] === $password) {
                $_SESSION['user_id'] = $user['user_id'];
                return [true, ""];
            } else {
                return [false, "パスワードが違います。"];
            }
        }

    } catch (Exception $e) {
        return [false, "システムエラーが発生しました。"];
    }
}

/**
 * ログイン必須チェック
 */
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /pumpup/SD3D_pumup/initial_screen_group/php/login.php");
        exit;
    }
}

function logout() {
    // セッション変数を全て解除する
    $_SESSION = array();

    // セッションクッキーを削除する (オプション、セキュリティ強化のため推奨)
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // セッションを破壊する
    session_destroy();
}