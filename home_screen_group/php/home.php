<?php
require_once("../../auth.php");
require_login('../../initial_screen_group/php/login.php');

// 仮ログイン（実際はログイン機能でセット）
if (!isset($_SESSION['user_id'])) { $_SESSION['user_id'] = 1; }

// ユーザー情報取得
$stmt = $pdo->prepare("SELECT user_name FROM users WHERE user_id = :id");
$stmt->bindValue(":id", $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['user_name' => ''];
?>



<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GoriFit ダッシュボード</title>

    <!-- 外部CSS読み込み -->
    <link rel="stylesheet" href="home_style.css">
</head>
<body>

    <div class="app-container">
        
        <!-- ヘッダー -->
        <header class="app-header">
            <span class="logo">GoriFit</span>
            <div class="header-actions">
                <button class="btn-upgrade">アップグレードする</button>
                <span class="icon-settings">⚙️</span>
            </div>
        </header>

        <!-- メインコンテンツ -->
        <main class="app-content">

            <!-- 1. 目標カード -->
            <section class="card">
                <div class="card-header">
                    <h2 class="card-title">目標</h2>
                    <span class="icon-menu">...</span>
                </div>
                
                <div class="goal-content">
                    <div class="goal-icon"></div>
                    <div class="goal-text">
                        <h3>理想のマッスルボディへ</h3>
                        <p>気合で頑張るしかない</p>
                    </div>
                </div>

                <div class="progress-bar-container">
                    <div class="progress-bar-inner" style="width: 10%;"></div>
                </div>

                <p class="progress-text">0/4</p>
                
                <button class="btn-primary" onclick="location.href='../../training_screen_group/php/training_record.php'">トレーニングを始める</button>
            </section>

            <!-- 2. おすすめルーティン -->
            <section class="card">
                <div class="card-header">
                    <h2 class="card-title">おすすめルーティン</h2>
                    <span class="icon-close">×</span>
                </div>

                <div class="routine-scroll-container">
                    <div class="routine-item">
                        <img src="https://via.placeholder.com/60" alt="ベンチプレス">
                        <div class="routine-item-text">
                            <h4>ベンチプレス 初級A</h4>
                            <p>合計4種目・16セット</p>
                        </div>
                    </div>

                    <div class="routine-item">
                        <img src="https://via.placeholder.com/60" alt="スクワット">
                        <div class="routine-item-text">
                            <h4>スクワット 中級B</h4>
                            <p>合計3種目・12セット</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 3. 運動量の変化 -->
            <section class="card">
                <div class="card-header">
                    <h2 class="card-title">運動量の変化</h2>
                    <span>></span>
                </div>

                <div class="tabs">
                    <div class="tab-item active">時間</div>
                    <div class="tab-item">ボリューム</div>
                    <div class="tab-item">密度</div>
                </div>

                <div class="data-placeholder">
                    <p>データ不足</p>
                    <span>変化を分析するには、1回以上の記録が必要です。</span>
                </div>
            </section>

        </main>

        <!-- 下部ナビゲーション -->
        <nav class="app-nav">
            <a href="home.php" class="nav-item active">
                <span class="nav-item-icon">🏠</span> ホーム
            </a>
            <a href="http://localhost/pumpup/SD3D_pumup/training_screen_group/php/calendar.php" class="nav-item">
                <span class="nav-item-icon">💪</span> カレンダー
            </a>
            <a href="mypage.php" class="nav-item">
                <span class="nav-item-icon">👤</span> マイページ
            </a>
        </nav>

    </div>

</body>
</html>
