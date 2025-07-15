<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $metaTitle ?: 'fitlife-blog.ru' }}</title>
    <meta name="author" content="fitlife-blog.ru">
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="yandex-verification" content="b3d4e1b5780d98f2" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=arsenal:400,400i,700,700i" rel="stylesheet" />

    @livewireStyles
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 font-family-karla">


<!-- Text Header -->
<header class="max-sm:hidden w-full container mx-auto">
    <div class="flex flex-col items-center py-12">
        <a class="text-center rounded-md font-bold border-gray-100 p-4 text-gray-800 uppercase hover:text-gray-700 text-7xl" href="{{route('home')}}">
           <span class="font-light">fitlife-blog</span>
        </a>
        <p class="text-lg text-gray-600">
            {{ \App\Models\TextWidget::getTitle('header') }}
        </p>
    </div>
</header>

<!-- Topic Nav -->
<nav class="w-full py-4 border-t border-b bg-gray-100" x-data="{ open: false }">
    <div class="block sm:hidden">
        <a
            href="#"
            class="block md:hidden text-base font-bold uppercase text-center flex justify-center items-center"
            @click="open = !open"
        >
            Меню <i :class="open ? 'fa-chevron-down': 'fa-chevron-up'" class="fas ml-2"></i>
        </a>
    </div>
    <div :class="open ? 'block': 'hidden'" class="w-full flex-grow sm:flex sm:items-center sm:w-auto">
        <div
            class="w-full container mx-auto flex flex-col sm:flex-row items-center justify-between text-sm font-bold uppercase mt-0 px-6 py-2">
            <div class="flex max-sm:flex-col">
                <a href="{{route('home')}}" class="hover:bg-blue-600 hover:text-white rounded py-2 px-4 mx-2">Главная</a>
                @foreach($menuCategories as $category)
                    <div class="relative group">
                        <a href="{{ route('by-category', $category) }}"
                           class="hover:bg-blue-600 hover:text-white rounded py-2 px-4 mx-2 block">
                            {{ $category->title }}
                        </a>

                        @if($category->children->isNotEmpty())
                            <div class="absolute left-0 top-full mt-1 hidden group-hover:block z-50 bg-white shadow-lg rounded">
                                @foreach($category->children as $child)
                                    <a href="{{ route('by-category', $child) }}"
                                       class="block whitespace-nowrap px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">
                                        {{ $child->title }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach

                <a href="{{route('about-us')}}" class="hover:bg-blue-600 hover:text-white rounded py-2 px-4 mx-2">О блоге</a>
                <a class="px-4 py-2 mx-2 hover:bg-blue-600 hover:text-white rounded inline-flex items-center space-x-2"
                   href="https://t.me/fitlifeblog" target="_blank" rel="noopener">
                    <img src="{{ asset('storage/telegram-brands-solid.svg') }}" alt="Telegram" class="h-5 w-5" />
                    <span>Мы в Телеграм</span>
                </a>
            </div>

{{--            <div class="flex max-sm:mt-5 items-center max-sm:flex-col">--}}
{{--                @auth--}}
{{--                    <div class="flex max-sm:mt-5 sm:items-center sm:ml-6">--}}
{{--                        <x-dropdown align="right" width="48">--}}
{{--                            <x-slot name="trigger">--}}
{{--                                <button--}}
{{--                                    class="hover:bg-blue-600 hover:text-white flex items-center rounded py-2 px-4 mx-2">--}}
{{--                                    <div>{{ Auth::user()->name }}</div>--}}

{{--                                    <div class="ml-1">--}}
{{--                                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg"--}}
{{--                                             viewBox="0 0 20 20">--}}
{{--                                            <path fill-rule="evenodd"--}}
{{--                                                  d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"--}}
{{--                                                  clip-rule="evenodd"/>--}}
{{--                                        </svg>--}}
{{--                                    </div>--}}
{{--                                </button>--}}
{{--                            </x-slot>--}}

{{--                            <x-slot name="content">--}}
{{--                                <x-dropdown-link :href="route('profile.edit')">--}}
{{--                                    {{ __('Профиль') }}--}}
{{--                                </x-dropdown-link>--}}

{{--                                <!-- Authentication -->--}}
{{--                                <form method="POST" action="{{ route('logout') }}">--}}
{{--                                    @csrf--}}

{{--                                    <x-dropdown-link :href="route('logout')"--}}
{{--                                                     onclick="event.preventDefault();--}}
{{--                                                this.closest('form').submit();">--}}
{{--                                        {{ __('Выйти') }}--}}
{{--                                    </x-dropdown-link>--}}
{{--                                </form>--}}
{{--                            </x-slot>--}}
{{--                        </x-dropdown>--}}
{{--                    </div>--}}
{{--                @else--}}
{{--                    <a href="{{route('login')}}"--}}
{{--                       class="hover:bg-blue-600 hover:text-white rounded py-2 px-4 mx-2">Войти</a>--}}
{{--                    <a href="{{route('register')}}" class="bg-blue-600 text-white rounded py-2 px-4 mx-2">Зарегистрироваться</a>--}}
{{--                @endauth--}}
{{--            </div>--}}
        </div>
    </div>
</nav>

    <div class="w-full container mx-auto  text-sm font-bold uppercase mt-0 px-6 py-2">
        <form method="get" action="{{route('search')}}">
            <input name="q" value="{{request()->get('q')}}"
                   class="block w-full rounded-md border-0 px-3.5 py-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset sm:text-sm sm:leading-6 font-medium"
                   placeholder="Поиск"/>
        </form>
    </div>


<div class="container mx-auto py-6">

    {{ $slot }}

</div>

<footer class="w-full border-t bg-white pb-12">
    <div class="w-full container mx-auto flex flex-col items-center">
        <div class="uppercase py-6">&copy;Fitlife-blog.ru</div>
    </div>
</footer>

@livewireScripts
<!-- Yandex.Metrika counter -->
<script type="text/javascript" >
    (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
        for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
        k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
    (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");

    ym(99363222, "init", {
        clickmap:true,
        trackLinks:true,
        accurateTrackBounce:true,
        webvisor:true
    });
</script>
<noscript><div><img src="https://mc.yandex.ru/watch/99363222" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
<!-- /Yandex.Metrika counter -->
</body>
</html>
