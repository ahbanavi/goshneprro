<?php

use App\Services\FoodPartyService;
use Illuminate\Support\Facades\Schedule;

Schedule::call(new FoodPartyService)->cron('*/'.config('foodparty.schedule_minutes').' * * * *');
