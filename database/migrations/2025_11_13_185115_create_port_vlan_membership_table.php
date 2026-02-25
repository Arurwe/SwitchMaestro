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
        Schema::create('port_vlan_membership', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_port_id')->constrained('device_ports')->onDelete('cascade');
            $table->foreignId('vlan_id')->constrained('vlans')->onDelete('cascade');
            $table->string('membership_type')->nullable();
            $table->timestamps();
            $table->unique(['device_port_id', 'vlan_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('port_vlan_membership');
    }
};
