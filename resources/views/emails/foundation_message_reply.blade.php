<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Tahoma', Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f9f9f9; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; border-top: 5px solid #2563eb; }
        .header { margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px;}
        .footer { margin-top: 30px; font-size: 12px; text-align: center; color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h3>رد من: {{ $foundationName }}</h3>
        </div>

        <div>
            {!! nl2br(e($replyBody)) !!}
        </div>

        <div class="footer">
            <p>تم إرسال هذه الرسالة عبر منصة نبضة خير</p>
        </div>
    </div>
</body>
</html>
