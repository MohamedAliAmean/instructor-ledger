<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')
                ->constrained('payout_batches')
                ->cascadeOnDelete();

            $table->foreignId('instructor_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedBigInteger('amount');

            $table->string('status');

            $table->string('idempotency_key')
                ->unique();

            $table->string('provider_reference')
                ->nullable();

            $table->index([
                'instructor_id',
                'status',
            ]);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
