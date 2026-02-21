@php
    $appName = config('app.name');
    $logo = storage_path('app/public/logo.png');
@endphp

@if(file_exists($logo))
    <img src="{{ asset('storage/logo.png') }}" alt="{{ $appName }}" class="h-8">
@else
    <div class="text-xl font-bold tracking-tight">
        {{ $appName }}
    </div>
@endif