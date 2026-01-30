<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointsConfiguration extends Model
{
    protected $fillable = [
        'event',
        'points',
        'set_by',
    ];

    public function setBy()
    {
        return $this->belongsTo(User::class, 'set_by');
    }
}
