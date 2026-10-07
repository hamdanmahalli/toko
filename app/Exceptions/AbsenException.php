<?php

namespace App\Exceptions;

use App\Enums\AbsenGagalReason;
use Exception;
use Throwable;

/**
 * Penolakan domain yang selalu disertai alasan yang bisa ditampilkan ke pengguna.
 */
class AbsenException extends Exception
{
    public function __construct(
        public readonly AbsenGagalReason $reason,
        ?string $pesan = null,
        public readonly array $konteks = [],
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($pesan ?? $reason->pesan(), $code, $previous);
    }

    public static function dari(AbsenGagalReason $reason, array $konteks = []): self
    {
        return new self($reason, null, $konteks);
    }

    public function alasan(): string
    {
        return $this->reason->value;
    }

    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'sukses' => false,
                'alasan' => $this->reason->value,
                'pesan' => $this->getMessage(),
                'konteks' => $this->konteks,
            ], 422);
        }

        return null;
    }
}
