<?php
// データベース接続
require_once ('../../db_connect.php');

try {
    // セッション開始
    session_start();
    $user_id = $_SESSION['user_id'] ?? null;

    if (!$user_id) {
        exit('ログイン情報がありません');
    }

    // trainings + parts（表示制御込み）
    $stmt = $pdo->prepare("
        SELECT
            t.training_id,
            t.training_name,
            t.created_user_id,
            tp.part_id
        FROM trainings t
        LEFT JOIN training_parts tp
            ON t.training_id = tp.training_id
        WHERE
            t.created_user_id IS NULL
            OR t.created_user_id = :user_id
        ORDER BY t.training_id
    ");
    $stmt->execute([':user_id' => $user_id]);
    $training_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ユーザーのブックマーク取得
    $stmt = $pdo->prepare("
        SELECT training_id
        FROM bookmarks
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $bookmarked_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // トレーニングごとに整形
    $trainings = [];
    foreach ($training_data as $row) {
        $id = $row['training_id'];

        if (!isset($trainings[$id])) {
            $trainings[$id] = [
                'training_id'    => $id,
                'training_name'  => $row['training_name'],
                'created_user_id'=> $row['created_user_id'],
                'part_ids'       => [],
                'is_bookmarked'  => in_array($id, $bookmarked_ids)
            ];
        }

        if ($row['part_id']) {
            $trainings[$id]['part_ids'][] = $row['part_id'];
        }
    }

    // parts
    $stmt = $pdo->query("SELECT part_id, part_name FROM parts ORDER BY part_id");
    $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // tools
    $stmt = $pdo->query("SELECT tool_id, tool_name FROM tools ORDER BY tool_id");
    $tools = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // types
    $stmt = $pdo->query("SELECT type_id, type_name FROM types ORDER BY type_id");
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    echo "データ取得エラー: " . $e->getMessage();
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>トレーニング一覧</title>
    <link rel="stylesheet" href="training_list.css">
</head>

<body>
<div class="container">

    <div class="header">
        <button class="back-btn" onclick="history.back()">＜</button>
        <h1>トレーニング一覧</h1>
    </div>

    <input type="text" class="search-box" id="search-input" placeholder="🔍 トレーニングを検索">

    <div class="filter-section">
        <button class="filter-btn active" data-part-id="all">□</button>
        <button class="filter-btn" data-part-id="bookmark">🏴 ブックマーク</button>
        <?php foreach ($parts as $part): ?>
            <button class="filter-btn" data-part-id="<?php echo $part['part_id']; ?>">
                <?php echo htmlspecialchars($part['part_name']); ?>
            </button>
        <?php endforeach; ?>
    </div>

    <div class="bookmark-filter">
        <input type="checkbox" class="checkbox" id="bookmark-only">
        <label for="bookmark-only">ブックマークのみ見る</label>
        
    </div>

    <form method="post" action="training_select.php">
        <div class="training-list">
            <?php foreach ($trainings as $training): ?>
                <div class="training-item"
                     data-training-id="<?php echo $training['training_id']; ?>"
                     data-part-ids="<?php echo implode(',', $training['part_ids']); ?>"
                     data-bookmarked="<?php echo $training['is_bookmarked'] ? '1' : '0'; ?>">
                    
                    <input type="checkbox"
                           class="checkbox"
                           name="training[]"
                           value="<?php echo $training['training_id']; ?>">

                    <span class="training-name">
                        <?php echo htmlspecialchars($training['training_name']); ?>
                        <?php if ($training['created_user_id'] == $user_id): ?>
                            <span style="font-size:12px;opacity:0.6;">👤</span>
                        <?php endif; ?>
                    </span>

                    <button type="button"
                            class="bookmark-icon"
                            data-training-id="<?php echo $training['training_id']; ?>">
                        <?php echo $training['is_bookmarked'] ? '🚩' : '🏴'; ?>
                    </button>

                    <button type="button"
                            class="info-icon"
                            data-training-id="<?php echo $training['training_id']; ?>">ⓘ</button>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="add-section">
            <button type="button" class="add-btn">
                <span style="font-size:20px;">+</span>
            </button>
            <button type="submit" class="submit-btn">トレーニングを追加する</button>
        </div>
    </form>
</div>

<!-- ===== 新規トレーニング追加モーダル（スクショ風） ===== -->
<div id="modal-overlay" class="modal-overlay">
    <div class="modal-content">

        <!-- 閉じる -->
        <button type="button" id="modal-close-btn" class="modal-close">×</button>

        <h2>新規トレーニング追加</h2>

        <form id="add-training-form">
            <!-- トレーニング名 -->
            <div class="form-group">
                <label for="training_name">トレーニング名</label>
                <input
                    id="training_name"
                    type="text"
                    name="training_name"
                    class="form-input"
                    placeholder="追加するトレーニング名を入力"
                    required
                >
            </div>

            <!-- カテゴリー（部位） -->
            <div class="form-group">
                <label>カテゴリー</label>
                <div class="button-group">
                    <?php foreach ($parts as $part): ?>
                        <button
                            type="button"
                            class="toggle-btn"
                            data-name="part_id"
                            data-value="<?php echo htmlspecialchars($part['part_id']); ?>"
                        >
                            <?php echo htmlspecialchars($part['part_name']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" id="part_id" name="part_id">
            </div>

            <!-- ツール -->
            <div class="form-group">
                <label>ツール</label>
                <div class="button-group">
                    <?php foreach ($tools as $tool): ?>
                        <button
                            type="button"
                            class="toggle-btn"
                            data-name="tool_id"
                            data-value="<?php echo htmlspecialchars($tool['tool_id']); ?>"
                        >
                            <?php echo htmlspecialchars($tool['tool_name']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" id="tool_id" name="tool_id">
            </div>

            <!-- タイプ（複数選択） -->
            <div class="form-group">
                <label>タイプ <span class="note">※最大2つまで選択可能</span></label>
                <div class="button-group">
                    <?php foreach ($types as $type): ?>
                        <button
                            type="button"
                            class="toggle-btn"
                            data-name="type_id"
                            data-value="<?php echo htmlspecialchars($type['type_id']); ?>"
                        >
                            <?php echo htmlspecialchars($type['type_name']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" id="type_id" name="type_id">
            </div>

            <button type="submit" class="modal-submit-btn">トレーニングを追加する</button>
        </form>

    </div>
</div>

<script src="training_list.js"></script>
<script src="detail_modal.js"></script>
<script src="training_detail_modal.js"></script>

<!-- 詳細モーダル -->
<div id="detail-modal-overlay" class="modal-overlay" style="z-index:2000; display:none;">
    <div id="detail-modal-content" class="modal-content detail-modal-box"></div>
</div>

</body>
</html>
