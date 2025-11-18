<?php
// データベース接続ファイルを読み込み
require_once '../../db_connect.php';

try {
    session_start();
    $user_id = $_SESSION['user_id'] ?? 1;
    
    // trainingsテーブル + 部位結合
    $stmt = $pdo->query("
        SELECT t.training_id, t.training_name, tp.part_id
        FROM trainings t
        LEFT JOIN training_parts tp ON t.training_id = tp.training_id
        ORDER BY t.training_id
    ");
    $training_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ブックマーク取得
    $stmt = $pdo->prepare("SELECT training_id FROM bookmarks WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $bookmarked_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // 種目ごとに整理
    $trainings = [];
    foreach ($training_data as $row) {
        $id = $row['training_id'];
        if (!isset($trainings[$id])) {
            $trainings[$id] = [
                'training_id' => $id,
                'training_name' => $row['training_name'],
                'part_ids' => [],
                'is_bookmarked' => in_array($id, $bookmarked_ids)
            ];
        }
        if ($row['part_id']) {
            $trainings[$id]['part_ids'][] = $row['part_id'];
        }
    }
    
    // parts
    $parts = $pdo->query("SELECT part_id, part_name FROM parts ORDER BY part_id")->fetchAll(PDO::FETCH_ASSOC);
    // tools
    $tools = $pdo->query("SELECT tool_id, tool_name FROM tools ORDER BY tool_id")->fetchAll(PDO::FETCH_ASSOC);
    // types
    $types = $pdo->query("SELECT type_id, type_name FROM types ORDER BY type_id")->fetchAll(PDO::FETCH_ASSOC);

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

<style>
/* ======= ここから追記：詳細モーダル ======= */

.modal-info {
  display: none;
  position: fixed;
  z-index: 2000;
  left: 0; top: 0;
  width: 100%; height: 100%;
  background: rgba(0,0,0,0.55);
  align-items: center;
  justify-content: center;
}

.modal-info-content {
  background: #fff;
  width: 90%;
  max-width: 420px;
  border-radius: 12px;
  padding: 16px;
  animation: fadeIn 0.25s ease;
}

.modal-info-close {
  float: right;
  cursor: pointer;
  font-size: 22px;
  border: none;
  background: none;
}

/* フェードイン */
@keyframes fadeIn { from {opacity: 0;} to {opacity: 1;} }

/* ======= 追記ここまで ======= */
</style>

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
        <button class="sort-btn">並び替え <span>↕️</span></button>
    </div>
    
    <form method="post" action="">
        <div class="training-list">
            <?php foreach ($trainings as $training): ?>
                <div class="training-item"
                     data-training-id="<?php echo $training['training_id']; ?>"
                     data-part-ids="<?php echo !empty($training['part_ids']) ? implode(',', $training['part_ids']) : ''; ?>"
                     data-bookmarked="<?php echo $training['is_bookmarked'] ? '1' : '0'; ?>">
                     
                    <input type="checkbox" class="checkbox" name="training[]" value="<?php echo $training['training_id']; ?>">
                    <span class="training-name"><?php echo htmlspecialchars($training['training_name']); ?></span>

                    <button type="button" class="bookmark-icon" data-training-id="<?php echo $training['training_id']; ?>">
                        <?php echo $training['is_bookmarked'] ? '🚩' : '🏴'; ?>
                    </button>

                    <button type="button" class="info-icon" data-training-id="<?php echo $training['training_id']; ?>">ⓘ</button>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="add-section">
            <button type="button" class="add-btn">
                <span style="font-size: 20px;">+</span>
            </button>
            <button type="submit" class="submit-btn">トレーニングを追加する</button>
        </div>
    </form>

    </div>


<!-- ▼ トレーニング詳細モーダル（新規追加） -->
<div id="modal-info" class="modal-info">
  <div class="modal-info-content">
    <button class="modal-info-close" id="modal-info-close">✕</button>
    <div id="modal-info-body"></div>
  </div>
</div>


<!-- ▼ 既存：トレーニング追加モーダル（変更なし） -->
<div id="modal-overlay" class="modal-overlay">
    <div id="add-training-modal" class="modal-content">
        <button type="button" class="modal-close" id="modal-close-btn">✕</button>
        <h2>新規トレーニング追加</h2>
        <!-- 省略：既存フォーム -->
    </div>
</div>

<script src="training_list.js"></script>

<!-- ▼ ここから追記：詳細取得JS -->
<script>
document.querySelectorAll(".info-icon").forEach(btn => {
  btn.addEventListener("click", () => {
    const id = btn.dataset.trainingId;
    fetch(`training_detail_modal.php?training_id=${id}`)
      .then(r => r.text())
      .then(html => {
        document.getElementById("modal-info-body").innerHTML = html;
        document.getElementById("modal-info").style.display = "flex";
      })
      .catch(() => alert("詳細の取得に失敗しました"));
  });
});

document.getElementById("modal-info-close").addEventListener("click", () => {
  document.getElementById("modal-info").style.display = "none";
});

window.addEventListener("click", (e) => {
  if (e.target.id === "modal-info") {
    document.getElementById("modal-info").style.display = "none";
  }
});
</script>
<!-- ▲ ここまで追記 -->

</body>
</html>
