<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Password Reset OTP</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .email-container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .email-header { background-color: #4CAF50; color: #ffffff; padding: 20px; text-align: center; }
        .email-body { padding: 30px; color: #333333; line-height: 1.6; }
        .otp-box { font-size: 28px; font-weight: bold; letter-spacing: 6px; text-align: center; color: #4CAF50; background: #f6fffa; border: 1px dashed #b7e1c7; padding: 16px; border-radius: 8px; margin: 20px 0; }
        .email-footer { background-color: #f4f4f4; padding: 20px; text-align: center; font-size: 12px; color: #666666; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>Password Reset OTP</h1>
        </div>
        <div class="email-body">
            <p>Hello,</p>
            <p>Use the following One-Time Password (OTP) to reset your account password:</p>
            <div class="otp-box">{{ $otp }}</div>
            <p>This OTP will expire in 10 minutes. If you did not request a password reset, you can safely ignore this email.</p>
        </div>
        <div class="email-footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
