<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Banding Ditolak</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f5; margin: 0; padding: 0; color: #3f3f46; }
        .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .header { background: #f97316; padding: 30px; text-align: center; color: white; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 600; }
        .content { padding: 40px 30px; }
        .content p { font-size: 16px; line-height: 1.6; color: #52525b; margin-bottom: 20px; }
        .reason-box { background: #fff7ed; border-left: 4px solid #f97316; padding: 15px 20px; margin: 25px 0; border-radius: 0 8px 8px 0; }
        .reason-title { font-weight: 600; color: #c2410c; margin-bottom: 5px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
        .reason-text { color: #ea580c; font-size: 15px; margin: 0; font-weight: 500; }
        .footer { background: #fafafa; padding: 20px; text-align: center; border-top: 1px solid #e4e4e7; }
        .footer p { margin: 0; font-size: 13px; color: #a1a1aa; }
        .button { display: inline-block; padding: 14px 30px; background-color: #0ea5e9; color: #ffffff !important; text-decoration: none; border-radius: 8px; font-weight: 700; margin-top: 10px; font-size: 15px; letter-spacing: 0.3px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Pemberitahuan Penting</h1>
        </div>
        <div class="content">
            <p>Halo Pengurus Organisasi <strong>{{ $orgName }}</strong>,</p>
            <p>Kami memberitahukan bahwa pengajuan banding untuk organisasi Anda telah <strong>Ditolak</strong> oleh Administrator Sistem MoneFlo. Status organisasi Anda tetap ditangguhkan (suspended).</p>
            
            @if(!empty($adminNote))
            <div class="reason-box">
                <div class="reason-title">Catatan Administrator:</div>
                <p class="reason-text">{{ $adminNote }}</p>
            </div>
            @endif
            
            <p>Jika Anda memiliki pertanyaan lebih lanjut, silakan hubungi tim dukungan kami atau ajukan banding kembali di kemudian hari melalui halaman portal.</p>
            
            <center>
                <a href="{{ env('FRONTEND_URL', 'http://localhost:5173') }}/login" class="button">Ke Portal MoneFlo</a>
            </center>
        </div>
        <div class="footer">
            <p>Pesan ini dikirim secara otomatis oleh Sistem MoneFlo.<br>Mohon tidak membalas email ini.</p>
        </div>
    </div>
</body>
</html>
