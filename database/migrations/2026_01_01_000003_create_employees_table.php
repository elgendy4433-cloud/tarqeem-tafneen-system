<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name')->comment('اسم الموظف');
            $table->string('national_id')->comment('رقم الهوية / رقم الإقامة');
            $table->string('profession')->nullable()->comment('المهنة');
            $table->string('skill_classification')->nullable()->comment('تصنيف المهارات');
            $table->string('department')->nullable()->comment('الإدارة');
            $table->string('branch')->nullable()->comment('الفرع');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('notes')->nullable()->comment('ملاحظات عامة');
            $table->timestamps();

            $table->unique(['organization_id', 'national_id']);
            $table->index(['organization_id', 'branch']);
            $table->index(['organization_id', 'department']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
