<!DOCTYPE html>
<html>
<head>
    <title>Pengumuman Sistem</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f5; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header { background-color: #2D3748; padding: 24px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 32px; text-align: center; color: #4A5568; }
        .content p { font-size: 16px; line-height: 1.5; margin-bottom: 24px; }
        .announcement-box { background-color: #FEFCBF; border: 2px solid #F6E05E; border-radius: 8px; padding: 20px; margin: 24px 0; font-size: 16px; color: #744210; text-align: left; }
        .footer { background-color: #F7FAFC; padding: 16px; text-align: center; font-size: 14px; color: #A0AEC0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $appName }}</h1>
        </div>
        <div class="content">
            <h2>Pemberitahuan Sistem</h2>
            <p>
                Terdapat pengumuman baru dari administrator sistem yang perlu Anda ketahui:
            </p>
            <div class="announcement-box">
                {{ $announcement }}
            </div>
            <p>Terima kasih telah menggunakan {{ $appName }}.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
