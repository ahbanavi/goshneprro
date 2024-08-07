<?php

namespace App\Policies;

use App\Models\FoodParty;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FoodPartyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, FoodParty $foodParty): bool
    {
        return $user->id === $foodParty->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, FoodParty $foodParty): bool
    {
        return $user->id === $foodParty->user_id;
    }

    public function delete(User $user, FoodParty $foodParty): bool
    {
        return $user->id === $foodParty->user_id;
    }
}
