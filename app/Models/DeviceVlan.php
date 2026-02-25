<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class DeviceVlan extends Pivot
{
    protected $table = 'device_vlan';


    
    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function vlan()
    {
        return $this->belongsTo(Vlan::class);
    }
}