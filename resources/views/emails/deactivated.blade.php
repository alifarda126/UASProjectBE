<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Akun Dinonaktifkan</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; margin: 0; padding: 40px 20px; color: #334155; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .header { background: #94a3b8; padding: 30px 20px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 24px; font-weight: 600; }
        .content { padding: 40px 30px; }
        .content p { font-size: 16px; line-height: 1.6; margin-bottom: 20px; }
        .btn-container { text-align: center; margin: 30px 0; }
        .btn { display: inline-block; padding: 14px 28px; background-color: #1e293b; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px; transition: background-color 0.2s; }
        .btn:hover { background-color: #0f172a; }
        .footer { text-align: center; padding: 20px; color: #94a3b8; font-size: 14px; border-top: 1px solid #f1f5f9; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Akun Dinonaktifkan</h1>
        </div>
        <div class="content">
            <p>Halo Pengurus <strong>{{ $orgName }}</strong>,</p>
            <p>Sistem kami mendeteksi bahwa tidak ada aktivitas di akun organisasi Anda selama lebih dari 30 hari. Untuk menjaga keamanan dan efisiensi sistem, akun organisasi Anda saat ini telah dinonaktifkan secara otomatis.</p>
            <p>Anda masih dapat mengakses akun Anda. Silakan masuk kembali ke dashboard MoneFlo dan klik tombol aktivasi untuk melanjutkan penggunaan.</p>
            <div class="btn-container">
                <a href="{{ config('app.frontend_url', 'http://localhost:5173') }}/login" class="btn">Masuk & Aktifkan</a>
            </div>
            <p>Jika Anda memiliki pertanyaan atau butuh bantuan, jangan ragu untuk membalas email ini.</p>
            <p>Terima kasih,<br><strong>Tim MoneFlo</strong></p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} MoneFlo. All rights reserved.
        </div>
    </div>
</body>
</html>
