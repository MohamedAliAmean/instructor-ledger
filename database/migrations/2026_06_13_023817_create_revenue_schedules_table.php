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
        Schema::create('revenue_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('instructor_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedBigInteger('amount');

            $table->date('earned_at');

            $table->boolean('processed')
                ->default(false);

            $table->index([
                'instructor_id',
                'earned_at',
            ]);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revenue_schedules');
    }
};
