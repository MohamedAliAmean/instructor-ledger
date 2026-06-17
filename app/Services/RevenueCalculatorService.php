<?php
namespace App\Services;

class RevenueCalculatorService
{
    /**
     * Calculate platform cut.
     */
    public function calculatePlatformCut(
        int $amountPaid,
        int $platformFeePercentage
    ): int {
        return (int) floor(
            $amountPaid * $platformFeePercentage / 100
        );
    }

    /**
     * Calculate net revenue after platform cut.
     */
    public function calculateNetRevenue(
        int $amountPaid,
        int $platformFeePercentage
    ): int {
        return $amountPaid -
        $this->calculatePlatformCut(
            $amountPaid,
            $platformFeePercentage
        );
    }

    /**
     * Split revenue across instructors.
     */
    public function allocateRevenue(
        int $netRevenue,
        array $allocations
    ): array {

        $result = [];

        $allocated = 0;

        foreach ($allocations as $instructorId => $percentage) {

            $share = (int) floor(
                $netRevenue * $percentage / 100
            );

            $result[$instructorId] = $share;

            $allocated += $share;
        }

        $remainder = $netRevenue - $allocated;

        if ($remainder > 0) {

            $firstInstructorId = array_key_first($result);

            $result[$firstInstructorId] += $remainder;
        }

        return $result;
    }
}
