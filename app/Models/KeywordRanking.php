<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KeywordRanking extends Model
{
    protected $fillable = [
        'keyword_id', 'rank_position', 'ranking_url',
        'provider', 'checked_date', 'checked_at',
    ];

    protected $casts = [
        'checked_date' => 'date',
        'checked_at'   => 'datetime',
    ];

    public function keyword()
    {
        return $this->belongsTo(Keyword::class);
    }
}
