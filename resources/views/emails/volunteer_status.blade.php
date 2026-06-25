<!DOCTYPE html>
<html lang="ar" dir="rtl" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>تحديث حالة طلب التطوع | نبضة خير</title>
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        table { border-collapse: collapse !important; }
        body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        a[x-apple-data-detectors] { color: inherit !important; text-decoration: none !important; font-size: inherit !important; font-family: inherit !important; font-weight: inherit !important; line-height: inherit !important; }

        @media screen and (max-width: 600px) {
            .container { width: 100% !important; max-width: 100% !important; border-radius: 0 !important;}
            .content-padding { padding: 30px 20px !important; }
        }
    </style>
</head>
<body style="background-color: #f3f4f6; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; text-align: right; direction: rtl;">

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f3f4f6;">
        <tr>
            <td align="center" style="padding: 40px 10px;">

                <table class="container" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 16px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05); overflow: hidden; border: 1px solid #e5e7eb;">

                    <!-- الهيدر -->
                    <tr>
                        <td align="center" style="background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); padding: 40px 30px;">
                            <h1 style="color: #ffffff; font-size: 28px; margin: 0; font-weight: 900; letter-spacing: 0.5px;">
                                نبضة خير <span style="color: #93c5fd;">💙</span>
                            </h1>
                            <p style="color: #bfdbfe; font-size: 14px; margin: 10px 0 0 0; font-weight: 600;">
                                تحديث حالة حساب المتطوع
                            </p>
                        </td>
                    </tr>

                    <!-- المحتوى الرئيسي -->
                    <tr>
                        <td class="content-padding" style="padding: 40px 40px 30px 40px; background-color: #ffffff;">

                            <h2 style="color: #1f2937; font-size: 20px; font-weight: bold; margin: 0 0 20px 0;">
                                مرحباً، {{ $volunteer->name ?? 'أيها المتطوع الكريم' }}
                            </h2>

                            <p style="color: #4b5563; font-size: 16px; line-height: 1.8; margin: 0 0 25px 0;">
                                نود إعلامك بأنه تمت مراجعة طلب انضمامك لفريق التطوع في منصة <strong>نبضة خير</strong>، وإليك النتيجة:
                            </p>

                            <!-- رسالة الحالة -->
                            @if($volunteer->status === 'approved')
                                <div style="background-color: #ecfdf5; border-right: 4px solid #10b981; padding: 25px; border-radius: 8px 4px 4px 8px; margin-bottom: 30px;">
                                    <h3 style="color: #065f46; font-size: 18px; margin: 0 0 10px 0;">تم قبول طلبك بنجاح! 🎉</h3>
                                    <p style="color: #047857; font-size: 15px; margin: 0; line-height: 1.7;">
                                        نهنئك لانضمامك إلى عائلة نبضة خير. نحن فخورون بشغفك للعمل الخيري. يمكنك الآن الدخول إلى المنصة لاستكشاف الفرص التطوعية المتاحة والمشاركة فيها.
                                    </p>
                                </div>

                                <!-- الزر (Call to Action) يظهر للمقبولين فقط -->
                                <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 30px;">
                                    <tr>
                                        <td align="center">
                                            <a href="https://nabdatkhair.com/login" target="_blank" style="background-color: #2563eb; color: #ffffff; display: inline-block; padding: 14px 30px; font-size: 16px; font-weight: bold; text-decoration: none; border-radius: 8px; box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);">
                                                تسجيل الدخول للمنصة
                                            </a>
                                        </td>
                                    </tr>
                                </table>

                            @elseif($volunteer->status === 'rejected')
                                <div style="background-color: #fff1f2; border-right: 4px solid #e11d48; padding: 25px; border-radius: 8px 4px 4px 8px; margin-bottom: 30px;">
                                    <h3 style="color: #9f1239; font-size: 18px; margin: 0 0 10px 0;">نعتذر، لم يتم قبول الطلب</h3>
                                    <p style="color: #be123c; font-size: 15px; margin: 0; line-height: 1.7;">
                                        نشكر لك رغبتك الصادقة في فعل الخير واهتمامك بالانضمام إلينا. نأسف لإبلاغك بأنه تعذر قبول طلبك في الوقت الحالي نظراً للاكتفاء بالعدد المطلوب أو لعدم تطابق بعض الشروط. نتمنى لك كل التوفيق.
                                    </p>
                                </div>
                            @else
                                <div style="background-color: #fffbeb; border-right: 4px solid #f59e0b; padding: 25px; border-radius: 8px 4px 4px 8px; margin-bottom: 30px;">
                                    <h3 style="color: #b45309; font-size: 18px; margin: 0 0 10px 0;">حسابك قيد المراجعة ⏳</h3>
                                    <p style="color: #d97706; font-size: 15px; margin: 0; line-height: 1.7;">
                                        تمت إعادة طلبك إلى مرحلة المراجعة. سيقوم فريقنا بتدقيق البيانات مرة أخرى وسنوافيك بالنتيجة النهائية في أقرب وقت ممكن.
                                    </p>
                                </div>
                            @endif

                            <!-- التوقيع -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td style="padding-top: 20px; border-top: 1px solid #f3f4f6;">
                                        <p style="color: #1f2937; font-size: 16px; font-weight: bold; margin: 0 0 5px 0;">
                                            مع خالص التحيات،
                                        </p>
                                        <p style="color: #2563eb; font-size: 15px; font-weight: 600; margin: 0;">
                                            إدارة منصة نبضة خير
                                        </p>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- الفوتر -->
                    <tr>
                        <td align="center" style="background-color: #f8fafc; padding: 30px; border-top: 1px solid #e5e7eb;">
                            <p style="color: #6b7280; font-size: 13px; line-height: 1.6; margin: 0 0 10px 0;">
                                تم إرسال هذه الرسالة تلقائياً من نظام إدارة المتطوعين في <a href="https://nabdatkhair.com" style="color: #2563eb; text-decoration: none; font-weight: bold;">نبضة خير</a>.
                            </p>
                            <p dir="ltr" style="color: #9ca3af; font-size: 12px; margin: 15px 0 0 0; font-family: Tahoma, sans-serif;">
                                &copy; {{ date('Y') }} Nabdat Khair. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
