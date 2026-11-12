<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('company_name');
            $table->text('address');
            $table->string('tax_id', 32)->nullable();          // NPWP / NIK
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number', 64)->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('signature_path')->nullable();      // stempel / TTE
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_profiles');
    }
};
