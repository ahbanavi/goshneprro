<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

class FoodParty extends Model
{
    use Notifiable;

    protected $fillable = [
        'description',
        'lat',
        'long',
        'threshold',
        'active',
        'tg_chat_id',
    ];

    protected $casts = [
        'id' => 'integer',
        'lat' => 'decimal:6',
        'long' => 'decimal:6',
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
}
