<!DOCTYPE html>
<html>
<head>
    <title>Kode Verifikasi Anda</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f5; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header { background-color: #2D3748; padding: 24px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 32px; text-align: center; color: #4A5568; }
        .content p { font-size: 16px; line-height: 1.5; margin-bottom: 24px; }
        .otp-box { background-color: #F7FAFC; border: 2px dashed #CBD5E0; border-radius: 8px; padding: 16px; margin: 24px 0; font-size: 32px; font-weight: bold; color: #2B6CB0; letter-spacing: 4px; }
        .footer { background-color: #F7FAFC; padding: 16px; text-align: center; font-size: 14px; color: #A0AEC0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>MoneFlo</h1>
        </div>
        <div class="content">
            <h2>Kode Verifikasi Anda</h2>
            <p>
                Gunakan kode verifikasi (OTP) berikut untuk 
                @if($action === 'register')
                    menyelesaikan pendaftaran organisasi Anda.
                @elseif($action === 'update_email')
                    memverifikasi perubahan email Anda.
                @else
                    memverifikasi akun Anda.
                @endif
            </p>
            <div class="otp-box">
                {{ $otp }}
            </div>
            <p>Kode ini hanya berlaku selama <strong>5 menit</strong>. Jangan bagikan kode ini kepada siapa pun.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} MoneFlo. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
