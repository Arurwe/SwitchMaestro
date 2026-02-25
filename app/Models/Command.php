<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Command extends Model
{
  protected $fillable = [
    'vendor_id',
    'action_id',
    'user_id',
    'commands',
    'description',
  ];

protected $casts = [
        'commands' => 'array',
    ];

  public function vendor()
  {
    return $this->belongsTo(Vendor::class);
  }

  public function action()
  {
    return $this->belongsTo(Action::class);
  }

  public function user()
  {
    return $this->belongsTo(User::class);
  }
}
