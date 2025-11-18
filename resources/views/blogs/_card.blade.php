<div class="w-full md:w-1/3 p-3">
    <article
        class="border rounded-lg overflow-hidden shadow hover:shadow-lg transition-shadow h-full">
        <a href="{{ route('blogs.show', $blog) }}">
            <div class="relative h-48">
                <img class="w-full h-48 object-cover"
                    src="{{ $blog->image ? asset('storage/' . $blog->image) : asset('storage/dummy.jpg') }}"
                    alt="{{ $blog->title }}">
            </div>
        </a>
        <div class="p-4">
            @if ($blog->category)
                <span class="text-xs text-gray-400 bg-gray-100 py-2 px-3">
                    {{ $blog->category->name }}
                </span>
            @endif
            <a href="{{ route('blogs.show', $blog) }}">
                <h3 class="text-lg font-semibold mt-4 mb-2">
                    {{ $blog->title }}
                </h3>
                <p class="text-gray-500 text-sm mb-4">
                    {{ $blog->excerpt }}
                </p>
            </a>
            <div class="flex flex-wrap gap-2">
                @foreach ($blog->cats->take(3) as $cat)
                    <span class="bg-gray-100 text-gray-400 text-xs py-1 px-2">
                        #{{ $cat->name }}
                    </span>
                @endforeach
            </div>
            <div class="mt-3 text-right text-sm font-semibold">
                {{ $blog->user ? $blog->user->name : '店長' }}
            </div>
        </div>
    </article>
</div>
