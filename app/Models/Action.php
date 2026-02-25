<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Action extends Model
{
    protected $fillable = [
        'name',
        'action_slug',
        'description',
    ];

    public function commands()
    {
        return $this->hasMany(Command::class);
    }
}
