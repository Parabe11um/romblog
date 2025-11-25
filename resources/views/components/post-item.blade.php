@props(['post', 'mainCategory' => null])
<article class="bg-white post-item flex flex-col shadow my-4">
    <!-- Article Image -->
    <a href="{{route('view', $post)}}" class="hover:opacity-75">
        <img src="{{$post->getThumbnail()}}" alt="{{$post->title}}" class="aspect-[4/3] p-3 w-full object-cover">
    </a>
    <div class="bg-white flex flex-col justify-start p-6">
        <div class="flex gap-4">
            @php
                // Убираем повтор
                $otherCategories = $post->categories->filter(fn($cat) => $mainCategory && $cat->id !== $mainCategory->id);
            @endphp

            @if($mainCategory)
                <a href="{{ route('by-category', $mainCategory->slug) }}" class="text-blue-700 text-sm font-bold uppercase pb-4">
                    {{ $mainCategory->title }}
                </a>
            @endif

            @foreach($otherCategories as $category)
                <a href="{{ route('category', $category->slug) }}" class="text-blue-700 text-sm font-bold uppercase pb-4">
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
