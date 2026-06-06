<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('lensmania:reconcile-paypal --stale-minutes=20')
    ->everyFifteenMinutes();
