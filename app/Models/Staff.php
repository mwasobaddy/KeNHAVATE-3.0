<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    protected $fillable = [
        'user_id',
        'work_email',
        'personal_email',
        'region_id',
        'directorate_id',
        'department_id',
        'designation',
        'employment_type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
