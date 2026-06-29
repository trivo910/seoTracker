<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Keyword extends Model
{
    protected $fillable = [
        'website_id', 'keyword', 'target_url', 'currency',
        'monthly_searches', 'semrush_volume', 'competition',
        'intent', 'kd', 'is_active', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_active'         => 'boolean',
        'monthly_searches'  => 'integer',
        'semrush_volume'    => 'integer',
        'kd'                => 'integer',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    public function website()
    {
        return $this->belongsTo(Website::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function rankings()
    {
        return $this->hasMany(KeywordRanking::class);
    }

    public function latestRanking()
    {
        return $this->hasOne(KeywordRanking::class)
            ->latest('checked_date');
    }
}
