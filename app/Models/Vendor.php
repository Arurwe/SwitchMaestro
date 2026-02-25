<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $fillable = ['name', 'netmiko_driver'];

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    public function commands()
    {
        return $this->hasMany(Command::class);
    }
}
