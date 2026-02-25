<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DevicePort extends Model
{
    protected $table = 'device_ports';

    protected $fillable = [
        'device_id',
        'name',
        'status',
        'protocol_status',
        'description',
        'speed',
        'duplex',
        'details',
    ];

    protected $casts = [
        'details' => 'json',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }


    public function vlanMemberships()
    {
        return $this->hasMany(PortVlanMembership::class, 'device_port_id');
    }


    public function vlans()
    {
        return $this->belongsToMany(Vlan::class, 'port_vlan_membership')
                    ->withPivot('membership_type');
    }
}
