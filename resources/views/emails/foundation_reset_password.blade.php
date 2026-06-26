<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>استعادة كلمة المرور | نبضة خير</title>
</head>
<body style="background-color: #f3f4f6; margin: 0; padding: 40px 10px; font-family: 'Segoe UI', Tahoma, sans-serif; text-align: right; direction: rtl;">
    <table width="100%" max-width="600" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e5e7eb;" cellpadding="0" cellspacing="0">
        <tr>
            <td style="background-color: #2563eb; padding: 30px; text-align: center;">
                <h1 style="color: #ffffff; font-size: 24px; margin: 0;">نبضة خير</h1>
            </td>
        </tr>
        <tr>
            <td style="padding: 40px 30px;">
                <p style="font-size: 16px; font-weight: bold; color: #1f2937;">مرحباً، {{ $foundationName }}</p>
                <p style="font-size: 15px; color: #4b5563; line-height: 1.6;">لقد تلقينا طلباً لاستعادة كلمة المرور الخاصة بحساب مؤسستكم على منصة نبضة خير. يرجى استخدام الرمز التالي لإتمام العملية:</p>

                <div style="background-color: #f8fafc; border: 1px dashed #cbd5e1; padding: 20px; text-align: center; border-radius: 8px; margin: 30px 0;">
                    <span style="font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #2563eb;">{{ $code }}</span>
                </div>

                <p style="font-size: 13px; color: #ef4444; margin-bottom: 20px;">* هذا الرمز صالح لمدة 15 دقيقة فقط. لا تشارك هذا الرمز مع أي شخص.</p>
                <p style="font-size: 14px; color: #6b7280;">إذا لم تقم بطلب استعادة كلمة المرور، يرجى تجاهل هذه الرسالة.</p>
            </td>
        </tr>
    </table>
</body>
</html>
