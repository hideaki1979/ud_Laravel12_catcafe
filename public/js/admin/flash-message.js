(function () {
    document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('flash-inner');
        if (!el) return;

        // スライドイン（Tailwind のユーティリティクラスを操作）
        requestAnimationFrame(function () {
            el.classList.remove('-translate-y-3', 'opacity-0');
            el.classList.add('translate-y-0', 'opacity-100');
        });

        // 3秒後にスライドアウトして親要素ごと削除
        setTimeout(function () {
            el.classList.remove('translate-y-0', 'opacity-100');
            el.classList.add('-translate-y-3', 'opacity-0');
            el.addEventListener('transitionend', function () {
                const outer = document.getElementById('flash-message');
                if (outer && outer.parentNode) outer.parentNode.removeChild(outer);
            }, { once: true });
        }, 3000);
    })
})()
