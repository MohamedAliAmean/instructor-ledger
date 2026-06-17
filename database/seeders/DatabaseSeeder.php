<?php

namespace Database\Seeders;

use App\Models\Instructor;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\User;
use App\Services\RevenueAccrualService;
use App\Services\RevenueAllocationService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $instructors = Instructor::factory()->count(3)->create();
        $student = Student::factory()->create(['name' => 'Demo Student']);

        $subscription = Subscription::factory()->create([
            'student_id' => $student->id,
            'amount_paid' => 100000,
            'platform_fee_percentage' => 20,
        ]);

        $subscription->instructors()->attach([
            $instructors[0]->id => ['allocation_percentage' => 50],
            $instructors[1]->id => ['allocation_percentage' => 30],
            $instructors[2]->id => ['allocation_percentage' => 20],
        ]);

        $allocationService = app(RevenueAllocationService::class);
        $allocationService->allocateForSubscription($subscription->fresh('instructors'));

        app(RevenueAccrualService::class)->processDueSchedules(now());
    }
}
