<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('module_type')->comment('iqama, contract');
            $table->unsignedBigInteger('record_id')->comment('معرف السجل في جدول الإقامات أو العقود');
            $table->string('record_name')->comment('اسم الموظف أو صاحب السجل');
            $table->string('record_identifier')->comment('رقم الإقامة أو الهوية');
            $table->integer('days_remaining_at_send')->comment('الأيام المتبقية وقت إرسال التنبيه');
            $table->string('recipient_email')->comment('البريد المستلم');
            $table->string('subject')->comment('عنوان البريد');
            $table->string('status')->default('sent')->comment('sent, failed');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index(['organization_id', 'module_type']);
            $table->index(['organization_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_logs');
    }
};
