<!DOCTYPE html>
<html>
<head>
    <title>Test Email from MoneFlo Backend</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f5; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header { background-color: #2D3748; padding: 24px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 32px; text-align: center; color: #4A5568; }
        .content p { font-size: 16px; line-height: 1.5; margin-bottom: 16px; }
        .badge { display: inline-block; background-color: #C6F6D5; border: 2px solid #68D391; border-radius: 8px; padding: 12px 24px; margin: 20px 0; font-size: 18px; font-weight: bold; color: #276749; }
        .timestamp-box { background-color: #EBF8FF; border: 1px solid #BEE3F8; border-radius: 8px; padding: 12px 20px; margin: 16px 0; font-size: 14px; color: #2C5282; font-family: monospace; }
        .footer { background-color: #F7FAFC; padding: 16px; text-align: center; font-size: 14px; color: #A0AEC0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>MoneFlo</h1>
        </div>
        <div class="content">
            <h2>📧 Test Email</h2>
            <p>This is a test email sent from the <strong>MoneFlo Backend</strong> to verify that the Gmail SMTP configuration is working correctly.</p>
            <div class="badge">
                ✅ SMTP Configuration OK
            </div>
            <p>If you received this email, it means the mail service is configured and delivering messages successfully.</p>
            <div class="timestamp-box">
                Sent at: {{ $timestamp }}
            </div>
            <p style="font-size: 14px; color: #718096;">This is an automated test message. No action is required.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} MoneFlo. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
