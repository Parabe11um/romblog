@props(['html' => ''])

{!! \Illuminate\Support\Str::sanitizeHtml((string) ($html ?? '')) !!}
