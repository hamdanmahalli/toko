@props(['status'])

@php
    $warna = match ($status) {
        \App\Enums\RequestStatus::Approved => 'bg-brand-100 text-brand-800',
        \App\Enums\RequestStatus::Rejected => 'bg-rose-100 text-rose-700',
        \App\Enums\RequestStatus::Cancelled => 'bg-slate-100 text-slate-500',
        default => 'bg-amber-100 text-amber-700',
    };
@endphp

<span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $warna }}">
    {{ $status->label() }}
</span>
