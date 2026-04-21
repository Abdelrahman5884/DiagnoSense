<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Verify Email</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f6f9; padding: 20px;">

<div style="max-width: 600px; margin: auto; background: #ffffff; padding: 30px; border-radius: 12px; text-align: center;">

    <h2 style="color: #2c3e50;">Hello {{ $name }} 👋</h2>

    <p style="font-size: 16px; color: #555;">
        Use the following OTP to verify your email:
    </p>

    <div style="margin: 30px 0;">
            <span style="
                display: inline-block;
                font-size: 28px;
                letter-spacing: 6px;
                font-weight: bold;
                color: #ffffff;
                background: #3490dc;
                padding: 15px 25px;
                border-radius: 8px;
            ">
                {{ $otp }}
            </span>
    </div>

    <p style="font-size: 14px; color: #888;">
        This OTP will expire in 10 minutes.
    </p>

    <hr style="margin: 30px 0;">

    <p style="font-size: 12px; color: #aaa;">
        If you didn’t request this, please ignore this email.
    </p>

</div>

</body>
</html>
