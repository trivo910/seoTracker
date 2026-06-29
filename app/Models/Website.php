<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Website extends Model
{
    protected $fillable = ['name', 'domain', 'base_url', 'is_active', 'created_by'];
    protected $casts    = ['is_active' => 'boolean'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function keywords()
    {
        return $this->hasMany(Keyword::class);
    }
}
