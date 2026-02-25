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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ip_address');
            $table->integer('port')->default(22);
            $table->text('description')->nullable();
            $table->string('status')->default('unknown'); 
            $table->foreignId('vendor_id')->constrained('vendors');
            $table->json('vlan_list')->nullable();
            $table->foreignId('credential_id')->constrained('credentials');
            $table->string('software_version')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('uptime')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
