// ================================
//  トレーニング詳細モーダル処理
// ================================

document.addEventListener("DOMContentLoaded", () => {
    console.log("training_detail_modal.js loaded");

    document.querySelectorAll(".info-icon").forEach(button => {
        button.addEventListener("click", async () => {

            const trainingId = button.dataset.trainingId;

            // training_detail_modal.php からHTML取得
            const response = await fetch(`training_detail_modal.php?training_id=${trainingId}`);
            const html = await response.text();

            const overlay = document.getElementById("detail-modal-overlay");
            const content = document.getElementById("detail-modal-content");

            // 内容を挿入
            content.innerHTML = html;

            // モーダル表示
            overlay.style.display = "flex";
            overlay.classList.add("active");
            document.body.style.overflow = "hidden";

            // 動的に追加された閉じるボタン取得
            const closeBtn = content.querySelector("#modal-info-close");
            if (closeBtn) {
                closeBtn.addEventListener("click", () => {
                    overlay.style.display = "none";
                    overlay.classList.remove("active");
                    document.body.style.overflow = "";
                });
            }

            // 背景クリックで閉じる
            overlay.addEventListener("click", e => {
                if (e.target === overlay) {
                    overlay.style.display = "none";
                    overlay.classList.remove("active");
                    document.body.style.overflow = "";
                }
            });
        });
    });
});
