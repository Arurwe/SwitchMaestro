<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkLink extends Model
{

    protected $table = 'network_links';

    protected $fillable = [
        'local_device_id',
        'local_port_name',
        'neighbor_device_hostname',
        'neighbor_port_name',
        'neighbor_device_id',
        'discovered_at',
    ];


    protected $casts = [
        'discovered_at' => 'datetime',
    ];


    public function localDevice(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'local_device_id');
    }


    public function neighborDevice(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'neighbor_device_id');
    }
}