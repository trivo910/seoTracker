<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'email', 'status', 'failure_reason',
        'ip_address', 'user_agent', 'country', 'city',
    ];

    protected $casts = [
        'created_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
