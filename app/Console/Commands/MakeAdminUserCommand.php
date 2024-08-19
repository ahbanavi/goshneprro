<?php

namespace App\Console\Commands;

use Filament\Commands\MakeUserCommand;
use Illuminate\Contracts\Auth\Authenticatable;

class MakeAdminUserCommand extends MakeUserCommand
{
    protected $description = 'Create a new admin user';

    protected $signature = 'make:admin-user';

    protected function createUser(): Authenticatable
    {
        $user = parent::createUser();

        $user->is_admin = true;
        $user->saveQuietly();

        return $user;
    }
}
