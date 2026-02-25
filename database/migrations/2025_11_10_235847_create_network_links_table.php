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

        Schema::create('network_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('local_device_id')->constrained('devices')->onDelete('cascade');
            $table->string('local_port_name');
            $table->string('neighbor_device_hostname');
            $table->string('neighbor_port_name');
            $table->foreignId('neighbor_device_id')->nullable()->constrained('devices')->onDelete('set null');
            $table->timestamp('discovered_at');
            $table->timestamps();
            $table->unique(['local_device_id', 'local_port_name']);
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('network_links');
    }
};
