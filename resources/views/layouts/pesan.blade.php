@php
    $pesan = match (true) {
        session('sukses') => ['sukses', session('sukses')],
        session('galat') => ['galat', session('galat')],
        session('pesan') => ['pesan', session('pesan')],
        default => null,
    };
@endphp

@if ($pesan)
    <div @class([
        'mb-4 flex items-start gap-2.5 rounded-xl px-4 py-3 text-[13px]',
        'bg-brand-50 text-brand-900 ring-1 ring-brand-100' => $pesan[0] === 'sukses',
        'bg-rose-50 text-rose-900 ring-1 ring-rose-100' => $pesan[0] === 'galat',
        'bg-amber-50 text-amber-900 ring-1 ring-amber-100' => $pesan[0] === 'pesan',
    ]) role="status">
        <svg class="mt-px h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            @if ($pesan[0] === 'sukses')
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            @else
                <path stroke-linecap="round" d="M12 8v5m0 3.5v.01M10.3 3.9 2.5 17.5A2 2 0 0 0 4.2 20.5h15.6a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/>
            @endif
        </svg>
        <span>{{ $pesan[1] }}</span>
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-[13px] text-rose-900 ring-1 ring-rose-100" role="alert">
        <ul class="space-y-0.5">
            @foreach ($errors->all() as $isi)
                <li>{{ $isi }}</li>
            @endforeach
        </ul>
    </div>
@endif