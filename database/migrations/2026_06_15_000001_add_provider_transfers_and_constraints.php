<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('provider_reference')->unique();
            $table->string('idempotency_key')->unique();
            $table->unsignedBigInteger('amount');
            $table->string('status');
            $table->timestamps();
        });

        Schema::table('revenue_schedules', function (Blueprint $table) {
            $table->unique([
                'subscription_id',
                'instructor_id',
                'earned_at',
            ], 'revenue_schedules_unique_accrual');
        });

        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->unique([
                'reference_type',
                'reference_id',
            ], 'ledger_entries_reference_unique');
        });
    }

    public function down(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->dropUnique('ledger_entries_reference_unique');
        });

        Schema::table('revenue_schedules', function (Blueprint $table) {
            $table->dropUnique('revenue_schedules_unique_accrual');
        });

        Schema::dropIfExists('provider_transfers');
    }
};
