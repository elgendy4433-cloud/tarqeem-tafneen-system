<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iqamas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('name')->comment('الاسم');
            $table->string('iqama_number')->comment('رقم الإقامة');
            $table->string('profession')->nullable()->comment('المهنة');
            $table->string('branch')->nullable()->comment('الفرع');
            $table->date('issue_date')->nullable()->comment('تاريخ الإصدار إن وجد');
            $table->date('expiry_date')->comment('تاريخ الانتهاء الميلادي');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->text('transfer_renewal_notes')->nullable()->comment('ملاحظات النقل والتجديد');
            $table->timestamp('alert_sent_at')->nullable()->comment('تاريخ ووقت إرسال التنبيه الفوري');
            $table->date('alerted_for_expiry')->nullable()->comment('تاريخ الانتهاء الذي أُرسل من أجله التنبيه لمنع التكرار');
            $table->timestamps();

            $table->unique(['organization_id', 'iqama_number']);
            $table->index(['organization_id', 'expiry_date']);
            $table->index(['organization_id', 'branch']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iqamas');
    }
};
