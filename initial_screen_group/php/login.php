<?php
require_once __DIR__ . '/../../auth.php'; // auth.phpへの正しいパスを確認してください

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // 1. 入力値の必須チェック
    if ($email === '' || $password === '') {
        $error = 'メールアドレスとパスワードを入力してください。';
    } else {
        
        // 2. 認証処理と戻り値の受け取り
        // auth.phpのlogin関数が [成功(bool), メッセージ(string)] の配列を返すため、list()で受け取ります。
        list($success, $message) = login($email, $password);

        if ($success) {
            // 3. 認証成功時のリダイレクト処理
            $default_redirect = '../../home_screen_group/php/home.php';

            $redirect = $_SESSION['redirect_to'] ?? $default_redirect;
            unset($_SESSION['redirect_to']);
            
            header('Location: ' . $redirect);
            exit;

        } else {
            // 4. 認証失敗時のエラーメッセージ表示
            // login()関数から返されたメッセージをエラー変数に格納します。
            $error = $message;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン</title>

    <style>
        /* CSSを修正してスマホビューに対応させます */
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
                         "Helvetica Neue", Arial, sans-serif;
            background:#f4f4f8; /* 画面外側の背景色 */
            margin:0;
            padding:0;
            min-height: 100vh;
            /* 中央寄せのため、bodyをFlexコンテナにするのは維持 */
            display: flex; 
            justify-content: center; 
            align-items: center; 
        }
        
        /* ✅ 新しいラッパー: カレンダー画面のヘッダー色（赤）を再現 */
        .mobile-app-container {
            max-width: 420px; /* 画面幅の最大値を制限 */
            width: 100%;
            min-height: 100vh;
            margin: 0 auto;
            background: #ff7b7b; /* 👈 メインカラーの背景色 */
            box-shadow: 0 0 10px rgba(0,0,0,.1);
            
            /* Flexアイテムとして配置された場合に、画面中央に自身を配置 */
            display: flex;
            justify-content: center;
            align-items: center;
            
            /* bodyのFlex設定を無効化するため、bodyのFlex設定を削除するか、HTML構造を調整する必要があります。
               ここでは、bodyのFlex設定を外し、mobile-app-containerを画面いっぱいに広げます。 */
        }
        
        /* bodyのFlex設定を無効化し、mobile-app-containerで画面を覆う */
        body {
            display: block; 
        }
        .mobile-app-container {
            position: relative;
            min-height: 100vh;
        }

        /* ログインフォームの白い部分 */
        .wrap {
            max-width:340px; /* コンテナ内で少し余白を持たせるため、少し狭めに */
            width: 85%; 
            
            /* フォームを垂直方向中央に配置 */
            margin: 0 auto;
            position: relative;
            top: -20px; /* 少し上に持ち上げてバランスを取る */
            
            background:#fff;
            padding:24px;
            border-radius:12px;
            box-shadow:0 2px 10px rgba(0,0,0,.06);
        }
        h1 {
            margin:0 0 16px;
            font-size:24px;
            text-align: center; 
            color: #ff7b7b;
            font-weight: bold;
        }
        .field { margin-bottom:16px; } 
        label {
            display:block;
            margin:0 0 6px;
            color:#555;
            font-size:14px;
            font-weight: 500;
        }
        input {
            width:100%;
            padding:12px;
            border:1px solid #ddd;
            border-radius:8px; 
            font-size:16px;
            box-sizing: border-box; 
            transition: border-color 0.2s;
        }
        input:focus {
            border-color: #ff7b7b;
            outline: none;
        }
        .btn {
            width:100%;
            padding:14px;
            background:#ff7b7b;
            color:#fff;
            border:none;
            border-radius:10px;
            font-weight:bold;
            font-size: 18px;
            cursor:pointer;
            transition: background-color 0.2s;
            box-shadow: 0 4px 10px rgba(255, 123, 123, 0.3);
        }
        .btn:hover {
            background: #ff6e6e;
        }
        .error {
            color:#d00;
            margin:8px 0 16px; 
            font-size:14px;
            text-align: center;
            border: 1px solid #d00;
            padding: 10px;
            background: #ffecec;
            border-radius: 8px;
        }
        a {
            display: block;
            text-align: center;
            font-size:14px;
            color:#007aff;
            margin-top:20px;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="mobile-app-container">
    <div class="wrap">
        <h1>ログイン</h1>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="field">
                <label>メールアドレス</label>
                <input type="email" name="email" required autocomplete="username">
            </div>

            <div class="field">
                <label>パスワード</label>
                <input type="password" name="password" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn">ログイン</button>
        </form>

        <a href="bodydata_register.php">新規登録の方はこちらから </a>
    </div>
    </div>
</body>
</html>
