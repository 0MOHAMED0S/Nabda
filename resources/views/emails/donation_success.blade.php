<!DOCTYPE html>
<html lang="ar" dir="rtl" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>تأكيد استلام التبرع | نبضة خير</title>
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        table { border-collapse: collapse !important; }
        body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    </style>
</head>
<body style="background-color: #f3f4f6; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; text-align: right; direction: rtl;">

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f3f4f6;">
        <tr>
            <td align="center" style="padding: 40px 10px;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 16px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05); overflow: hidden; border: 1px solid #e5e7eb;">

                    <!-- الهيدر -->
                    <tr>
                        <td align="center" style="background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); padding: 40px 30px;">
                            <h1 style="color: #ffffff; font-size: 28px; margin: 0; font-weight: 900;">
                                نبضة خير <span style="color: #93c5fd;">💙</span>
                            </h1>
                            <p style="color: #bfdbfe; font-size: 14px; margin: 10px 0 0 0; font-weight: 600;">
                                @if($donation->donation_type === 'financial') إيصال استلام تبرع مالي @else تأكيد تسجيل تبرع عيني @endif
                            </p>
                        </td>
                    </tr>

                    <!-- المحتوى -->
                    <tr>
                        <td style="padding: 40px 30px; background-color: #ffffff;">

                            <h2 style="color: #1f2937; font-size: 20px; font-weight: bold; margin: 0 0 20px 0;">
                                مرحباً، {{ $donation->donor_name ?? 'فاعل خير' }}
                            </h2>

                            @if($donation->donation_type === 'financial')
                                <div style="background-color: #ecfdf5; border-right: 4px solid #10b981; padding: 20px; border-radius: 8px 4px 4px 8px; margin-bottom: 25px;">
                                    <h3 style="color: #065f46; font-size: 18px; margin: 0 0 10px 0;">تم استلام تبرعك بنجاح! 🎉</h3>
                                    <p style="color: #047857; font-size: 15px; margin: 0; line-height: 1.7;">
                                        شكراً لك! تبرعك السخي بقيمة <strong>{{ number_format($donation->amount) }} جنيه</strong>
                                        @if($caseTitle) لحالة "{{ $caseTitle }}" @endif
                                        قد تم بنجاح ووصل إلى المستفيد. جعلها الله في ميزان حسناتك.
                                    </p>
                                </div>
                            @else
                                <div style="background-color: #eff6ff; border-right: 4px solid #f59e0b; padding: 20px; border-radius: 8px 4px 4px 8px; margin-bottom: 25px;">
                                    <h3 style="color: #92400e; font-size: 18px; margin: 0 0 10px 0;">تم تسجيل طلبك بنجاح 🎁</h3>
                                    <p style="color: #b45309; font-size: 15px; margin: 0; line-height: 1.7;">
                                        شكراً لعطائك! لقد قمنا بتسجيل تبرعك العيني <strong>({{ $donation->item_category }})</strong>. سنتواصل معك في أقرب وقت لترتيب تفاصيل استلام التبرع.
                                    </p>
                                </div>
                            @endif

                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td style="padding-top: 20px; border-top: 1px solid #f3f4f6;">
                                        <p style="color: #1f2937; font-size: 16px; font-weight: bold; margin: 0 0 5px 0;">مع خالص التحيات،</p>
                                        <p style="color: #2563eb; font-size: 15px; font-weight: 600; margin: 0;">إدارة منصة نبضة خير</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- الفوتر -->
                    <tr>
                        <td align="center" style="background-color: #f8fafc; padding: 30px; border-top: 1px solid #e5e7eb;">
                            <p dir="ltr" style="color: #9ca3af; font-size: 12px; margin: 0; font-family: Tahoma, sans-serif;">
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
