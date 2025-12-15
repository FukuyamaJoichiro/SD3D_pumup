/**
 * profile.js
 * プロフィール画面用 共通JS
 * - モーダルの開閉
 * - Escキーで閉じる
 * - 背景クリックで閉じる
 */

document.addEventListener('DOMContentLoaded', () => {

  /* ===== モーダルを開く ===== */
  document.querySelectorAll('[data-open-modal]').forEach(button => {
    button.addEventListener('click', () => {
      const modalId = button.dataset.openModal;
      const modal = document.getElementById(modalId);
      if (!modal) return;

      modal.style.display = 'flex';
      document.body.style.overflow = 'hidden'; // 背景スクロール防止
    });
  });

  /* ===== モーダルを閉じる（ボタン） ===== */
  document.querySelectorAll('[data-close-modal]').forEach(button => {
    button.addEventListener('click', () => {
      const modal = button.closest('.modal-overlay');
      if (!modal) return;

      modal.style.display = 'none';
      document.body.style.overflow = '';
    });
  });

  /* ===== 背景クリックで閉じる ===== */
  document.querySelectorAll('.modal-overlay').forEach(modal => {
    modal.addEventListener('click', e => {
      if (e.target === modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
      }
    });
  });

  /* ===== Escキーで閉じる ===== */
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.style.display = 'none';
      });
      document.body.style.overflow = '';
    }
  });

});
