<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('document_name')->comment('اسم الوثيقة');
            $table->string('document_type')->nullable()->comment('النوع (ترخيص بلدي، سجل تجاري، غرفة تجارية، إلخ)');
            $table->string('document_number')->comment('رقم الوثيقة / الترخيص');
            $table->string('branch')->nullable()->comment('الفرع المستفيد');
            $table->date('issue_date')->nullable()->comment('تاريخ الإصدار');
            $table->date('expiry_date')->comment('تاريخ الانتهاء الميلادي');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->timestamps();

            $table->unique(['organization_id', 'document_number']);
            $table->index(['organization_id', 'expiry_date']);
            $table->index(['organization_id', 'branch']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
