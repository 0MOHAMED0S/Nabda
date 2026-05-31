<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Tahoma', Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f9f9f9; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; border-top: 5px solid #2563eb; }
        .info-box { background-color: #f3f4f6; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .footer { margin-top: 30px; font-size: 12px; text-align: center; color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <h3>مرحباً، لديك رسالة تواصل جديدة عبر منصة نبضة خير</h3>

        <div class="info-box">
            <p><strong>اسم المرسل:</strong> {{ $foundationMessage->name }}</p>
            <p><strong>البريد الإلكتروني:</strong> {{ $foundationMessage->email }}</p>
            <p><strong>الموضوع:</strong> {{ $foundationMessage->subject }}</p>
        </div>

        <h4>نص الرسالة:</h4>
        <p>{!! nl2br(e($foundationMessage->message)) !!}</p>

        <p><br>يمكنكم الرد على هذه الرسالة من خلال لوحة تحكم المؤسسة الخاصة بكم.</p>

        <div class="footer">
            <p>منصة نبضة خير</p>
        </div>
    </div>
</body>
</html>
