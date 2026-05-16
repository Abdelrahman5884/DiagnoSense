<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reset Your Password</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f6f9; padding: 20px;">

<div style="max-width: 600px; margin: auto; background: #ffffff; padding: 30px; border-radius: 12px; text-align: center; border-top: 5px solid #e3342f;">

    <h2 style="color: #2c3e50;">Password Reset Code 📩</h2>

    <p style="font-size: 16px; color: #555; line-height: 1.5;">
        Hello <strong>{{ $name }}</strong>, <br>
        We received a request to reset your <strong>DiagnoSense</strong> password.
        Please use the following code to proceed:
    </p>

    <div style="margin: 30px 0;">
            <span style="
                display: inline-block;
                font-size: 32px;
                letter-spacing: 8px;
                font-weight: bold;
                color: #ffffff;
                background: #e3342f;
                padding: 15px 30px;
                border-radius: 8px;
            ">
                {{ $otp }}
            </span>
    </div>

    <p style="font-size: 14px; color: #888;">
        This code is valid for <strong>10 minutes</strong>.
        For security, do not share this code with anyone.
    </p>

    <hr style="margin: 30px 0; border: 0; border-top: 1px solid #eee;">

    <p style="font-size: 12px; color: #aaa;">
        If you didn’t request a password reset, you can safely ignore this email.
        Your account security is our priority.
    </p>

    <p style="font-size: 11px; color: #ccc; margin-top: 20px;">
        © {{ date('Y') }} DiagnoSense. All rights reserved.
    </p>

</div>

</body>
</html>
