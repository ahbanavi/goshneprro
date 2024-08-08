<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/express', function () {
    return \App\Services\MarketPartyService::get(\App\Models\MarketParty::first());
});
