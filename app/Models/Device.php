<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\VendorTagPublished;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Device extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'ip_address',
        'port',
        'description',
        'status',
        'vendor_id',
        'credential_id',
        'driver_override',
        'software_version',
        'model',
        'serial_number',
        'uptime',
    ];

    protected $casts = [
        'port' => 'integer',
    ];



    public function getActivitylogOptions():LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('switch')
            ->setDescriptionForEvent(fn(string $eventName) => "Switch '{$this->name}' {$eventName}");
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    } 

    public function credential()
    {
        return $this->belongsTo(Credential::class);
    }

    public function configurationBackups()
    {
        return $this->hasMany(configurationBackup::class);
    }

    public function deviceGroups()
    {
        return $this->belongsToMany(DeviceGroup::class, 'device_device_group');
    }

    public function ports()
    {
        return $this->hasMany(DevicePort::class);
    }

    public function taskLogs()
    {
        return $this->hasMany(TaskLog::class);
    }

    public function effectiveDriver()
    {
        return $this->driver_override ?? $this->vendor->netmiko_driver;
    }
    public function vlans()
{
    return $this->belongsToMany(Vlan::class, 'device_vlan')
                ->withPivot(['type', 'route_interface']); 
}
}
