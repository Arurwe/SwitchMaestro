<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortVlanMembership extends Model
{
    protected $table = 'port_vlan_membership';

    protected $fillable = [
        'device_port_id',
        'vlan_id',
        'membership_type', 
    ];


    public function port()
    {
        return $this->belongsTo(DevicePort::class, 'device_port_id');
    }


    public function vlan()
    {
        return $this->belongsTo(Vlan::class);
    }
}