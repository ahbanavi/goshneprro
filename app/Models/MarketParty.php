<?php

namespace App\Models;

use App\Interfaces\Party;
use App\Services\MarketPartyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

class MarketParty extends Model implements Party
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

    public function run(): int
    {
        return MarketPartyService::get($this);
    }

    public function cacheLen(): int
    {
        return MarketPartyService::cacheLen($this);
    }

    public function clearCache(): bool
    {
        return MarketPartyService::clearCache($this);
    }
}
