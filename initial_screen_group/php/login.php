<?php
require_once __DIR__ . '/../../auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'メールアドレスとパスワードを入力してください。';
    } else {
        if (login($email, $password)) {
            $default_redirect = '../../home_screen_group/php/home.php';

            $redirect = $_SESSION['redirect_to'] ?? $default_redirect;
            unset($_SESSION['redirect_to']);
            
            header('Location: ' . $redirect);
            exit;

        } else {
            // ✅ auth.php 内のエラー文を表示
            $error = $msg;
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
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
                         "Helvetica Neue", Arial, sans-serif;
            background:#f4f4f8;
            margin:0;
        }
        .wrap {
            max-width:420px;
            margin:40px auto;
            background:#fff;
            padding:24px;
            border-radius:12px;
            box-shadow:0 2px 10px rgba(0,0,0,.06);
        }
        h1 {
            margin:0 0 16px;
            font-size:20px;
        }
        .field { margin-bottom:12px; }
        label {
            display:block;
            margin:0 0 6px;
            color:#555;
            font-size:14px;
        }
        input {
            width:100%;
            padding:10px;
            border:1px solid #ddd;
            border-radius:6px;
            font-size:14px;
        }
        .btn {
            width:100%;
            padding:12px;
            background:#ff7b7b;
            color:#fff;
            border:none;
            border-radius:8px;
            font-weight:bold;
            cursor:pointer;
        }
        .error {
            color:#d00;
            margin:8px 0 0;
            font-size:13px;
        }
        .hint {
            font-size:12px;
            color:#777;
            margin-top:12px;
        }
    </style>
</head>

<body>
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

        <p class="hint">初回利用の場合は管理者にアカウント作成を依頼してください。</p>
    </div>
</body>
</html> 