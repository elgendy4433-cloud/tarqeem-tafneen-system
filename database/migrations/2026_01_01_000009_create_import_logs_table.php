<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('module_type')->comment('iqamas, contracts, licenses');
            $table->string('file_name');
            $table->integer('total_rows')->default(0);
            $table->integer('inserted_rows')->default(0);
            $table->integer('updated_rows')->default(0);
            $table->integer('failed_rows')->default(0);
            $table->json('error_details')->nullable()->comment('تفاصيل الأخطاء والصفوف غير الصالحة');
            $table->timestamps();

            $table->index(['organization_id', 'module_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
