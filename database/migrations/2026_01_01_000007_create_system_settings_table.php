<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->integer('iqama_alert_threshold')->default(45)->comment('حد التنبيه للإقامات بالأيام (افتراضي 45)');
            $table->integer('contract_alert_threshold')->default(90)->comment('حد التنبيه للعقود بالأيام (افتراضي 90)');
            $table->text('alert_emails')->nullable()->comment('قائمة الإيميلات المستلمة مفصولة بفواصل');
            $table->boolean('is_alert_enabled')->default(true)->comment('حالة تفعيل التنبيهات');
            $table->timestamps();

            $table->unique('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
