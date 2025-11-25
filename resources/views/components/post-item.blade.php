@props(['post', 'mainCategory' => null])

@php
    $post->loadMissing('categories');
@endphp
<article class="bg-white post-item flex flex-col shadow my-4">
    <!-- Article Image -->
    <a href="{{route('view', $post)}}" class="hover:opacity-75">
        <img src="{{$post->getThumbnail()}}" alt="{{$post->title}}" class="aspect-[4/3] p-3 w-full object-cover">
    </a>
    <div class="bg-white flex flex-col justify-start p-6">
        <div class="flex gap-4">

            @php
                // Если на странице категории — передан mainCategory
                if ($mainCategory) {
                    // Показываем сначала основную категорию
                    echo '<a href="'.route('by-category', $mainCategory->slug).'"
                             class="text-blue-700 text-sm font-bold uppercase pb-4">'
                            .$mainCategory->title.
                         '</a>';

                    // Остальные категории этого поста, исключая основную
                    $categories = $post->categories->where('id', '!=', $mainCategory->id);
                } else {
                    // Если mainCategory НЕ передан — просто выводим все категории
                    $categories = $post->categories;
                }
            @endphp

            @foreach($categories as $category)
                <a href="{{ route('by-category', $category->slug) }}"
                   class="text-blue-700 text-sm font-bold uppercase pb-4">
                    {{ $category->title }}
                </a>
            @endforeach
        </div>

        <a href="{{route('view', $post)}}" class="text-3xl font-bold hover:text-gray-700 pb-4">
            {{$post->title}}
        </a>

        <p href="#" class="text-sm pb-3">
            Опубликовано {{$post->getFormattedDate()}}
        </p>

        <a href="{{route('view', $post)}}" class="pb-6">
            {{$post->shortBody()}}
        </a>
        <a  href="{{route('view', $post)}}" class="uppercase text-gray-800 hover:text-black">Читать <i
                class="fas fa-arrow-right"></i></a>
    </div>
</article>
