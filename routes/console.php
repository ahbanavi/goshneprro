<?php

use App\Services\FoodPartyService;
use App\Services\MarketPartyService;
use Illuminate\Support\Facades\Schedule;

Schedule::call(new FoodPartyService)->cron('*/'.config('foodparty.schedule_minutes').' * * * *');
Schedule::call(new MarketPartyService)->cron('*/'.config('marketparty.schedule_minutes').' * * * *');
