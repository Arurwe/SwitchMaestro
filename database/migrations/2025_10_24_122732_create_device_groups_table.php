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
        Schema::create('device_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('device_device_group', function (Blueprint $table) {
            $table->primary(['device_id', 'device_group_id']);
            $table->foreignId('device_id')->constrained('devices')->onDelete('cascade');
            $table->foreignId('device_group_id')->constrained('device_groups')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_groups');
        Schema::dropIfExists('device_device_group');
    }
};
