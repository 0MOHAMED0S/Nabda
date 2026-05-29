<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Tahoma', Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f9f9f9; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; border-top: 5px solid #2563eb; }
        .header { text-align: center; margin-bottom: 20px; }
        .success { color: #16a34a; font-weight: bold; }
        .danger { color: #dc2626; font-weight: bold; }
        .footer { margin-top: 30px; font-size: 12px; text-align: center; color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>منصة نبضة خير</h2>
        </div>

        <p>مرحباً، <strong>{{ $volunteer->full_name ?? 'أيها المتطوع الكريم' }}</strong></p>

        <p>نود إعلامكم بأنه تم تحديث حالة طلب التطوع الخاص بكم على منصة "نبضة خير".</p>

        <p>
            النتيجة:
            @if($volunteer->status === 'approved')
                <span class="success">تم قبول طلبكم 🎉</span>
                <br><br>أهلاً بكم في فريق نبضة خير. سنتواصل معكم قريباً للبدء في المهام الميدانية.
            @else
                <span class="danger">نعتذر، لم يتم قبول الطلب</span>
                <br><br>نأسف لإبلاغكم بأنه تعذر قبول طلبكم في الوقت الحالي. نتمنى لكم التوفيق ونشكر لكم رغبتكم في فعل الخير.
            @endif
        </p>

        <div class="footer">
            <p>مع تحيات فريق عمل نبضة خير</p>
            <p>contact@nabdatkhair.com</p>
        </div>
    </div>
</body>
</html>
