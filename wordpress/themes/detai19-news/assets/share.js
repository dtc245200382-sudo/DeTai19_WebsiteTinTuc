/* A manual copy field remains available if clipboard permission is denied. */
document.querySelectorAll('.share-box').forEach(function (box) {
    const button = box.querySelector('.copy-link');
    const input = box.querySelector('input');
    const status = box.querySelector('.share-status');
    button.hidden = false;
    button.addEventListener('click', async function () {
        input.focus();
        input.select();
        try {
            if (!navigator.clipboard || !window.isSecureContext) throw new Error('Clipboard unavailable');
            await navigator.clipboard.writeText(input.value);
            status.textContent = 'Đã sao chép liên kết bài viết.';
        } catch (error) {
            status.textContent = 'Đã chọn liên kết. Nhấn Ctrl+C hoặc chạm giữ để sao chép.';
        }
    });
});
