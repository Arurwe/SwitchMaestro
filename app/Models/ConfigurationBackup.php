<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfigurationBackup extends Model
{
   protected $fillable = [
        'device_id',
        'user_id',
        'configuration',
    ];

    public function device()
    {
         return $this->belongsTo(Device::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
