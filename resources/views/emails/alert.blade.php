<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #f8fafc; color: #1e293b; margin: 0; padding: 20px; direction: rtl; text-align: right; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .header { background: #0f172a; color: #ffffff; padding: 24px; text-align: center; }
        .header h1 { margin: 0 0 6px 0; font-size: 20px; }
        .header p { margin: 0; font-size: 14px; opacity: 0.85; }
        .content { padding: 24px; }
        .alert-banner { background: #fff7ed; border-right: 4px solid #ea580c; padding: 14px 18px; border-radius: 6px; margin-bottom: 24px; color: #9a3412; font-weight: 500; font-size: 15px; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .info-table th, .info-table td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; text-align: right; }
        .info-table th { background: #f8fafc; color: #64748b; font-size: 13px; font-weight: 600; width: 35%; }
        .info-table td { color: #0f172a; font-size: 14px; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 9999px; font-size: 12px; font-weight: bold; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .footer { background: #f8fafc; padding: 16px 24px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>نظام المتابعة</h1>
            <p>{{ $organization->code === 'tarqeem' ? 'مؤسسة ترقيم TRQEM' : 'شركة تفنين TAFNEEN' }}</p>
        </div>

        <div class="content">
            <div class="alert-banner">
                ⚠️ إشعار فوري: السجل التالي بلغ النطاق الحرج ويتطلب اتخاذ إجراء فوري قبل الانتهاء.
            </div>

            <table class="info-table">
                <tr>
                    <th>نوع الوثيقة</th>
                    <td>
                        <strong>{{ $moduleType === 'iqama' ? 'بطاقة إقامة' : 'عقد عمل وظيفي' }}</strong>
                    </td>
                </tr>
                <tr>
                    <th>الاسم الكامل</th>
                    <td><strong>{{ $recordName }}</strong></td>
                </tr>
                <tr>
                    <th>{{ $moduleType === 'iqama' ? 'رقم الإقامة' : 'رقم الهوية / الإقامة' }}</th>
                    <td><code style="font-size: 15px; background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">{{ $identifier }}</code></td>
                </tr>
                <tr>
                    <th>المهنة</th>
                    <td>{{ $profession }}</td>
                </tr>
                <tr>
                    <th>الفرع / الإدارة</th>
                    <td>{{ $branch }}</td>
                </tr>
                <tr>
                    <th>تاريخ الانتهاء</th>
                    <td><strong style="color: #0f172a;">{{ $expiryDate }}</strong></td>
                </tr>
                <tr>
                    <th>الأيام المتبقية</th>
                    <td>
                        <span class="badge {{ $daysRemaining < 0 ? 'badge-danger' : 'badge-warning' }}">
                            {{ $daysRemaining < 0 ? 'منتهي منذ ' . abs($daysRemaining) . ' يوم' : $daysRemaining . ' يوماً متبقية' }}
                        </span>
                    </td>
                </tr>
                @if(!empty($notes))
                <tr>
                    <th>الملاحظات</th>
                    <td style="color: #475569; font-size: 13px;">{{ $notes }}</td>
                </tr>
                @endif
            </table>

            <p style="font-size: 13px; color: #64748b; line-height: 1.6;">
                تم إرسال هذا التنبيه آلياً بموجب إعدادات التنبيهات لمؤسسة {{ $organization->name }}. لن يتكرر إرسال هذا التنبيه لنفس السجل إلا بعد تجديده.
            </p>
        </div>

        <div class="footer">
            نظام ترقيم وتفنين الموحد &copy; {{ date('Y') }} - جميع الحقوق محفوظة
        </div>
    </div>
</body>
</html>
