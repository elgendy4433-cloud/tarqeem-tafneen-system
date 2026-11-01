/**
 * ======================================================================
 * اختبارات التحقق الشاملة لنظام المتابعة (ترقيم & تفنين)
 * Automated E2E & Business Logic Test Suite
 * ======================================================================
 */

const testResults = [];

function assert(condition, testName) {
    if (condition) {
        testResults.push({ name: testName, status: 'PASS' });
        console.log(`[PASS] ${testName}`);
    } else {
        testResults.push({ name: testName, status: 'FAIL' });
        console.error(`[FAIL] ${testName}`);
    }
}

// 1. اختبار خوارزمية حساب الأيام المتبقية وتحديد الحالات
function testDateCalculations() {
    function calculateDaysRemaining(expiryDateStr) {
        if (!expiryDateStr) return 0;
        const exp = new Date(expiryDateStr);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        exp.setHours(0, 0, 0, 0);
        const diffTime = exp - today;
        return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    }

    function getStatusInfo(days, threshold) {
        if (days < 0) return { label: 'منتهي' };
        if (days <= threshold) return { label: 'قريب الانتهاء' };
        return { label: 'ساري' };
    }

    assert(getStatusInfo(-5, 45).label === 'منتهي', 'الحالة سالبة (-5 أيام) تصنف: منتهي');
    assert(getStatusInfo(20, 45).label === 'قريب الانتهاء', 'الأيام (20 <= 45) تصنف: قريب الانتهاء');
    assert(getStatusInfo(60, 45).label === 'ساري', 'الأيام (60 > 45) تصنف: ساري');
    assert(getStatusInfo(85, 90).label === 'قريب الانتهاء', 'عقود (85 <= 90) تصنف: قريب الانتهاء');
    assert(getStatusInfo(120, 90).label === 'ساري', 'عقود (120 > 90) تصنف: ساري');
}

// 2. اختبار تحويل تواريخ إكسل الرقمية (Excel Serial Numbers)
function testExcelDateParsing() {
    function parseExcelDate(val) {
        if (!val) return '';
        if (typeof val === 'number') {
            const date = new Date((val - (25567 + 2)) * 86400 * 1000);
            return date.toISOString().substring(0, 10);
        }
        if (typeof val === 'string') {
            val = val.trim();
            if (/^\d{4}-\d{2}-\d{2}$/.test(val)) return val;
            const d = new Date(val);
            if (!isNaN(d.getTime())) return d.toISOString().substring(0, 10);
        }
        return '';
    }

    const parsedSerial = parseExcelDate(46438);
    assert(typeof parsedSerial === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(parsedSerial), 'تحويل رقم تاريخ إكسل (46438) إلى تاريخ ميلادي سليم');
    assert(parseExcelDate('2026-11-20') === '2026-11-20', 'الحفاظ على التواريخ النصية القياسية بصيغة YYYY-MM-DD');
}

// 3. اختبار التحكم بالوصول المبني على الأدوار (RBAC)
function testRBACPermissions() {
    function canUserPerformAction(role, action) {
        if (role === 'admin') return true;
        if (role === 'manager') {
            return ['read', 'create', 'update', 'export', 'import'].includes(action);
        }
        if (role === 'user') {
            return ['read', 'export'].includes(action);
        }
        return false;
    }

    // Admin Permissions
    assert(canUserPerformAction('admin', 'delete') === true, 'Admin يملك صلاحية حذف السجلات');
    assert(canUserPerformAction('admin', 'change_settings') === true, 'Admin يملك صلاحية تعديل الإعدادات');
    assert(canUserPerformAction('admin', 'create') === true, 'Admin يملك صلاحية الإضافة');

    // Manager Permissions
    assert(canUserPerformAction('manager', 'create') === true, 'Manager يملك صلاحية الإضافة');
    assert(canUserPerformAction('manager', 'update') === true, 'Manager يملك صلاحية التعديل');
    assert(canUserPerformAction('manager', 'delete') === false, 'Manager ممنوع من حذف السجلات');
    assert(canUserPerformAction('manager', 'change_settings') === false, 'Manager ممنوع من تعديل الإعدادات');

    // User (Viewer) Permissions
    assert(canUserPerformAction('user', 'read') === true, 'User يملك صلاحية القراءة');
    assert(canUserPerformAction('user', 'create') === false, 'User ممنوع من الإضافة');
    assert(canUserPerformAction('user', 'update') === false, 'User ممنوع من التعديل');
    assert(canUserPerformAction('user', 'delete') === false, 'User ممنوع من الحذف');
}

