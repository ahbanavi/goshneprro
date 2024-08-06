<?php

namespace App\Exceptions;

use Exception;

class SnappFoodPartyBlockedException extends Exception
{
    protected $message = 'ERR: IP Address is blocked by Snappfood, please change your IP Address and try again';

    protected $code = 403;
}
