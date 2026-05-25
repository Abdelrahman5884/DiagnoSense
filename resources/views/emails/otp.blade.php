<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Resend OTP</title>
</head>

<body style="margin:0; padding:0; font-family: 'Segoe UI', Arial; background:#f4f6f9;">

    <!-- Outer Container -->
    <div style="max-width:650px; margin:50px auto;">

        <!-- Card -->
        <div style="
            background:#ffffff;
            border-radius:16px;
            padding:40px;
            text-align:center;
            box-shadow:0 10px 30px rgba(0,0,0,0.08);
        ">

            <!-- Logo / App Name -->
            <h1 style="
                margin:0;
                font-size:32px;
                font-weight:800;
                color:#2c3e50;
                letter-spacing:1px;
            ">
                DiagnoSense
            </h1>

            <p style="
                color:#999;
                font-size:14px;
                margin-top:5px;
            ">
                Email Verification
            </p>

            <!-- Divider -->
            <div style="
                width:60px;
                height:4px;
                background:#2c3e50;
                margin:20px auto;
                border-radius:5px;
            "></div>

            <!-- User Name -->
            <h2 style="
                margin:25px 0 10px;
                font-size:24px;
                font-weight:700;
                color:#34495e;
            ">
                Hello {{ $name }} 👋
            </h2>

            <!-- Message -->
            <p style="
                color:#555;
                font-size:16px;
                line-height:1.6;
                margin-bottom:10px;
            ">
                You requested to resend your verification code.
            </p>

            <p style="
                color:#777;
                font-size:15px;
            ">
                Use the code below to complete your verification:
            </p>

            <!-- OTP BOX -->
            <div style="
                margin:30px 0;
                padding:20px;
                background:linear-gradient(135deg, #f1f3f5, #e9ecef);
                border-radius:12px;
                display:inline-block;
            ">
                <span style="
                    font-size:36px;
                    font-weight:800;
                    letter-spacing:8px;
                    color:#2c3e50;
                ">
                    {{ $otp }}
                </span>
            </div>

            <!-- Expiry -->
            <p style="
                color:#888;
                font-size:14px;
                margin-top:10px;
            ">
                ⏳ This OTP will expire in <strong>10 minutes</strong>.
            </p>

            <!-- Warning -->
            <p style="
                margin-top:30px;
                font-size:13px;
                color:#aaa;
            ">
                If you didn’t request this, you can safely ignore this email.
            </p>

        </div>

        <!-- Footer -->
        <p style="
            text-align:center;
            margin-top:20px;
            font-size:12px;
            color:#bbb;
        ">
            © {{ date('Y') }} DiagnoSense. All rights reserved.
        </p>

    </div>

</body>
</html>