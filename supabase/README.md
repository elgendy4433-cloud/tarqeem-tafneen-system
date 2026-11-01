# إعداد قاعدة البيانات السحابية (Supabase Database Setup)

يحتوي هذا المجلد على المخطط الشامل لقاعدة البيانات السحابية (PostgreSQL) الخاص بنظام المتابعة (ترقيم & تفنين).

## 🚀 طريقة الإعداد بنقرة واحدة (1-Click SQL Setup)

1. افتح حسابك في منصة [Supabase](https://supabase.com) وأنشئ مشروعاً جديداً.
2. من القائمة الجانبية في لوحة تحكم Supabase، اضغط على **SQL Editor**.
3. افتح ملف المخطط:
   [`supabase/migrations/20261029_initial_schema.sql`](./migrations/20261029_initial_schema.sql)
4. انسخ كامل المحتوى والصقه في الـ **SQL Editor** ثم اضغط زر **Run**.
5. ستتم تهيئة:
   - كافة الجداول الـ 8 (`organizations`, `profiles`, `organization_memberships`, `iqamas`, `contracts`, `licenses`, `system_settings`, `alert_logs`, `import_logs`).
   - تفعيل أمان مستوى الصفوف **Row Level Security (RLS)** مع عزل كامل للبيانات وسياسات الحذف الآمن.
   - الدوال والتريغرز التلقائية لتحديث السجلات ومزامنة مستخدمي المصادقة `auth.users`.
   - الفهارس الفائقة السرعة لتحسين البحث وحساب الحالات.
   - بذور البيانات الافتراضية الأولية لترقيم وتفنين.

## 🔑 مفاتيح الربط (Environment Variables)

من لوحة Supabase اذهب إلى **Project Settings** > **API**:
- **Project URL**: ضعه في `SUPABASE_URL`
- **anon / public key**: ضعه في `SUPABASE_ANON_KEY`
*(تنبيه: لا تقم بمشاركة `service_role key` أبداً في واجهة المستخدم أو المستودع العام).*