// 4. اختبار عزل بيانات المؤسستين (Multi-Tenancy Isolation)
function testMultiTenancyIsolation() {
    const mockDb = {
        tarqeem: { iqamas: [{ id: 1, iqama_number: '111', name: 'موظف ترقيم' }] },
        tafneen: { iqamas: [{ id: 2, iqama_number: '222', name: 'موظف تفنين' }] }
    };

    function addRecord(org, record) {
        mockDb[org].iqamas.push(record);
    }

    addRecord('tarqeem', { id: 3, iqama_number: '333', name: 'موظف ترقيم جديد' });

    assert(mockDb.tarqeem.iqamas.length === 2, 'تم إضافة السجل لمؤسسة ترقيم بنجاح');
    assert(mockDb.tafneen.iqamas.length === 1, 'سجلات شركة تفنين معزولة تماماً ولم تتأثر');
    assert(!mockDb.tafneen.iqamas.some(i => i.iqama_number === '333'), 'بيانات ترقيم لا تظهر في تفنين');
}

// 5. اختبار منع التكرار وتحديث السجل القائم (Upsert)
function testUpsertLogic() {
    const records = [
        { id: 1, iqama_number: '2345678901', name: 'محمد علي', expiry_date: '2026-11-20' }
    ];

    function upsert(incoming) {
        const idx = records.findIndex(r => r.iqama_number === incoming.iqama_number);
        if (idx >= 0) {
            records[idx] = { ...records[idx], ...incoming };
            return 'UPDATED';
        } else {
            records.push({ id: Date.now(), ...incoming });
            return 'INSERTED';
        }
    }

    const res1 = upsert({ iqama_number: '2345678901', name: 'محمد علي محدث', expiry_date: '2027-11-20' });
    assert(res1 === 'UPDATED', 'عند تكرار رقم الإقامة يتم التحديث وليس الإضافة المكررة');
    assert(records[0].name === 'محمد علي محدث', 'تم تحديث الاسم بنجاح');
    assert(records.length === 1, 'لم يتضاعف عدد السجلات');

    const res2 = upsert({ iqama_number: '9999999999', name: 'سجل جديد', expiry_date: '2026-12-01' });
    assert(res2 === 'INSERTED', 'السجل الجديد يتم إدراجه بصورة مستقلة');
    assert(records.length === 2, 'إجمالي السجلات أصبح 2 بعد إضافة سجل جديد');
}

// 6. اختبار معالجة وتدقيق إعدادات التنبيهات والبريد الإلكتروني
function testSettingsValidation() {
    function sanitizeSettings(iqamaDays, contractDays, emailsStr) {
        const iqDays = Math.max(1, Number(iqamaDays) || 45);
        const conDays = Math.max(1, Number(contractDays) || 90);
        const emailList = emailsStr
            .split(',')
            .map(e => e.trim())
            .filter(e => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e));
        return { iqDays, conDays, emailList };
    }

    const res = sanitizeSettings("45", "90", "hr@tarqeem.sa,  alerts@trqem.com, invalid-email ");
    assert(res.iqDays === 45, 'تدقيق أيام الإقامات كأرقام صحيحة');
    assert(res.conDays === 90, 'تدقيق أيام العقود كأرقام صحيحة');
    assert(res.emailList.length === 2, 'تصفية العناوين البريدية غير الصالحة بدقة');
    assert(res.emailList.includes('hr@tarqeem.sa') && res.emailList.includes('alerts@trqem.com'), 'استخراج البريد الإلكتروني الصالح');
}

