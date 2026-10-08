<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Username;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BuatAkunPemilik extends Command
{
    protected $signature = 'toko:buat-akun
                            {--nama= : Nama lengkap pemilik}
                            {--email= : Email untuk login}
                            {--username= : Username untuk login (dibuat otomatis dari email bila kosong)}
                            {--password= : Password (non-interaktif, khusus bootstrap deploy)}';

    protected $description = 'Membuat akun pengguna dengan peran Pemilik (password diminta secara interaktif)';

    public function handle(): int
    {
        $this->newLine();
        $this->line('  <fg=cyan>Buat akun Pemilik</>');
        $this->line('  <fg=gray>Password tidak akan pernah disimpan di dalam kode.</>');
        $this->newLine();

        $nama = (string) ($this->option('nama') ?: $this->ask('Nama lengkap'));
        $email = (string) ($this->option('email') ?: $this->ask('Email untuk login'));
        $username = (new Username)->normalisasi((string) $this->option('username'));

        $validator = Validator::make(
            ['nama' => $nama, 'email' => $email, 'username' => $username],
            [
                'nama' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
                'username' => [
                    'nullable',
                    'string',
                    'min:3',
                    'max:50',
                    'regex:/^[A-Za-z0-9_.-]+$/',
                    Rule::unique('users', 'username'),
                ],
            ],
            [
                'email.unique' => 'Email tersebut sudah terdaftar.',
                'username.unique' => 'Username tersebut sudah terdaftar.',
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error('  '.$error);
            }

            return self::FAILURE;
        }

        $password = (string) $this->option('password');
        if ($password !== '') {
            if (strlen($password) < 8) {
                $this->error('  Password minimal 8 karakter.');

                return self::FAILURE;
            }
        } else {
            $password = (string) $this->secret('Password (min. 8 karakter)');
            $konfirmasi = (string) $this->secret('Ulangi password');

            if (strlen($password) < 8) {
                $this->error('  Password minimal 8 karakter.');

                return self::FAILURE;
            }

            if ($password !== $konfirmasi) {
                $this->error('  Password tidak cocok.');

                return self::FAILURE;
            }
        }

        $user = User::create([
            'name' => $nama,
            'email' => $email,
            'username' => $username !== '' ? $username : (new Username)->dariEmail($email),
            'password' => Hash::make($password),
            'aktif' => true,
        ]);

        $user->assignRole('pemilik');

        $this->newLine();
        $this->info("  Akun {$email} (username: {$user->username}) dibuat dengan peran Pemilik.");
        $this->newLine();

        return self::SUCCESS;
    }
}
