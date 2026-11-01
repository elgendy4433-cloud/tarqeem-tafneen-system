<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('اسم المؤسسة: ترقيم / تفنين');
            $table->string('code')->unique()->comment('رمز المؤسسة: tarqeem / tafneen');
            $table->string('tax_number')->nullable()->comment('الرقم الضريبي');
            $table->string('commercial_registration')->nullable()->comment('السجل التجاري');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
