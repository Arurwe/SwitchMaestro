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
       Schema::table('device_ports', function (Blueprint $table) {
            if (Schema::hasColumn('device_ports', 'vlan')) {
                $table->dropColumn('vlan');
            }
        });
        Schema::table('devices', function (Blueprint $table) {
            if (Schema::hasColumn('devices', 'vlan_list')) {
                $table->dropColumn('vlan_list');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('device_ports', function (Blueprint $table) {
            $table->json('vlan')->nullable();
        });
        Schema::table('devices', function (Blueprint $table) {
            $table->json('vlan_list')->nullable();
        });
    }
};
