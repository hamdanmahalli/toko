@props(['kolom' => [], 'baris' => [], 'rapi' => true])

{{-- Tabel data ringkas. $kolom berisi label, $baris berisi array isi per baris.
     Bila panjang $baris tidak sama dengan $kolom, sel yang tidak ada dilewati
     supaya tabel tidak fatal kalau datanya belum lengkap. --}}
<div class="overflow-x-auto">
    <table class="w-full min-w-[34rem] text-left text-[13px]">
        <thead class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-medium text-slate-400">
            <tr>
                @foreach ($kolom as $label)
                    <th class="px-3 py-2.5 font-medium">{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($baris as $sel)
                <tr class="align-top {{ $rapi ? 'odd:bg-slate-50/40' : '' }}">
                    @foreach (array_keys($kolom) as $i)
                        <td class="px-3 py-2.5 text-slate-600">{!! $sel[$i] ?? '' !!}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td class="px-3 py-4 text-center text-slate-400" colspan="{{ max(1, count($kolom)) }}">
                        Belum ada data.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>