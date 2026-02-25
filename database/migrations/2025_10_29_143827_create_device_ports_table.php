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
         Schema::create('device_ports', function (Blueprint $table) {

            $table->id();
            $table->foreignId('device_id')->constrained('devices')->onDelete('cascade');
            $table->string('name')->index();
            $table->string('status')->nullable()->index();
            $table->string('protocol_status')->nullable()->index();
            $table->string('description')->nullable();
            $table->string('speed')->nullable();
            $table->string('duplex')->nullable();
            $table->json('vlan')->nullable();
            $table->unique(['device_id', 'name']);
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_ports');
    }
};
