<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class KirimPasswordBaru extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $passwordBaru  dikirim sekali lewat email, lalu sebaiknya diganti oleh pemiliknya
     */
    public function __construct(
        public User $akun,
        public string $passwordBaru,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Password akun '.$this->akun->name.' diatur ulang',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-baru',
        );
    }
}
