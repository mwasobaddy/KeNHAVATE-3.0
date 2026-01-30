<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Point extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'reason',
        'awarded_by',
        'awarded_at',
    ];

    protected $casts = [
        'awarded_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function awardedBy()
    {
        return $this->belongsTo(User::class, 'awarded_by');
    }
}
