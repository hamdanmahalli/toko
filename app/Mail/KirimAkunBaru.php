<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class KirimAkunBaru extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $passwordAwal  dikirim sekali lewat email, lalu langsung diubah oleh pemiliknya
     */
    public function __construct(
        public User $akun,
        public string $passwordAwal,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Akun absensi Anda sudah dibuat',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.akun-baru',
        );
    }
}
