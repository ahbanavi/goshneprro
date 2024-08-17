<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

class MarketParty extends Model
{
    use Notifiable;

    protected $fillable = [
        'description',
        'latitude',
        'longitude',
        'threshold',
        'tg_chat_id',
        'active',
        'user_id',
        'products',
    ];

    protected $casts = [
        'id' => 'integer',
        'latitude' => 'double',
        'longitude' => 'double',
        'threshold' => 'integer',
        'tg_chat_id' => 'integer',
        'active' => 'boolean',
        'products' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function routeNotificationForTelegram($notification)
    {
        return $this->tg_chat_id;
    }
}
