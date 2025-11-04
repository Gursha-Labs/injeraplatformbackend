<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset OTP - Injera Platform</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f6f9fc;
            margin: 0;
            padding: 24px;
        }

        .card {
            max-width: 560px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            padding: 28px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
        }

        .brand {
            font-weight: 700;
            color: #111827;
            letter-spacing: .2px;
        }

        .greeting {
            margin: 0 0 12px;
            color: #111827;
            font-size: 18px;
        }

        .line {
            color: #374151;
            margin: 8px 0;
        }

        .otp {
            font-size: 30px;
            letter-spacing: 6px;
            font-weight: 800;
            color: #111827;
            text-align: center;
            margin: 22px 0;
            padding: 16px;
            border: 1px dashed #d1d5db;
            border-radius: 10px;
            background: #f9fafb;
        }

        .muted {
            color: #6b7280;
            font-size: 14px;
        }

        .footer {
            margin-top: 28px;
            color: #6b7280;
            font-size: 14px;
        }

        .strong {
            font-weight: 700;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="brand">Injera Platform</div>
        <p class="greeting">Hello {{ $username ?? 'User' }}!</p>
        <p class="line">We received a request to reset your password for Injera Platform.</p>
        <p class="line">Your OTP code for password reset is:</p>
        <div class="otp">{{ $otp }}</div>
        <p class="line strong">This OTP will expire in 5 minutes.</p>
        <p class="muted">If you did not request a password reset, no further action is required.</p>
        <p class="footer">Regards,<br />The Injera Team</p>
    </div>

</body>

</html>
