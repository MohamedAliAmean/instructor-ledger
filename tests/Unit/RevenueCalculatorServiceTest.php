<?php

use App\Services\RevenueCalculatorService;

describe('RevenueCalculatorService', function () {
    beforeEach(function () {
        $this->calculator = new RevenueCalculatorService;
    });

    it('calculates platform cut using floor rounding', function () {
        expect($this->calculator->calculatePlatformCut(10000, 20))->toBe(2000);
        expect($this->calculator->calculatePlatformCut(10001, 20))->toBe(2000);
    });

    it('allocates net revenue across instructors without losing cents', function () {
        $allocations = [
            1 => 50,
            2 => 30,
            3 => 20,
        ];

        $shares = $this->calculator->allocateRevenue(10000, $allocations);

        expect(array_sum($shares))->toBe(10000);
        expect($shares[1])->toBe(5000);
        expect($shares[2])->toBe(3000);
        expect($shares[3])->toBe(2000);
    });

    it('assigns remainder cents to the first instructor', function () {
        $shares = $this->calculator->allocateRevenue(100, [
            1 => 34,
            2 => 33,
            3 => 33,
        ]);

        expect(array_sum($shares))->toBe(100);
        expect($shares[1])->toBe(34);
    });
});