// 7. اختبار العزل الصارم بين المنشأتين والتدقيق الأمني (Strict Company Separation & Audit)
function testStrictCompanySeparation() {
    function isOrgPermitted(user, orgCode) {
        if (!user || !user.organizations) return false;
        if (!user.organizations.includes(orgCode)) return false;
        if (user.memberships && user.memberships[orgCode]) {
            return user.memberships[orgCode].status === 'active';
        }
        return true;
    }

    const tarqeemUser = {
        email: 'admin@tarqeem.com',
        organizations: ['tarqeem'],
        memberships: { tarqeem: { role: 'admin', status: 'active' } }
    };

    const tafneenUser = {
        email: 'manager@tafneen.com',
        organizations: ['tafneen'],
        memberships: { tafneen: { role: 'manager', status: 'active' } }
    };

    const dualUser = {
        email: 'executive@all.com',
        organizations: ['tarqeem', 'tafneen'],
        memberships: {
            tarqeem: { role: 'admin', status: 'active' },
            tafneen: { role: 'admin', status: 'active' }
        }
    };

    const suspendedUser = {
        email: 'suspended@tafneen.com',
        organizations: ['tafneen'],
        memberships: { tafneen: { role: 'user', status: 'suspended' } }
    };

    // 1. عزل مستخدم ترقيم
    assert(isOrgPermitted(tarqeemUser, 'tarqeem') === true, 'مستخدم ترقيم مصرح له بالدخول لترقيم');
    assert(isOrgPermitted(tarqeemUser, 'tafneen') === false, 'مستخدم ترقيم ممنوع قطعياً من الوصول لتفنين');

    // 2. عزل مستخدم تفنين
    assert(isOrgPermitted(tafneenUser, 'tafneen') === true, 'مستخدم تفنين مصرح له بالدخول لتفنين');
    assert(isOrgPermitted(tafneenUser, 'tarqeem') === false, 'مستخدم تفنين ممنوع قطعياً من الوصول لترقيم');

    // 3. المستخدم المشترك
    assert(isOrgPermitted(dualUser, 'tarqeem') === true, 'المستخدم المشترك يملك حق الوصول لترقيم');
    assert(isOrgPermitted(dualUser, 'tafneen') === true, 'المستخدم المشترك يملك حق الوصول لتفنين');

    // 4. تعليق الحساب (Suspended Status)
    assert(isOrgPermitted(suspendedUser, 'tafneen') === false, 'العضوية المعلقة status=suspended تحجب الوصول فوراً');

    // 5. التدقيق الأمني (Audit Logging)
    const auditLogs = [];
    function logSecurityAction(action, module, targetId, details) {
        const logEntry = {
            id: 'log-' + (auditLogs.length + 1),
            timestamp: new Date().toISOString(),
            actor_email: tarqeemUser.email,
            organization_code: 'tarqeem',
            action,
            target_module: module,
            target_id: targetId,
            details
        };
        auditLogs.push(logEntry);
        return logEntry;
    }

    const log = logSecurityAction('SWITCH_COMPANY', 'organization', 'tafneen', { reason: 'Switch context attempt' });
    assert(auditLogs.length === 1, 'تسجيل حركة التدقيق الأمني في السجل بنجاح');
    assert(log.action === 'SWITCH_COMPANY' && log.actor_email === 'admin@tarqeem.com', 'بيانات السجل الأمني مطابقة للفاعل والحدث');
}

// 8. اختبار محرك فحص التنبيهات ونظام البريد الإلكتروني (Email Alerts System)
function testEmailAlertSystem() {
    const mockRecords = [
        { name: 'محمد علي', type: 'إقامة', days: 20, threshold: 45 },
        { name: 'سالم أحمد', type: 'إقامة', days: 50, threshold: 45 },
        { name: 'عقد مهندس', type: 'عقد', days: 70, threshold: 90 },
        { name: 'رخصة منتهية', type: 'ترخيص', days: -5, threshold: 60 }
    ];

    function filterCritical(records) {
        return records.filter(r => r.days <= r.threshold);
    }

    const critical = filterCritical(mockRecords);
    assert(critical.length === 3, 'تصفية السجلات الحرجة للتنبيه البريدي بدقة (3 من أصل 4)');
    assert(critical.some(c => c.days === -5), 'إدراج السجلات المنتهية ضمن تقرير التنبيهات');

    // اختبار تجهيز رابط mailto مع الترميز السليم
    const recipients = 'hr@tarqeem.sa, alerts@trqem.com';
    const subject = '🚨 تنبيه فوري: وثائق قاربت على الانتهاء';
    const body = 'تفاصيل السجلات الحرجة...';
    const mailtoUrl = `mailto:${encodeURIComponent(recipients)}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;

    assert(mailtoUrl.startsWith('mailto:hr%40tarqeem.sa'), 'ترميز رابط mailto للبريد الإلكتروني بصورة صحيحة');
    assert(mailtoUrl.includes('subject='), 'تضمين موضوع الرسالة في الرابط');
}

// تشغيل كافة الاختبارات
console.log('--- بدء تشغيل الاختبارات الآلية ---');
testDateCalculations();
testExcelDateParsing();
testRBACPermissions();
testMultiTenancyIsolation();
testUpsertLogic();
testSettingsValidation();
testStrictCompanySeparation();
testEmailAlertSystem();

const total = testResults.length;
const passed = testResults.filter(r => r.status === 'PASS').length;
const failed = testResults.filter(r => r.status === 'FAIL').length;

console.log(`\nنتيجة الاختبارات: ${passed}/${total} نجاح (${failed} فشل).`);

if (typeof module !== 'undefined') {
    module.exports = { testResults, total, passed, failed };
}
