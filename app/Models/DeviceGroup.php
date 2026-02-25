<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DeviceGroup extends Model
{
    use LogsActivity;
    
   protected $fillable = ['name', 'description'];

    public function getActivitylogOptions():LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('device_groups')
            ->setDescriptionForEvent(fn(string $eventName) => "device_groups '{$this->name}' {$eventName}");
    }

    public function devices()
    {
        return $this->belongsToMany(Device::class, 'device_device_group');
    }
}
