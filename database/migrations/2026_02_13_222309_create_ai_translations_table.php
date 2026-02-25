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
        Schema::create('ai_translations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('source_vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->foreignId('target_vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->longText('input_commands');
            $table->longText('translated_commands');
            $table->text('error_message')->nullable();
            $table->string('model_name')->nullable();

            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_translations');
    }
};
