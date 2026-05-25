<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>
        @if($action === 'register')
            Kode Verifikasi Registrasi – MoneFlo
        @elseif($action === 'update_email')
            Kode Verifikasi Perubahan Email – MoneFlo
        @elseif($action === 'forgot_password')
            Kode Pemulihan Kata Sandi – MoneFlo
        @else
            Kode Verifikasi – MoneFlo
        @endif
    </title>
    <style>
        /* Reset */
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #EDF2F7;
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: 100%;
        }

        /* Wrapper */
        .wrapper {
            width: 100%;
            background-color: #EDF2F7;
            padding: 40px 16px;
        }

        /* Card */
        .container {
            max-width: 560px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #1A202C 0%, #2D3748 100%);
            padding: 28px 32px;
            text-align: center;
        }
        .header-logo {
            font-size: 26px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 1px;
        }
        .header-tagline {
            font-size: 12px;
            color: #A0AEC0;
            margin-top: 4px;
            letter-spacing: 0.5px;
        }

        /* Badge */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin-bottom: 16px;
        }
        .badge-register       { background-color: #C6F6D5; color: #276749; }
        .badge-update-email   { background-color: #BEE3F8; color: #2A69AC; }
        .badge-forgot-password { background-color: #FEEBC8; color: #975A16; }
        .badge-default        { background-color: #E9D8FD; color: #553C9A; }

        /* Content */
        .content {
            padding: 36px 40px;
            text-align: center;
            color: #4A5568;
        }
        .content h2 {
            font-size: 22px;
            font-weight: 700;
            color: #1A202C;
            margin-bottom: 12px;
        }
        .content .description {
            font-size: 15px;
            line-height: 1.7;
            color: #718096;
            margin-bottom: 28px;
        }

        /* OTP Box */
        .otp-wrapper {
            background: linear-gradient(135deg, #F7FAFC 0%, #EBF4FF 100%);
            border: 2px solid #BEE3F8;
            border-radius: 12px;
            padding: 24px 16px;
            margin: 0 0 28px;
        }
        .otp-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #718096;
            margin-bottom: 10px;
        }
        .otp-code {
            font-size: 42px;
            font-weight: 800;
            color: #2B6CB0;
            letter-spacing: 10px;
            line-height: 1;
            font-variant-numeric: tabular-nums;
        }

        /* Warning */
        .warning-box {
            background-color: #FFFBEB;
            border-left: 4px solid #F6AD55;
            border-radius: 6px;
            padding: 12px 16px;
            text-align: left;
            margin-bottom: 24px;
        }
        .warning-box p {
            font-size: 13px;
            color: #744210;
            line-height: 1.5;
        }
        .warning-box strong {
            font-weight: 700;
        }

        /* Expiry notice */
        .expiry-notice {
            font-size: 13px;
            color: #A0AEC0;
            margin-bottom: 0;
        }
        .expiry-notice strong {
            color: #E53E3E;
        }

        /* Divider */
        .divider {
            border: none;
            border-top: 1px solid #E2E8F0;
            margin: 0;
        }

        /* Footer */
        .footer {
            background-color: #F7FAFC;
            padding: 20px 32px;
            text-align: center;
        }
        .footer p {
            font-size: 12px;
            color: #A0AEC0;
            line-height: 1.6;
        }
        .footer a {
            color: #4299E1;
            text-decoration: none;
        }

        /* Responsive */
        @media only screen and (max-width: 600px) {
            .content { padding: 28px 24px; }
            .otp-code { font-size: 34px; letter-spacing: 6px; }
            .header { padding: 22px 24px; }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">

        {{-- ── Header ── --}}
        <div class="header">
            <div class="header-logo">MoneFlo</div>
            <div class="header-tagline">Manajemen Keuangan Organisasi</div>
        </div>

        {{-- ── Body ── --}}
        <div class="content">

            {{-- Action badge --}}
            @if($action === 'register')
                <span class="badge badge-register">Registrasi Akun</span>
            @elseif($action === 'update_email')
                <span class="badge badge-update-email">Perubahan Email</span>
            @elseif($action === 'forgot_password')
                <span class="badge badge-forgot-password">Pemulihan Kata Sandi</span>
            @else
                <span class="badge badge-default">Verifikasi</span>
            @endif

            {{-- Title --}}
            <h2>
                @if($action === 'forgot_password')
                    Kode Pemulihan Kata Sandi
                @else
                    Kode Verifikasi Anda
                @endif
            </h2>

            {{-- Description --}}
            <p class="description">
                @if($action === 'register')
                    Gunakan kode di bawah ini untuk menyelesaikan pendaftaran organisasi Anda di MoneFlo.
                @elseif($action === 'update_email')
                    Gunakan kode di bawah ini untuk mengonfirmasi perubahan alamat email akun Anda.
                @elseif($action === 'forgot_password')
                    Gunakan kode di bawah ini untuk mereset kata sandi akun MoneFlo Anda.
                    Jika Anda tidak meminta reset kata sandi, abaikan email ini.
                @else
                    Gunakan kode di bawah ini untuk memverifikasi akun MoneFlo Anda.
                @endif
            </p>

            {{-- OTP Code --}}
            <div class="otp-wrapper">
                <div class="otp-label">Kode OTP</div>
                <div class="otp-code">{{ $otp }}</div>
            </div>

            {{-- Security warning --}}
            <div class="warning-box">
                <p>
                    <strong>⚠ Jangan bagikan kode ini kepada siapa pun</strong>, termasuk tim MoneFlo.
                    Kami tidak pernah meminta kode OTP Anda melalui telepon atau pesan.
                </p>
            </div>

            {{-- Expiry --}}
            <p class="expiry-notice">
                Kode ini berlaku selama <strong>5 menit</strong> dan hanya dapat digunakan satu kali.
            </p>

        </div>

        <hr class="divider">

        {{-- ── Footer ── --}}
        <div class="footer">
            <p>
                Email ini dikirim secara otomatis oleh sistem MoneFlo.<br>
                Jika Anda tidak merasa melakukan permintaan ini, abaikan email ini.
            </p>
            <p style="margin-top: 8px;">
                &copy; {{ date('Y') }} MoneFlo. All rights reserved.
            </p>
        </div>

    </div>
</div>
</body>
</html>
