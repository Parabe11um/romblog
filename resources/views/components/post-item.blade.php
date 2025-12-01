@props(['post', 'mainCategory' => null])

@php
    $post->loadMissing('categories');

    // Выбор категорий
    if ($mainCategory) {
        $categories = $post->categories->where('id', '!=', $mainCategory->id);
    } else {
        $categories = $post->categories;
    }
@endphp

<article class="bg-white post-item flex flex-col shadow my-4">

    <!-- Article Image -->
    <a href="{{ route('view', $post) }}" class="hover:opacity-75">
        <img src="{{ $post->getThumbnail() }}"
             alt="{{ $post->title }}"
             class="p-3 w-full object-contain">
    </a>

    <!-- Content -->
    <div class="bg-white flex flex-col p-6 h-full">

        <!-- Categories -->
        <div class="flex gap-4 flex-wrap">

            @if($mainCategory)
                <a href="{{ route('by-category', $mainCategory->slug) }}"
                   class="text-blue-700 text-sm font-bold uppercase pb-4">
                    {{ $mainCategory->title }}
                </a>
            @endif

            @foreach($categories as $category)
                <a href="{{ route('by-category', $category->slug) }}"
                   class="text-blue-700 text-sm font-bold uppercase pb-4">
                    {{ $category->title }}
                </a>
            @endforeach

        </div>

        <!-- Date -->
        <p class="text-sm pb-3">
            Опубликовано {{ $post->getFormattedDate() }}
        </p>

        <!-- Short body -->
        <a href="{{ route('view', $post) }}" class="pb-6">
            {{ $post->shortBody() }}
        </a>

        <!-- READ button at the bottom -->
        <a href="{{ route('view', $post) }}"
           class="uppercase text-gray-800 hover:text-black mt-auto">
            Читать <i class="fas fa-arrow-right"></i>
        </a>

    </div>
</article>
