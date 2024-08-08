<?php

namespace App\Policies;

use App\Models\MarketParty;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MarketPartyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MarketParty $marketParty): bool
    {
        return $user->isAdmin() || $user->id === $marketParty->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, MarketParty $marketParty): bool
    {
        return $user->isAdmin() || $user->id === $marketParty->user_id;
    }

    public function delete(User $user, MarketParty $marketParty): bool
    {
        return $user->isAdmin() || $user->id === $marketParty->user_id;
    }
}
