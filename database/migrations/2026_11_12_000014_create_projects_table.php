<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', [
                'Perencanaan',
                'Sedang Berjalan',
                'Menunggu Review',
                'Selesai',
                'Dibatalkan',
            ])->default('Perencanaan');
            $table->unsignedTinyInteger('progress_percentage')->default(0);
            $table->decimal('budget', 15, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('deadline')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'client_id']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
