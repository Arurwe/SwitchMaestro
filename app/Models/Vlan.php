<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vlan extends Model
{

    protected $fillable = [
        'vlan_id',
        'name',
        'description',
    ];


    public function devices()
    {
        return $this->belongsToMany(Device::class, 'device_vlan');

    }


    public function portMemberships()
    {
        return $this->hasMany(PortVlanMembership::class);
    }
}