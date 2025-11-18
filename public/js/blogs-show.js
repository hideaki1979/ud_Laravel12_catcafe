// ブログ詳細ページ：著者の他のブログを追加で読み込む
document.addEventListener('DOMContentLoaded', function () {
    const loadMoreBtn = document.getElementById('load-more-btn');
    const authorBlogsContainer = document.getElementById('author-blogs-container');
    const loadMoreContainer = document.getElementById('load-more-container');

    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function () {
            const blogId = this.getAttribute('data-blog-id');
            const offset = parseInt(this.getAttribute('data-offset'));
            const url = `/api/blogs/${blogId}/author-blogs?offset=${offset}`;

            // ボタンを無効化
            this.disabled = true;
            this.textContent = '読み込み中...';

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    // 取得したブログをDOMに追加
                    data.blogs.forEach(function (blog) {
                        const blogHtml = `
                            <div class="w-full md:w-1/3 p-3">
                                <article class="border rounded-lg overflow-hidden shadow hover:shadow-lg transition-shadow h-full">
                                    <a href="${blog.url}">
                                        <div class="relative h-48">
                                            <img class="w-full h-48 object-cover" src="${blog.image}" alt="${blog.title}">
                                        </div>
                                    </a>
                                    <div class="p-4">
                                        ${blog.category ? `<span class="text-xs text-gray-400 bg-gray-100 py-2 px-3">${blog.category}</span>` : ''}
                                        <a href="${blog.url}">
                                            <h3 class="text-lg font-semibold mt-4 mb-2">${blog.title}</h3>
                                            <p class="text-gray-500 text-sm mb-4">${blog.excerpt}</p>
                                        </a>
                                        <div class="flex flex-wrap gap-2">
                                            ${blog.cats.map(cat => `<span class="bg-gray-100 text-gray-400 text-xs py-1 px-2">#${cat}</span>`).join('')}
                                        </div>
                                        <div class="mt-3 text-right text-sm font-semibold">${blog.user_name}</div>
                                    </div>
                                </article>
                            </div>
                        `;
                        authorBlogsContainer.insertAdjacentHTML('beforeend', blogHtml);
                    });

                    // offsetを更新
                    const newOffset = offset + data.blogs.length;
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
