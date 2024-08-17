<?php

namespace App\Exceptions;

use Exception;

class MarketPartyBlockedException extends Exception
{
    protected $message = 'ERR: IP Address is blocked by Snappexpress, please change your IP Address and try again';

    protected $code = 403;
}
