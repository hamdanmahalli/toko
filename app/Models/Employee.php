<?php

namespace App\Models;

use App\Enums\PayrollType;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

#[
    Fillable([
        'user_id', 'shop_id', 'position_id', 'nip', 'nama', 'telepon', 'email', 'foto',
        'qr_version', 'tanggal_masuk', 'tanggal_keluar',
        'tipe_payroll', 'gaji_harian', 'tarif_jam',
        'aktif', 'boleh_presensi', 'catatan',
    ]),
]
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'qr_version' => 'integer',
            'tanggal_masuk' => 'date',
            'tanggal_keluar' => 'date',
            'tipe_payroll' => PayrollType::class,
            'gaji_harian' => 'decimal:2',
            'tarif_jam' => 'decimal:2',
            'aktif' => 'boolean',
            'boleh_presensi' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function shiftAssignments(): HasMany
    {
        return $this->hasMany(EmployeeShift::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function overtimeRequests(): HasMany
    {
        return $this->hasMany(OvertimeRequest::class);
    }

    public function payrollDetails(): HasMany
    {
        return $this->hasMany(PayrollDetail::class);
    }

    public function cashBooks(): HasMany
    {
        return $this->hasMany(CashBook::class);
    }

    /** Absensi approved lewat pengajuan izin/cuti/dinas pada tanggal tertentu. */
    public function approvedLeaves(): HasMany
    {
        return $this->leaveRequests()
            ->where('status', 'approved');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    public function scopeDiToko(Builder $query, int|Shop $shop): Builder
    {
        return $query->where('shop_id', $shop instanceof Shop ? $shop->id : $shop);
    }

    /** URL foto karyawan di disk publik; null bila belum ada foto. */
    public function fotoUrl(): ?string
    {
        return $this->foto ? Storage::disk('public')->url($this->foto) : null;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->nama)) ?: [];

        if ($parts === [] || $parts[0] === '') {
            return '?';
        }

        // Nama tunggal seperti "Lutfi" tetap informatif dengan dua huruf.
        if (count($parts) === 1) {
            return mb_strtoupper(mb_substr((string) $parts[0], 0, 2));
        }

        return mb_strtoupper(
            mb_substr((string) $parts[0], 0, 1)
            .mb_substr((string) end($parts), 0, 1),
        );
    }

    /**
     * Naikkan versi QR untuk membatalkan semua kartu lama karyawan ini.
     */
    public function rotateQr(): self
    {
        $this->qr_version++;
        $this->save();

        return $this;
    }

    /** Penugasan shift yang berlaku pada tanggal tertentu, null bila belum ada. */
    public function shiftBerlaku(?string $tanggal = null): ?EmployeeShift
    {
        $tanggal ??= now()->toDateString();

        return $this->shiftAssignments()
            ->berlakuPada($tanggal)
            ->orderByDesc('mulai_berlaku')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Pasang template shift. Hanya satu penugasan aktif pada satu waktu;
     * penugasan lama ditutup (bukan dihapus) supaya riwayat jam kerja utuh.
     *
     * Template null berarti karyawan dilepas dari shift, bukan diberi shift baru.
     */
    public function pasangShift(?int $shiftTemplateId, ?string $mulaiBerlaku = null): void
    {
        $mulai = Carbon::parse($mulaiBerlaku ?? $this->tanggal_masuk ?? now())->startOfDay();

        $aktif = $this->shiftAssignments()->where('aktif', true)->get();

        $sekarang = $aktif->first(fn (EmployeeShift $s) => $s->berlakuPadaTanggal($mulai->toDateString()));

        // Penugasan aktif lain ikut ditutup supaya tidak ada dua shift aktif bersamaan.
        foreach ($aktif as $s) {
            if ($s === $sekarang) {
                continue;
            }

            // Penugasan yang belum mulai ditutup tepat sebelum tanggal mulainya.
            $dimulai = $s->mulai_berlaku !== null && $s->mulai_berlaku->greaterThan($mulai);

            $akhir = $dimulai
                ? $s->mulai_berlaku->copy()->subDay()
                : $mulai->copy()->subDay();

            // Jaga rentang tetap valid walau ada data ganda di tanggal yang sama.
            if (! $dimulai && $s->mulai_berlaku !== null && $akhir->lessThan($s->mulai_berlaku)) {
                $akhir = $s->mulai_berlaku->copy();
            }

            $s->update(['aktif' => false, 'selesai_berlaku' => $akhir->toDateString()]);
        }

        if ($shiftTemplateId === null) {
            if ($sekarang !== null) {
                $sekarang->update([
                    'aktif' => false,
                    'selesai_berlaku' => $mulai->copy()->subDay()->toDateString(),
                ]);
            }

            return;
        }

        if ($sekarang !== null && $sekarang->shift_template_id === $shiftTemplateId) {
            return;
        }

        if ($sekarang !== null) {
            $selesai = $mulai->copy()->subDay();

            $sekarang->update([
                'aktif' => false,
                // Jangan buat rentang yang berakhir sebelum mulai penugasan lama.
                'selesai_berlaku' => $sekarang->mulai_berlaku !== null && $sekarang->mulai_berlaku->greaterThan($selesai)
                    ? $sekarang->mulai_berlaku->toDateString()
                    : $selesai->toDateString(),
            ]);
        }

        $this->shiftAssignments()->create([
            'shift_template_id' => $shiftTemplateId,
            'mulai_berlaku' => $mulai->toDateString(),
            'aktif' => true,
        ]);
    }
}
