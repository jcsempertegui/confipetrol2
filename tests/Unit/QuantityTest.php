<?php

use App\Support\Quantity;

it('formats warehouse quantities without unnecessary zeroes', function () {
    expect(Quantity::format(5))->toBe('5')
        ->and(Quantity::format('2.500'))->toBe('2.5')
        ->and(Quantity::format(1.25))->toBe('1.25')
        ->and(Quantity::format(1.234))->toBe('1.234')
        ->and(Quantity::format(-3.5))->toBe('-3.5')
        ->and(Quantity::format(1200))->toBe('1,200')
        ->and(Quantity::format(0.0004))->toBe('0');
});
