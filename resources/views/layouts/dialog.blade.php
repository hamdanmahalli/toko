{{--
    Dialog kustom sebagai pengganti popup bawaan browser (confirm/alert/prompt)
    supaya tampilannya seragam dengan aplikasi. Markup statis ditulis di sini
    agar kelas Tailwind tetap terbaca saat build; isi teks dan tombolnya diatur
    lewat window.TokoDialog di resources/js/app.js.
--}}
<div id="dialog" class="fixed inset-0 z-[60] hidden" role="presentation">
    <div data-dialog-tutup class="absolute inset-0 bg-brand-950/40 backdrop-blur-sm"></div>

    <div class="absolute inset-x-0 bottom-0 flex justify-center p-4 sm:inset-0 sm:items-center">
        <div data-dialog-kartu role="dialog" aria-modal="true" aria-labelledby="dialog-judul"
             class="masuk w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl safe-bottom">
            <div class="flex items-start gap-3">
                <span data-dialog-ikon class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-full"></span>

                <div class="min-w-0 flex-1">
                    <p id="dialog-judul" data-dialog-judul class="font-display text-[15px] leading-snug text-slate-900"></p>
                    <p data-dialog-pesan class="mt-1 text-[13px] leading-relaxed text-slate-600"></p>
                </div>
            </div>

            <textarea data-dialog-input rows="3"
                      class="mt-3 hidden w-full resize-none rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-brand-400"></textarea>
            <p data-dialog-galat class="mt-1.5 hidden text-[12px] font-medium text-merah-600"></p>

            <div class="mt-4 flex gap-2">
                <button type="button" data-dialog-batal
                        class="flex-1 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                    Batal
                </button>
                <button type="button" data-dialog-ok
                        class="flex-1 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:brightness-105 active:scale-[.99]">
                    Lanjut
                </button>
            </div>
        </div>
    </div>
</div>
