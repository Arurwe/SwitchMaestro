<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Credential extends Model
{
    use LogsActivity;
    
    protected $fillable = ['name', 'username', 'password', 'secret'];
    protected $hidden = ['password', 'secret'];

    public function getActivitylogOptions():LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logExcept(['password', 'secret'])
            ->logOnlyDirty()
            ->useLogName('Credentials')
            ->setDescriptionForEvent(fn(string $eventName) => "Credential '{$this->name}' {$eventName}");
    }


    public function tapActivity(Activity $activity, string $eventName)
    {
        if ($this->isDirty('password')) {
            $activity->properties = $activity->properties->put('password', 'zmieniono hasło');
        }
        
        if ($this->isDirty('secret')) {
            $activity->properties = $activity->properties->put('secret', 'zmieniono sekret');
        }
    }
    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    protected function password(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => Crypt::encryptString($value) 
        );
    }
    protected function secret(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => $value !== null ? Crypt::encryptString($value) : null 
            );
    }
}
