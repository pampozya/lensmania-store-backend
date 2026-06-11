<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('orders:reconcile-paypal --stale-minutes=20')
    ->everyFifteenMinutes();
