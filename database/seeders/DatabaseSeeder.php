<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ١. إنشاء مؤسسة ترقيم
        $tarqeem = Organization::firstOrCreate(
            ['code' => 'tarqeem'],
            [
                'name' => 'مؤسسة ترقيم',
                'is_active' => true,
            ]
        );

        SystemSetting::firstOrCreate(
            ['organization_id' => $tarqeem->id],
            [
                'iqama_alert_threshold' => 45,
                'contract_alert_threshold' => 90,
                'alert_emails' => 'hr@tarqeem.com',
                'is_alert_enabled' => true,
            ]
        );

        // ٢. إنشاء شركة تفنين (مطابقة تماماً لهيكل ترقيم)
        $tafneen = Organization::firstOrCreate(
            ['code' => 'tafneen'],
            [
                'name' => 'شركة تفنين',
                'is_active' => true,
            ]
        );

        SystemSetting::firstOrCreate(
            ['organization_id' => $tafneen->id],
            [
                'iqama_alert_threshold' => 45,
                'contract_alert_threshold' => 90,
                'alert_emails' => 'hr@tafneen.com',
                'is_alert_enabled' => true,
            ]
        );

        // ٣. إنشاء المستخدم الرئيسي
        $admin = User::firstOrCreate(
            ['email' => 'admin@tarqeem.com'],
            [
                'name' => 'مدير النظام',
                'password' => Hash::make('admin123456'),
                'current_organization_id' => $tarqeem->id,
                'role' => 'admin',
            ]
        );

        // ربط المستخدم بكلتا المؤسستين
        $admin->organizations()->syncWithoutDetaching([
            $tarqeem->id => ['role' => 'owner'],
            $tafneen->id => ['role' => 'owner'],
        ]);
    }
}
