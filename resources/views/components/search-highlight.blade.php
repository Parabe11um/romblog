@props(['title', 'query' => ''])

@php
    $parts = $query === ''
        ? [(string) $title]
        : explode($query, (string) $title);
@endphp
@foreach($parts as $part)@unless($loop->first)<span class="bg-yellow-300">{{ $query }}</span>@endunless{{ $part }}@endforeach
