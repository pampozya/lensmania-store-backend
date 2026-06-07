<?php

use App\Models\Affiliate;

it('calculates commission owed from basis points', function () {
    $a = new Affiliate(['commission_bps' => 1000]); // 10%
    expect($a->commissionOwedCents(5000))->toBe(500);   // $50 -> $5
    expect($a->commissionOwedCents(3500))->toBe(350);   // $35 -> $3.50

    $b = new Affiliate(['commission_bps' => 2500]); // 25%
    expect($b->commissionOwedCents(10000))->toBe(2500); // $100 -> $25

    $c = new Affiliate(['commission_bps' => 0]);
    expect($c->commissionOwedCents(9999))->toBe(0);
});
