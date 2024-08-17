<?php

namespace App\Models;

use App\Interfaces\Party;
use App\Services\FoodPartyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

class FoodParty extends Model implements Party
{
    use Notifiable;

    protected $fillable = [
        'user_id',
        'description',
        'latitude',
        'longitude',
        'threshold',
        'active',
        'tg_chat_id',
    ];

    protected $casts = [
        'id' => 'integer',
        'latitude' => 'double',
        'longitude' => 'double',
        'active' => 'boolean',
        'threshold' => 'integer',
        'tg_chat_id' => 'integer',
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
        return FoodPartyService::get($this);
    }

    public function cacheLen(): int
    {
        return FoodPartyService::cacheLen($this);
    }

    public function clearCache(): bool
    {
        return FoodPartyService::clearCache($this);
    }
}
