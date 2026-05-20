<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Organisasi Diaktifkan</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f5; margin: 0; padding: 0; color: #3f3f46; }
        .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .header { background: #10b981; padding: 30px; text-align: center; color: white; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 600; }
        .content { padding: 40px 30px; }
        .content p { font-size: 16px; line-height: 1.6; color: #52525b; margin-bottom: 20px; }
        .success-box { background: #ecfdf5; border-left: 4px solid #10b981; padding: 15px 20px; margin: 25px 0; border-radius: 0 8px 8px 0; }
        .success-title { font-weight: 600; color: #065f46; margin-bottom: 5px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
        .success-text { color: #047857; font-size: 15px; margin: 0; font-weight: 500; }
        .footer { background: #fafafa; padding: 20px; text-align: center; border-top: 1px solid #e4e4e7; }
        .footer p { margin: 0; font-size: 13px; color: #a1a1aa; }
        .button { display: inline-block; padding: 12px 24px; background-color: #083D56; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Akses Dipulihkan!</h1>
        </div>
        <div class="content">
            <p>Halo Pengurus Organisasi <strong>{{ $orgName }}</strong>,</p>
            <p>Kabar baik! Kami memberitahukan bahwa akun organisasi Anda kini telah <strong>berhasil diaktifkan kembali</strong> oleh Administrator Sistem MoneFlo.</p>
            
            <div class="success-box">
                <div class="success-title">Status Saat Ini:</div>
                <p class="success-text">Aktif Normal. Semua pembatasan akses telah dicabut.</p>
            </div>
            
            <p>Anda dan seluruh anggota organisasi kini sudah dapat kembali login ke dashboard, mencatat transaksi, serta mengelola fitur-fitur lainnya seperti sedia kala.</p>
            
            <center>
                <a href="{{ url('/') }}" class="button">Masuk ke Dashboard</a>
            </center>
        </div>
        <div class="footer">
            <p>Pesan ini dikirim secara otomatis oleh Sistem MoneFlo.<br>Mohon tidak membalas email ini.</p>
        </div>
    </div>
</body>
</html>
