<!DOCTYPE html>
<html lang="id">
<body style="margin:0;padding:0;background:#f8fafc;font-family:'Segoe UI',Arial,sans-serif;">
    <div style="max-width:480px;margin:24px auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;">
        <h1 style="font-size:18px;color:#0f172a;margin:0 0 12px;">Password Diatur Ulang</h1>
        <p style="font-size:14px;color:#334155;line-height:1.6;margin:0 0 16px;">
            Halo, <strong>{{ $akun->name }}</strong>.
        </p>
        <p style="font-size:14px;color:#334155;line-height:1.6;margin:0 0 16px;">
            Password akun Anda baru saja diatur ulang oleh admin. Masuk dengan
            <strong>{{ $akun->email }}</strong> atau username
            <strong>{{ $akun->username }}</strong> menggunakan password baru ini:
        </p>
        <p style="font-size:22px;font-weight:700;letter-spacing:1px;background:#f1f5f9;border-radius:8px;padding:12px;text-align:center;color:#0f172a;">
            {{ $passwordBaru }}
        </p>
        <p style="font-size:14px;color:#334155;line-height:1.6;margin:16px 0 0;">
            Demi keamanan, segera ganti password ini lewat menu Profil setelah masuk.
        </p>
        <p style="font-size:13px;color:#64748b;line-height:1.5;margin:16px 0 0;">
            Kalau Anda tidak meminta pengaturan ulang ini, segera hubungi admin toko.
        </p>
    </div>
</body>
</html>
