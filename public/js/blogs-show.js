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
                    data.blogs.forEach(function (blog) {
                        // 取得したブログをDOMに追加
                        const blogCardWrapper = document.createElement('div');
                        blogCardWrapper.className = 'w-full md:w-1/3 p-3';

                        const article = document.createElement('article');
                        article.classList = 'border rounded-lg overflow-hidden shadow hover:shadow-lg transition-shadow h-full';

                        const linkImage = document.createElement('a');
                        linkImage.href = blog.url;

                        const imageContainer = document.createElement('div');
                        imageContainer.className = 'relative h-48';

                        const img = document.createElement('img');
                        img.className = 'w-full h-48 object-cover';
                        img.src = blog.image;
                        img.alt = blog.title;

                        imageContainer.appendChild(img);
                        linkImage.appendChild(imageContainer);
                        article.appendChild(linkImage);

                        const contentDiv = document.createElement('div');
                        contentDiv.className = 'p-4';

                        if (blog.category) {
                            const categorySpan = document.createElement('span');
                            categorySpan.className = 'text-xs text-gray-400 bg-gray-100 py-2 px-3';

                            categorySpan.textContent = blog.category;
                            contentDiv.appendChild(categorySpan);
                        }

                        const linkTitleExcerpt = document.createElement('a');
                        linkTitleExcerpt.href = blog.url;

                        const h3 = document.createElement('h3');
                        h3.className = 'text-lg font-semibold mt-4 mb-2';
                        h3.textContent = blog.title;
                        const pExcerpt = document.createElement('p');
                        pExcerpt.className = 'text-gray-500 text-sm mb-4';
                        pExcerpt.textContent = blog.excerpt;

                        linkTitleExcerpt.appendChild(h3);
                        linkTitleExcerpt.appendChild(pExcerpt);
                        contentDiv.appendChild(linkTitleExcerpt);
                        const catsDiv = document.createElement('div');
                        catsDiv.className = 'flex flex-wrap gap-2';
                        blog.cats.forEach(function (cat) {
                            const catSpan = document.createElement('span');
                            catSpan.className = 'bg-gray-100 text-gray-400 text-xs py-1 px-2';
                            catSpan.textContent = `#${cat}`;
                            catsDiv.appendChild(catSpan);
                        });
                        contentDiv.appendChild(catsDiv);
                        const userNameDiv = document.createElement('div');
                        userNameDiv.className = 'mt-3 text-right text-sm font-semibold';
                        userNameDiv.textContent = blog.user_name;
                        contentDiv.appendChild(userNameDiv);
                        article.appendChild(contentDiv);
                        blogCardWrapper.appendChild(article);
                        authorBlogsContainer.appendChild(blogCardWrapper);
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
