<?php

use Illuminate\Support\Facades\Blade;

it('preserves normal search highlighting', function () {
    $html = Blade::render(
        '<x-search-highlight :title="$title" :query="$query" />',
        [
            'title' => 'Healthy food',
            'query' => 'food',
        ]
    );

    expect($html)
        ->toContain('Healthy ')
        ->toContain('<span class="bg-yellow-300">food</span>');
});

it('escapes html in post titles', function () {
    $html = Blade::render(
        '<x-search-highlight :title="$title" :query="$query" />',
        [
            'title' => 'Healthy <b>food</b>',
            'query' => 'food',
        ]
    );

    expect($html)
        ->not->toContain('<b>')
        ->toContain('&lt;b&gt;')
        ->toContain('&lt;/b&gt;');
});

it('escapes special characters in highlighted queries', function () {
    $html = Blade::render(
        '<x-search-highlight :title="$title" :query="$query" />',
        [
            'title' => 'Food & health',
            'query' => '&',
        ]
    );

    expect($html)
        ->toContain('<span class="bg-yellow-300">&amp;</span>');
});

it('handles an empty search query', function () {
    $html = Blade::render(
        '<x-search-highlight :title="$title" :query="$query" />',
        [
            'title' => 'Healthy food',
            'query' => '',
        ]
    );

    expect($html)
        ->toContain('Healthy food')
        ->not->toContain('bg-yellow-300');
});

it('rejects array search parameters', function () {
    $this->getJson('/search?q%5B%5D=food')
        ->assertStatus(422);
});

it('rejects excessively long search parameters', function () {
    $this->getJson('/search?q=' . str_repeat('a', 201))
        ->assertStatus(422);
});

it('accepts a normal search query', function () {
    $this->get('/search?q=food')
        ->assertOk();
});

it('accepts an empty search query', function () {
    $this->get('/search')
        ->assertOk();
});
