<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* إعدادات الخطوط والخلفية */
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
        /* الهيدر */
        .header {
            background-color: #2563eb;
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .header h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        /* المحتوى */
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
        /* جدول تفاصيل المرسل */
        .details-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
        }
        .details-table td {
            padding: 8px 0;
            font-size: 15px;
            border-bottom: 1px dashed #cbd5e1;
        }
        .details-table tr:last-child td {
            border-bottom: none;
        }
        .label {
            color: #64748b;
            width: 120px;
            font-weight: bold;
        }
        .value {
            color: #0f172a;
            font-weight: 600;
        }
        .value a {
            color: #2563eb;
            text-decoration: none;
        }
        /* صندوق نص الرسالة */
        .message-title {
            font-size: 16px;
            color: #1f2937;
            margin-bottom: 10px;
            font-weight: bold;
        }
        .message-body {
            background-color: #ffffff;
            border-right: 4px solid #10b981; /* خط أخضر لتمييز الرسالة الجديدة */
            padding: 20px;
            border-radius: 4px 8px 8px 4px;
            color: #334155;
            font-size: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            border: 1px solid #f1f5f9;
            border-right-width: 4px;
            margin-bottom: 30px;
        }
        /* التذييل */
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
            <div class="header">
                <h2>إشعار برسالة تواصل جديدة 📩</h2>
            </div>

            <div class="content">
                <div class="greeting">مرحباً فريق عمل المؤسسة،</div>

                <p class="intro-text">
                    لقد تلقيتم رسالة تواصل جديدة عبر صفحتكم الرسمية على منصة <strong>نبضة خير</strong>. إليكم التفاصيل:
                </p>

                <div class="details-box">
                    <table class="details-table">
                        <tr>
                            <td class="label">اسم المرسل:</td>
                            <td class="value">{{ $foundationMessage->name }}</td>
                        </tr>
                        <tr>
                            <td class="label">البريد الإلكتروني:</td>
                            <td class="value"><a href="mailto:{{ $foundationMessage->email }}">{{ $foundationMessage->email }}</a></td>
                        </tr>
                        <tr>
                            <td class="label">موضوع الرسالة:</td>
                            <td class="value">{{ $foundationMessage->subject }}</td>
                        </tr>
                    </table>
                </div>

                <div class="message-title">نص الرسالة:</div>
                <div class="message-body">
                    {!! nl2br(e($foundationMessage->message)) !!}
                </div>

                <p class="intro-text" style="text-align: center; background-color: #eff6ff; padding: 15px; border-radius: 8px; color: #1e3a8a;">
                    💡 <strong>تنبيه:</strong> يمكنكم قراءة هذه الرسالة والرد عليها مباشرة عبر قسم "الرسائل" في لوحة تحكم المؤسسة الخاصة بكم.
                </p>
            </div>

            <div class="footer">
                <p>تم إرسال هذا الإشعار التلقائي من منصة <span class="platform-link">نبضة خير</span></p>
                <p dir="ltr" style="margin-top: 15px;">&copy; {{ date('Y') }} Nabdat Khair. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
