<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('name')->comment('الاسم');
            $table->string('national_id')->comment('رقم الهوية / الإقامة');
            $table->string('profession')->nullable()->comment('المهنة');
            $table->string('skill_classification')->nullable()->comment('تصنيف المهارات');
            $table->string('department')->nullable()->comment('الإدارة');
            $table->string('branch')->nullable()->comment('الفرع');
            $table->date('contractual_relation_start')->nullable()->comment('بداية العلاقة التعاقدية');
            $table->date('contract_start_date')->nullable()->comment('بداية العقد');
            $table->date('contract_end_date')->comment('نهاية العقد (المعتمد لحساب الأيام والحالة)');
            $table->integer('renewals_count')->default(0)->comment('عدد مرات التجديد');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->timestamp('alert_sent_at')->nullable()->comment('تاريخ ووقت إرسال التنبيه الفوري');
            $table->date('alerted_for_expiry')->nullable()->comment('تاريخ نهاية العقد الذي أُرسل من أجله التنبيه');
            $table->timestamps();

            $table->unique(['organization_id', 'national_id']);
            $table->index(['organization_id', 'contract_end_date']);
            $table->index(['organization_id', 'department']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
