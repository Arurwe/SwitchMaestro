<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiTranslation extends Model
{
    protected $fillable = [
        'user_id',
        'source_vendor_id',
        'target_vendor_id',
        'input_commands',
        'translated_commands',
        'error_message',
        'model_name',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sourceVendor()
    {
        return $this->belongsTo(Vendor::class, 'source_vendor_id');
    }

    public function targetVendor()
    {
        return $this->belongsTo(Vendor::class, 'target_vendor_id');
    }
}
