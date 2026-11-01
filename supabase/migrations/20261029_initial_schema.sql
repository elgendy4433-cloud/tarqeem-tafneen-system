-- ======================================================================
-- نظام المتابعة لإدارة الإقامات والعقود والتراخيص | ترقيم & تفنين
-- PostgreSQL Migration & Row-Level Security (RLS) Schema for Supabase
-- Date: 2026-10-29
-- Multi-Tenant Architecture with Strict Isolation & RBAC
-- ======================================================================

-- 1. تفعيل الامتدادات الضرورية
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- ======================================================================
-- 2. جدول المؤسسات (Organizations / Companies)
-- ======================================================================
CREATE TABLE IF NOT EXISTS public.organizations (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    code VARCHAR(50) UNIQUE NOT NULL, -- 'tarqeem' or 'tafneen'
    name VARCHAR(255) NOT NULL,
    legal_name VARCHAR(255),
    commercial_registration VARCHAR(50),
    tax_number VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- ======================================================================
-- 3. جدول الملفات الشخصية للمستخدمين (Profiles)
-- مرتبط بنظام مصادقة Supabase (auth.users)
-- ======================================================================
CREATE TABLE IF NOT EXISTS public.profiles (
    id UUID PRIMARY KEY REFERENCES auth.users(id) ON DELETE CASCADE,
    email VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    role VARCHAR(50) DEFAULT 'user' CHECK (role IN ('admin', 'manager', 'user')),
    current_organization_code VARCHAR(50) DEFAULT 'tarqeem' REFERENCES public.organizations(code) ON UPDATE CASCADE,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- ======================================================================
-- 4. جدول عضويات المستخدمين في المؤسسات (Organization Memberships)
-- يدعم تعدد الكيانات بحيث يحدد صراحة وصول المستخدم لترقيم، تفنين، أو كليهما
-- مع عزل صارم (Strict Company Isolation) وحالة العضوية
-- ======================================================================
CREATE TABLE IF NOT EXISTS public.organization_memberships (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES public.profiles(id) ON DELETE CASCADE,
    organization_code VARCHAR(50) NOT NULL REFERENCES public.organizations(code) ON DELETE CASCADE ON UPDATE CASCADE,
    role VARCHAR(50) DEFAULT 'user' CHECK (role IN ('admin', 'manager', 'user')),
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'suspended', 'invited')),
    invited_by UUID REFERENCES auth.users(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE (user_id, organization_code)
);

-- ======================================================================
-- 5. جدول الإقامات (Iqamas)
-- ======================================================================
CREATE TABLE IF NOT EXISTS public.iqamas (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_code VARCHAR(50) NOT NULL REFERENCES public.organizations(code) ON DELETE CASCADE ON UPDATE CASCADE,
    name VARCHAR(255) NOT NULL,
    iqama_number VARCHAR(50) NOT NULL,
    profession VARCHAR(150),
    branch VARCHAR(150),
    issue_date DATE,
    expiry_date DATE NOT NULL,
    notes TEXT,
    transfer_renewal_notes TEXT,
    created_by UUID REFERENCES auth.users(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE (organization_code, iqama_number)
);

-- ======================================================================
-- 6. جدول العقود الوظيفية (Contracts)
-- حل مشكلة صيغ الإكسل وحساب أيام العمل والعلاقة التعاقدية بدقة
-- ======================================================================
CREATE TABLE IF NOT EXISTS public.contracts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_code VARCHAR(50) NOT NULL REFERENCES public.organizations(code) ON DELETE CASCADE ON UPDATE CASCADE,
    name VARCHAR(255) NOT NULL,
    national_id VARCHAR(50) NOT NULL,
    profession VARCHAR(150),
    skill_classification VARCHAR(100),
    department VARCHAR(150),
    branch VARCHAR(150),
    contractual_relation_start DATE,
    contract_start_date DATE,
    contract_end_date DATE NOT NULL,
    renewals_count INT DEFAULT 0 CHECK (renewals_count >= 0),
    notes TEXT,
    created_by UUID REFERENCES auth.users(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE (organization_code, national_id)
);

-- ======================================================================
-- 7. جدول التراخيص والشهادات (Licenses)
-- ======================================================================
CREATE TABLE IF NOT EXISTS public.licenses (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_code VARCHAR(50) NOT NULL REFERENCES public.organizations(code) ON DELETE CASCADE ON UPDATE CASCADE,
    document_name VARCHAR(255) NOT NULL,
    document_type VARCHAR(100),
    document_number VARCHAR(100) NOT NULL,
    branch VARCHAR(150),
    issue_date DATE,
    expiry_date DATE NOT NULL,
    notes TEXT,
    created_by UUID REFERENCES auth.users(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE (organization_code, document_number)
);

-- ======================================================================
-- 8. جدول إعدادات التنبيهات المؤسسية (System Settings)
-- ======================================================================
CREATE TABLE IF NOT EXISTS public.system_settings (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_code VARCHAR(50) UNIQUE NOT NULL REFERENCES public.organizations(code) ON DELETE CASCADE ON UPDATE CASCADE,
    iqama_alert_threshold INT DEFAULT 45 CHECK (iqama_alert_threshold > 0),
    contract_alert_threshold INT DEFAULT 90 CHECK (contract_alert_threshold > 0),
    license_alert_threshold INT DEFAULT 60 CHECK (license_alert_threshold > 0),
    alert_emails TEXT,
    is_alert_enabled BOOLEAN DEFAULT TRUE,
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- ======================================================================
-- 9. جدول سجلات التنبيهات المرسلة (Alert Logs)
-- ======================================================================
CREATE TABLE IF NOT EXISTS public.alert_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_code VARCHAR(50) NOT NULL REFERENCES public.organizations(code) ON DELETE CASCADE ON UPDATE CASCADE,
    record_id VARCHAR(100),
    type VARCHAR(50) CHECK (type IN ('iqama', 'contract', 'license')),
    subject VARCHAR(255) NOT NULL,
    recipient TEXT NOT NULL,
    details TEXT,
    days_remaining INT,
    expiry_ref DATE,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- ======================================================================
-- 10. جدول سجلات الاستيراد الذكي (Import Logs)
-- ======================================================================
CREATE TABLE IF NOT EXISTS public.import_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_code VARCHAR(50) NOT NULL REFERENCES public.organizations(code) ON DELETE CASCADE ON UPDATE CASCADE,
    module_type VARCHAR(50) CHECK (module_type IN ('iqamas', 'contracts', 'licenses')),
    file_name VARCHAR(255),
    total_rows INT DEFAULT 0,
    inserted_rows INT DEFAULT 0,
    updated_rows INT DEFAULT 0,
    failed_rows INT DEFAULT 0,
    error_details JSONB,
    created_by UUID REFERENCES auth.users(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- ======================================================================
-- 11. جدول سجل التدقيق الإداري والأمني (Audit Logs)
-- يسجل عمليات الدخول، تبديل الكيانات، الإضافة، التعديل، والحذف ودعوة المستخدمين
-- ======================================================================
CREATE TABLE IF NOT EXISTS public.audit_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_code VARCHAR(50) NOT NULL REFERENCES public.organizations(code) ON DELETE CASCADE ON UPDATE CASCADE,
    user_id UUID REFERENCES auth.users(id) ON DELETE SET NULL,
    user_email VARCHAR(255),
    action VARCHAR(100) NOT NULL, -- 'LOGIN', 'SWITCH_COMPANY', 'CREATE_RECORD', 'UPDATE_RECORD', 'DELETE_RECORD', 'INVITE_USER', 'CHANGE_ROLE', 'SUSPEND_USER'
    target_entity VARCHAR(100), -- 'iqamas', 'contracts', 'licenses', 'memberships', 'system_settings'
    entity_id VARCHAR(100),
    details JSONB,
    ip_address VARCHAR(50),
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- ======================================================================
-- 12. الفهارس المتقدمة لتحسين سرعة الاستعلامات والفلترة (Indexes)
-- ======================================================================
CREATE INDEX IF NOT EXISTS idx_iqamas_org_expiry ON public.iqamas (organization_code, expiry_date);
CREATE INDEX IF NOT EXISTS idx_iqamas_branch ON public.iqamas (branch);
CREATE INDEX IF NOT EXISTS idx_contracts_org_end ON public.contracts (organization_code, contract_end_date);
CREATE INDEX IF NOT EXISTS idx_contracts_dept ON public.contracts (department);
CREATE INDEX IF NOT EXISTS idx_licenses_org_expiry ON public.licenses (organization_code, expiry_date);
CREATE INDEX IF NOT EXISTS idx_alert_logs_org_created ON public.alert_logs (organization_code, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_audit_logs_org_created ON public.audit_logs (organization_code, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_audit_logs_user ON public.audit_logs (user_id);
CREATE INDEX IF NOT EXISTS idx_memberships_user_org ON public.organization_memberships (user_id, organization_code, status);

-- ======================================================================
-- 13. دالة وتريغر التحديث التلقائي للوقت (updated_at Trigger)
-- ======================================================================
CREATE OR REPLACE FUNCTION public.handle_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_organizations_updated_at ON public.organizations;
CREATE TRIGGER trg_organizations_updated_at BEFORE UPDATE ON public.organizations FOR EACH ROW EXECUTE FUNCTION public.handle_updated_at();

DROP TRIGGER IF EXISTS trg_profiles_updated_at ON public.profiles;
CREATE TRIGGER trg_profiles_updated_at BEFORE UPDATE ON public.profiles FOR EACH ROW EXECUTE FUNCTION public.handle_updated_at();

DROP TRIGGER IF EXISTS trg_memberships_updated_at ON public.organization_memberships;
CREATE TRIGGER trg_memberships_updated_at BEFORE UPDATE ON public.organization_memberships FOR EACH ROW EXECUTE FUNCTION public.handle_updated_at();

DROP TRIGGER IF EXISTS trg_iqamas_updated_at ON public.iqamas;
CREATE TRIGGER trg_iqamas_updated_at BEFORE UPDATE ON public.iqamas FOR EACH ROW EXECUTE FUNCTION public.handle_updated_at();

DROP TRIGGER IF EXISTS trg_contracts_updated_at ON public.contracts;
CREATE TRIGGER trg_contracts_updated_at BEFORE UPDATE ON public.contracts FOR EACH ROW EXECUTE FUNCTION public.handle_updated_at();

DROP TRIGGER IF EXISTS trg_licenses_updated_at ON public.licenses;
CREATE TRIGGER trg_licenses_updated_at BEFORE UPDATE ON public.licenses FOR EACH ROW EXECUTE FUNCTION public.handle_updated_at();

-- ======================================================================
-- 14. دوال فحص الصلاحيات وعزل الكيانات الصارم (Strict Multi-Tenancy Functions)
-- ======================================================================

-- التحقق الصارم من صلاحية المستخدم للوصول إلى مؤسسة معينة (عضوية نشطة فقط)
-- لا يستطيع أي مستخدم أو مدير الوصول لأي بيانات لشركة أخرى ما لم يكن عضواً نشطاً صريحاً فيها
CREATE OR REPLACE FUNCTION public.has_org_access(org_code TEXT)
RETURNS BOOLEAN AS $$
    SELECT EXISTS (
        SELECT 1 FROM public.organization_memberships
        WHERE user_id = auth.uid() 
          AND organization_code = org_code 
          AND status = 'active'
    );
$$ LANGUAGE sql STABLE SECURITY DEFINER;

-- الحصول على دور المستخدم الصريح داخل المؤسسة المحددة
CREATE OR REPLACE FUNCTION public.get_user_org_role(org_code TEXT)
RETURNS TEXT AS $$
    SELECT role FROM public.organization_memberships
    WHERE user_id = auth.uid() 
      AND organization_code = org_code 
      AND status = 'active'
    LIMIT 1;
$$ LANGUAGE sql STABLE SECURITY DEFINER;

-- الحصول على دور المستخدم العام
CREATE OR REPLACE FUNCTION public.current_user_role()
RETURNS TEXT AS $$
    SELECT role FROM public.profiles WHERE id = auth.uid();
$$ LANGUAGE sql STABLE SECURITY DEFINER;

-- ======================================================================
-- 15. دالة معالجة المستخدم الجديد آلياً عند إنشائه بواسطة المدير
-- ======================================================================
CREATE OR REPLACE FUNCTION public.handle_new_auth_user()
RETURNS TRIGGER AS $$
DECLARE
    assigned_org TEXT := COALESCE(NEW.raw_user_meta_data->>'organization_code', 'tarqeem');
    user_role TEXT := COALESCE(NEW.raw_user_meta_data->>'role', 'user');
BEGIN
    INSERT INTO public.profiles (id, email, full_name, role, current_organization_code)
    VALUES (
        NEW.id,
        NEW.email,
        COALESCE(NEW.raw_user_meta_data->>'full_name', split_part(NEW.email, '@', 1)),
        user_role,
        CASE WHEN assigned_org = 'both' THEN 'tarqeem' ELSE assigned_org END
    )
    ON CONFLICT (id) DO NOTHING;

    -- تعيين العضوية الصريحة بناءً على الاختيار
    IF assigned_org = 'both' THEN
        INSERT INTO public.organization_memberships (user_id, organization_code, role, status)
        VALUES 
            (NEW.id, 'tarqeem', user_role, 'active'),
            (NEW.id, 'tafneen', user_role, 'active')
        ON CONFLICT (user_id, organization_code) DO UPDATE 
        SET role = EXCLUDED.role, status = 'active';
    ELSE
        INSERT INTO public.organization_memberships (user_id, organization_code, role, status)
        VALUES (NEW.id, assigned_org, user_role, 'active')
        ON CONFLICT (user_id, organization_code) DO UPDATE 
        SET role = EXCLUDED.role, status = 'active';
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

DROP TRIGGER IF EXISTS on_auth_user_created ON auth.users;
CREATE TRIGGER on_auth_user_created
    AFTER INSERT ON auth.users
    FOR EACH ROW EXECUTE FUNCTION public.handle_new_auth_user();

-- ======================================================================
-- 16. تفعيل أمان مستوى الصفوف (Row Level Security - RLS)
-- ======================================================================
ALTER TABLE public.organizations ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.profiles ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.organization_memberships ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.iqamas ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.contracts ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.licenses ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.system_settings ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.alert_logs ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.import_logs ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.audit_logs ENABLE ROW LEVEL SECURITY;

-- ----------------------------------------------------------------------
-- سياسات Organizations
-- ----------------------------------------------------------------------
CREATE POLICY "Organizations: Read assigned active organizations"
    ON public.organizations FOR SELECT TO authenticated
    USING (public.has_org_access(code));

CREATE POLICY "Organizations: Admin update"
    ON public.organizations FOR UPDATE TO authenticated
    USING (public.has_org_access(code) AND public.get_user_org_role(code) = 'admin');

-- ----------------------------------------------------------------------
-- سياسات Profiles
-- ----------------------------------------------------------------------
CREATE POLICY "Profiles: Read own profile or org peers"
    ON public.profiles FOR SELECT TO authenticated
    USING (id = auth.uid() OR public.current_user_role() = 'admin');

CREATE POLICY "Profiles: Update own profile"
    ON public.profiles FOR UPDATE TO authenticated
    USING (id = auth.uid());

-- ----------------------------------------------------------------------
-- سياسات Organization Memberships
-- ----------------------------------------------------------------------
CREATE POLICY "Memberships: View own memberships or company admin view"
    ON public.organization_memberships FOR SELECT TO authenticated
    USING (
        user_id = auth.uid() 
        OR (public.has_org_access(organization_code) AND public.get_user_org_role(organization_code) = 'admin')
    );

CREATE POLICY "Memberships: Admin insert member"
    ON public.organization_memberships FOR INSERT TO authenticated
    WITH CHECK (
        public.has_org_access(organization_code) 
        AND public.get_user_org_role(organization_code) = 'admin'
    );

CREATE POLICY "Memberships: Admin update member"
    ON public.organization_memberships FOR UPDATE TO authenticated
    USING (
        public.has_org_access(organization_code) 
        AND public.get_user_org_role(organization_code) = 'admin'
    );

CREATE POLICY "Memberships: Admin delete member"
    ON public.organization_memberships FOR DELETE TO authenticated
    USING (
        public.has_org_access(organization_code) 
        AND public.get_user_org_role(organization_code) = 'admin'
    );

-- ----------------------------------------------------------------------
-- سياسات Iqamas (عزل صارم وصلاحيات حسب الدور)
-- ----------------------------------------------------------------------
CREATE POLICY "Iqamas: Select by organization access"
    ON public.iqamas FOR SELECT TO authenticated
    USING (public.has_org_access(organization_code));

CREATE POLICY "Iqamas: Insert by manager or admin"
    ON public.iqamas FOR INSERT TO authenticated
    WITH CHECK (
        public.has_org_access(organization_code) 
        AND public.get_user_org_role(organization_code) IN ('admin', 'manager')
    );

CREATE POLICY "Iqamas: Update by manager or admin"
    ON public.iqamas FOR UPDATE TO authenticated
    USING (
        public.has_org_access(organization_code) 
        AND public.get_user_org_role(organization_code) IN ('admin', 'manager')
    );

CREATE POLICY "Iqamas: Delete by admin only"
    ON public.iqamas FOR DELETE TO authenticated
    USING (
        public.has_org_access(organization_code) 
        AND public.get_user_org_role(organization_code) = 'admin'
    );

-- ----------------------------------------------------------------------
-- سياسات Contracts (عزل صارم وصلاحيات حسب الدور)
-- ----------------------------------------------------------------------
CREATE POLICY "Contracts: Select by organization access"
    ON public.contracts FOR SELECT TO authenticated
    USING (public.has_org_access(organization_code));

CREATE POLICY "Contracts: Insert by manager or admin"
    ON public.contracts FOR INSERT TO authenticated
    WITH CHECK (
        public.has_org_access(organization_code) 
        AND public.get_user_org_role(organization_code) IN ('admin', 'manager')
    );

CREATE POLICY "Contracts: Update by manager or admin"
    ON public.contracts FOR UPDATE TO authenticated
    USING (
        public.has_org_access(organization_code) 
        AND public.get_user_org_role(organization_code) IN ('admin', 'manager')
    );

CREATE POLICY "Contracts: Delete by admin only"
    ON public.contracts FOR DELETE TO authenticated
    USING (
        public.has_org_access(organization_code) 
        AND public.get_user_org_role(organization_code) = 'admin'
    );

-- ----------------------------------------------------------------------
-- سياسات Licenses (عزل صارم وصلاحيات حسب الدور)
-- ----------------------------------------------------------------------
CREATE POLICY "Licenses: Select by organization access"
    ON public.licenses FOR SELECT TO authenticated
    USING (public.has_org_access(organization_code));

CREATE POLICY "Licenses: Insert by manager or admin"
    ON public.licenses FOR INSERT TO authenticated
    WITH CHECK (
        public.has_org_access(organization_code) 
        AND public.get_user_org_role(organization_code) IN ('admin', 'manager')
    );

CREATE POLICY "Licenses: Update by manager or admin"
    ON public.licenses FOR UPDATE TO authenticated
    USING (
        public.has_org_access(organization_code) 
        AND public.get_user_org_role(organization_code) IN ('admin', 'manager')
    );

CREATE POLICY "Licenses: Delete by admin only"
    ON public.licenses FOR DELETE TO authenticated
    USING (
        public.has_org_access(organization_code) 
        AND public.get_user_org_role(organization_code) = 'admin'
    );

-- ----------------------------------------------------------------------
-- سياسات System Settings
-- ----------------------------------------------------------------------
CREATE POLICY "Settings: Select by organization access"
    ON public.system_settings FOR SELECT TO authenticated
    USING (public.has_org_access(organization_code));

CREATE POLICY "Settings: Update by company admin only"
    ON public.system_settings FOR ALL TO authenticated
    USING (
        public.has_org_access(organization_code) 
        AND public.get_user_org_role(organization_code) = 'admin'
    );

-- ----------------------------------------------------------------------
-- سياسات Alert Logs & Import Logs
-- ----------------------------------------------------------------------
CREATE POLICY "AlertLogs: Select by organization access"
    ON public.alert_logs FOR SELECT TO authenticated
    USING (public.has_org_access(organization_code));

CREATE POLICY "AlertLogs: Insert by authorized member"
    ON public.alert_logs FOR INSERT TO authenticated
    WITH CHECK (
        public.has_org_access(organization_code) 
        AND public.get_user_org_role(organization_code) IN ('admin', 'manager')
    );

CREATE POLICY "ImportLogs: Select by organization access"
    ON public.import_logs FOR SELECT TO authenticated
    USING (public.has_org_access(organization_code));

CREATE POLICY "ImportLogs: Insert by authorized member"
    ON public.import_logs FOR INSERT TO authenticated
    WITH CHECK (
        public.has_org_access(organization_code) 
        AND public.get_user_org_role(organization_code) IN ('admin', 'manager')
    );

-- ----------------------------------------------------------------------
-- سياسات Audit Logs
-- ----------------------------------------------------------------------
CREATE POLICY "AuditLogs: Select by organization access"
    ON public.audit_logs FOR SELECT TO authenticated
    USING (
        public.has_org_access(organization_code) 
        AND public.get_user_org_role(organization_code) IN ('admin', 'manager')
    );

CREATE POLICY "AuditLogs: Insert by active member"
    ON public.audit_logs FOR INSERT TO authenticated
    WITH CHECK (public.has_org_access(organization_code));

-- ======================================================================
-- 17. البيانات الأولية الافتراضية (Initial Seed Data)
-- ======================================================================
INSERT INTO public.organizations (code, name, legal_name, commercial_registration, tax_number, is_active)
VALUES
    ('tarqeem', 'مؤسسة ترقيم TRQEM', 'مؤسسة ترقيم للخدمات التجارية', '1010896541', '300055442200003', TRUE),
    ('tafneen', 'شركة تفنين TAFNEEN', 'شركة تفنين لتقنية المعلومات', '2050991423', '310066553300003', TRUE)
ON CONFLICT (code) DO UPDATE 
SET name = EXCLUDED.name, commercial_registration = EXCLUDED.commercial_registration;

INSERT INTO public.system_settings (organization_code, iqama_alert_threshold, contract_alert_threshold, license_alert_threshold, alert_emails, is_alert_enabled)
VALUES
    ('tarqeem', 45, 90, 60, 'hr@tarqeem.sa, alerts@tarqeem.sa', TRUE),
    ('tafneen', 45, 90, 60, 'hr@tafneen.com, management@tafneen.com', TRUE)
ON CONFLICT (organization_code) DO NOTHING;

-- بيانات أولية لمؤسسة ترقيم
INSERT INTO public.iqamas (organization_code, name, iqama_number, profession, branch, issue_date, expiry_date, notes, transfer_renewal_notes)
VALUES
    ('tarqeem', 'محمد أحمد علي', '2345678901', 'مهندس برمجيات', 'فرع الرياض', '2023-01-15', '2026-11-20', 'ساري ومستقر', 'لا يوجد نقل كفالة'),
    ('tarqeem', 'عمر سالم الخالد', '2456789012', 'محاسب عام', 'فرع جدة', '2023-05-10', '2026-11-05', 'تجديد سنوي', 'بانتظار سداد المقابل المالي'),
    ('tarqeem', 'طارق زياد منصور', '2567890123', 'فني شبكات', 'فرع الدمام', '2022-10-01', '2026-10-15', 'منتهي قريباً', 'تم التقديم على منصة قوى'),
    ('tarqeem', 'خالد بن عبد الله', '2678901234', 'مسؤول موارد بشرية', 'فرع الرياض', '2024-02-01', '2027-04-10', 'ساري ومجدد', '')
ON CONFLICT (organization_code, iqama_number) DO NOTHING;

INSERT INTO public.contracts (organization_code, name, national_id, profession, skill_classification, department, branch, contract_start_date, contract_end_date, renewals_count, notes)
VALUES
    ('tarqeem', 'محمد أحمد علي', '2345678901', 'مهندس برمجيات', 'محترف', 'تقنية المعلومات', 'فرع الرياض', '2024-01-01', '2026-12-30', 2, 'تجديد سنوي تلقائي'),
    ('tarqeem', 'عمر سالم الخالد', '2456789012', 'محاسب عام', 'متوسط', 'الإدارة المالية', 'فرع جدة', '2023-11-01', '2026-11-15', 1, 'إشعار بالتجديد قبل 90 يوم'),
    ('tarqeem', 'طارق زياد منصور', '2567890123', 'فني شبكات', 'ماهر', 'الدعم الفني', 'فرع الدمام', '2024-03-01', '2027-02-28', 0, 'عقد ساري مدة سنتين')
ON CONFLICT (organization_code, national_id) DO NOTHING;

INSERT INTO public.licenses (organization_code, document_name, document_type, document_number, branch, issue_date, expiry_date, notes)
VALUES
    ('tarqeem', 'رخصة البلدية المركزية', 'بلدي', 'BLD-78945', 'فرع الرياض', '2023-01-10', '2026-11-25', 'ساري'),
    ('tarqeem', 'السجل التجاري الرئيسي', 'تجاري', 'CR-1010896', 'المركز الرئيسي', '2022-05-15', '2027-05-15', 'مجدد لخمس سنوات'),
    ('tarqeem', 'شهادة الدفاع المدني', 'سلامة', 'CD-45120', 'فرع جدة', '2024-02-01', '2026-10-30', 'قريب الانتهاء')
ON CONFLICT (organization_code, document_number) DO NOTHING;

-- بيانات أولية لشركة تفنين
INSERT INTO public.iqamas (organization_code, name, iqama_number, profession, branch, issue_date, expiry_date, notes, transfer_renewal_notes)
VALUES
    ('tafneen', 'سعيد عبد الرحمن', '2789012345', 'مدير تسويق', 'المقر الرئيسي', '2023-06-15', '2026-11-12', 'تفنين', 'تم نقل الكفالة بنجاح'),
    ('tafneen', 'باسم فيصل الحربي', '2890123456', 'مصمم جرافيك', 'فرع الخبر', '2024-01-10', '2027-01-20', 'ساري', 'كفالة داخلية')
ON CONFLICT (organization_code, iqama_number) DO NOTHING;

INSERT INTO public.contracts (organization_code, name, national_id, profession, skill_classification, department, branch, contract_start_date, contract_end_date, renewals_count, notes)
VALUES
    ('tafneen', 'سعيد عبد الرحمن', '2789012345', 'مدير تسويق', 'خبير', 'التسويق', 'المقر الرئيسي', '2024-01-01', '2026-12-15', 1, 'مكافأة تجديد سنوي'),
    ('tafneen', 'باسم فيصل الحربي', '2890123456', 'مصمم جرافيك', 'محترف', 'الإعلام الرقمي', 'فرع الخبر', '2024-06-01', '2027-05-31', 0, 'عقد عمل سنوي')
ON CONFLICT (organization_code, national_id) DO NOTHING;

INSERT INTO public.licenses (organization_code, document_name, document_type, document_number, branch, issue_date, expiry_date, notes)
VALUES
    ('tafneen', 'رخصة بلدية تفنين', 'بلدي', 'TF-8890', 'فرع الرياض', '2023-08-01', '2027-08-01', 'ساري'),
    ('tafneen', 'سجل تجاري شركة تفنين', 'تجاري', 'CR-2050991', 'المركز الرئيسي', '2023-01-15', '2028-01-15', 'ساري ومستقر')
ON CONFLICT (organization_code, document_number) DO NOTHING;
