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
        Schema::create('actions', function (Blueprint $table) {
            $table->id();
            $table->string('name'); 
            $table->string('action_slug')->unique();
            $table->text('description')->nullable(); 
            $table->timestamps();
        });
        
        
        Schema::create('commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade'); 
            $table->foreignId('action_id')->constrained('actions')->onDelete('cascade'); 
            $table->foreignId('user_id')
                ->default(1) 
                ->constrained('users')
                ->onDelete('set null')
                ->nullable(); 
            $table->json('commands');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['vendor_id', 'action_id']); 
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commands');
        Schema::dropIfExists('actions');
    }
};
