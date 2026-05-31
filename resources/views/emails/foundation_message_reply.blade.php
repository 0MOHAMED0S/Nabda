<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* إعادة ضبط الأساسيات */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f3f4f6;
            color: #333333;
            line-height: 1.8;
            -webkit-font-smoothing: antialiased;
        }
        .wrapper {
            padding: 40px 20px;
            background-color: #f3f4f6;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        /* الهيدر (رأس الإيميل) */
        .header {
            background-color: #2563eb; /* اللون الأزرق الرسمي */
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        /* محتوى الإيميل */
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 15px;
        }
        .intro-text {
            color: #4b5563;
            font-size: 15px;
            margin-bottom: 25px;
        }
        /* صندوق الرد (يضيف لمسة احترافية جداً) */
        .message-box {
            background-color: #f8fafc;
            border-right: 4px solid #2563eb; /* خط أزرق على اليمين لتمييز الرد */
            padding: 25px 20px;
            border-radius: 4px 8px 8px 4px;
            color: #1f2937;
            font-size: 16px;
            margin-bottom: 30px;
        }
        .sign-off {
            color: #4b5563;
            font-size: 15px;
        }
        /* التذييل (الفوتر) */
        .footer {
            background-color: #f9fafb;
            padding: 25px 20px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .footer p {
            margin: 5px 0;
            font-size: 13px;
            color: #6b7280;
        }
        .platform-link {
            color: #2563eb;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <!-- الهيدر -->
            <div class="header">
                <h2>{{ $foundationName }}</h2>
            </div>

            <!-- المحتوى -->
            <div class="content">
                <div class="greeting">تحية طيبة،</div>

                <p class="intro-text">
                    لقد تلقيت رداً جديداً على رسالتك من فريق عمل <strong>{{ $foundationName }}</strong>:
                </p>

                <!-- صندوق عرض الرد بشكل أنيق -->
                <div class="message-box">
                    {!! nl2br(e($replyBody)) !!}
                </div>

                <div class="sign-off">
                    <p>مع خالص التحيات،<br><strong>إدارة {{ $foundationName }}</strong></p>
                </div>
            </div>

            <!-- التذييل -->
            <div class="footer">
                <p>تم إرسال هذه الرسالة بكل حُب عبر منصة <span class="platform-link">نبضة خير</span> 💙</p>
                <p>إذا كان لديك أي استفسار آخر، يمكنك الرد مباشرة على هذا البريد الإلكتروني.</p>
                <p dir="ltr" style="margin-top: 15px;">&copy; {{ date('Y') }} Nabdat Khair. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
