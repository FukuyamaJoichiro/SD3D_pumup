<?php
// ✅ セッション衝突を防ぐ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_connect.php';
$GENERAL_ERROR_MSG = "メールアドレスまたはパスワードが正しくありません。";

/**
 * ログイン処理
 * @return array [成功(bool), メッセージ(string)]
 */
function login($email, $password) {
    global $pdo, $GENERAL_ERROR_MSG;

    // 🌟 修正不要。DBにハッシュ値が保存されているため、trueのまま
    $USE_HASH = true; 

    try {
        // 1. メールアドレスでユーザー情報を取得
        $stmt = $pdo->prepare(
            "SELECT user_id, password FROM users WHERE email = :email"
        );
        $stmt->bindValue(":email", $email, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // ユーザーが存在しない
        if (!$user) {
            return [false, $GENERAL_ERROR_MSG];
        }

        // 2. パスワードを検証
        $is_authenticated = false;

        if ($USE_HASH) {
            // ✅ ハッシュ検証: password_verifyで平文とDBのハッシュ値を比較
            if (password_verify($password, $user['password'])) {
                $is_authenticated = true;
            }
        } else {
            // ❌ 平文比較: (推奨されないが、USE_HASH=false時のための互換性)
            if ($user['password'] === $password) {
                $is_authenticated = true;
            }
        }
        
        // 3. 認証結果の返却
        if ($is_authenticated) {
            $_SESSION['user_id'] = $user['user_id'];
            return [true, ""]; // ログイン成功
        } else {
            // 認証失敗
            return [false, $GENERAL_ERROR_MSG]; 
        }

    } catch (Exception $e) {
        // 本番環境ではエラーログに出力することを強く推奨します
        return [false, "システムエラーが発生しました。"];
    }
}

/**
 * ログイン必須チェック
 */
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        // リダイレクトする前に現在のURLを保存するとより使いやすい（login.phpに記述済みかも）
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