// ブログ詳細ページ：著者の他のブログを追加で読み込む
document.addEventListener('DOMContentLoaded', function () {
    const loadMoreBtn = document.getElementById('load-more-btn');
    const authorBlogsContainer = document.getElementById('author-blogs-container');
    const loadMoreContainer = document.getElementById('load-more-container');

    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function () {
            const blogId = this.getAttribute('data-blog-id');
            const offset = parseInt(this.getAttribute('data-offset'), 10);
            const url = `/api/blogs/${blogId}/author-blogs?offset=${offset}`;

            // ボタンを無効化
            this.disabled = true;
            this.textContent = '読み込み中...';

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    // 取得したブログをDOMに追加
                    authorBlogsContainer.insertAdjacentHTML('beforeend', data.html);

                    // offsetを更新
                    const newOffset = offset + data.blogs_count;
                    loadMoreBtn.setAttribute('data-offset', newOffset);

                    // これ以上ブログがない場合はボタンを非表示
                    if (!data.has_more) {
                        loadMoreContainer.style.display = 'none';
                    } else {
                        // ボタンを再有効化
                        loadMoreBtn.disabled = false;
                        loadMoreBtn.textContent = 'もっと見る';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // エラー時もボタンを再有効化
                    loadMoreBtn.disabled = false;
                    loadMoreBtn.textContent = 'もっと見る';
                });
        });
    }
});
