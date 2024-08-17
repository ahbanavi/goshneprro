<?php

use App\Services\FoodPartyService;
use App\Services\MarketPartyService;
use Illuminate\Support\Facades\Schedule;

Schedule::call(new FoodPartyService)->cron(config('goshne.scheduler.food_party'));
Schedule::call(new MarketPartyService)->cron(config('goshne.scheduler.market_party'));
