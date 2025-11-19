<?php
require_once '../../db_connect.php';

$training_id = filter_input(INPUT_GET, 'training_id', FILTER_VALIDATE_INT);
if (!$training_id) {
    http_response_code(400);
    exit("不正なアクセスです");
}

$stmt = $pdo->prepare("
    SELECT t.training_name, t.explanation, t.move,
           GROUP_CONCAT(DISTINCT tl.tool_name SEPARATOR '、') AS tools,
           GROUP_CONCAT(DISTINCT ty.type_name SEPARATOR '、') AS types
    FROM trainings t
    LEFT JOIN training_tools tt ON t.training_id = tt.training_id
    LEFT JOIN tools tl ON tt.tool_id = tl.tool_id
    LEFT JOIN training_types tty ON t.training_id = tty.training_id
    LEFT JOIN types ty ON tty.type_id = ty.type_id
    WHERE t.training_id = :id
    GROUP BY t.training_id
    LIMIT 1
");
$stmt->bindValue(':id', $training_id, PDO::PARAM_INT);
$stmt->execute();
$training = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$training) {
    exit("データが見つかりません。");
}

// move(BLOB)をパスへ変換
$mediaPath = null;
if (!empty($training['move'])) {
    $mediaPath = base64_decode($training['move']);
}

// 説明文整形（番号付きリスト風に加工）
$description = $training['explanation'] ?? "";
$descriptionLines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $description)));
?>

<!-- ====== モーダル全体コンテナ ====== -->
<div class="training-modal-container">

    <!-- ▼ ヘッダー -->
    <div class="modal-header">
        <h2 class="modal-title"><?php echo htmlspecialchars($training['training_name'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <button class="modal-close-btn" id="modal-info-close"></button>
    </div>

    <!-- ▼ 説明タブ -->
    <div class="tab-button-area">
        <button class="tab-btn active">説明</button>
    </div>

    <!-- ▼ 種目画像/動画 -->
<?php
// move = "Back Squat.mp4" などのファイル名が入っている前提
$mediaFile = $training['move'] ?? null;
$mediaPath = null;

if ($mediaFile) {
    // トレーニング詳細画面と同じ階層のフォルダに置いている想定
    // 例：training_screen_group/php/tr_img/Back Squat.mp4
    $mediaPath = "../tr_img/" . $mediaFile;
}

// 判定して表示する
if ($mediaPath):
    $lower = strtolower($mediaPath);
?>

<?php if (preg_match('/\.(mp4|mov|webm)$/i', $lower)): ?>
    <video src="<?php echo $mediaPath; ?>" controls playsinline class="media-box"></video>

<?php elseif (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $lower)): ?>
    <img src="<?php echo $mediaPath; ?>" class="media-box" alt="トレーニング画像">

<?php endif; ?>

<?php endif; ?>



    <!-- ▼ ツール＆タイプ -->
    <div class="info-box">
        <div class="info-col">
            <span class="info-label">ツール</span>
            <span class="info-value"><?php echo $training['tools'] ?: 'なし'; ?></span>
        </div>
        <div class="info-col">
            <span class="info-label">タイプ</span>
            <span class="info-value"><?php echo $training['types'] ?: 'ー'; ?></span>
        </div>
    </div>

    <!-- ▼ 説明文（番号表示） -->
    <div class="description-area">
        <h3 class="desc-title">トレーニング方法</h3>
        <?php $i=1; foreach ($descriptionLines as $line): ?>
            <p class="desc-line">
                <span class="desc-number"><?php echo sprintf('%02d', $i++); ?></span>
                <?php echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endforeach; ?>
    </div>

</div>


<!-- ===== CSS（training_detail_modal専用） ===== -->
<style>
.training-modal-container {
    font-family: "Helvetica Neue", Arial, sans-serif;
    color: #222;
    padding: 8px 10px 20px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-title {
    font-size: 20px;
    flex-grow: 1;
    text-align: center;
}
.modal-close-btn {
    background: none;
    border: none;
    font-size: 22px;
    cursor: pointer;
}
.bookmark-icon {
    font-size: 22px;
}

.tab-button-area {
    text-align: center;
    margin: 8px 0 12px;
}
.tab-btn {
    width: 50%;
    padding: 8px;
    border-radius: 6px;
    border: 1px solid #555;
    background: #fff;
    font-size: 14px;
}
.tab-btn.active {
    background: #efefef;
}

.media-box {
    width: 100%;
    border-radius: 8px;
    margin: 10px 0;
}

.info-box {
    display: flex;
    background: #f7f7f7;
    border-radius: 8px;
    padding: 10px;
    margin-bottom: 12px;
    justify-content: space-between;
}
.info-col {
    width: 48%;
    text-align: center;
}
.info-label {
    font-size: 12px;
    color: #777;
    display: block;
}
.info-value {
    font-size: 14px;
    font-weight: bold;
}

.description-area {
    padding: 4px;
}
.desc-title {
    font-size: 15px;
    margin-bottom: 6px;
}
.desc-line {
    font-size: 14px;
    margin: 5px 0;
    line-height: 1.5em;
    display: flex;
}
.desc-number {
    color: #007aff;
    font-weight: bold;
    margin-right: 8px;
}
</style>
