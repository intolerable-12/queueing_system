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
    Schema::create('queue_cutoffs', function (Blueprint $table) {

        $table->id();

        $table->date('cutoff_date')->unique();

        $table->boolean('is_closed')->default(false);

        $table->foreignId('closed_by')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

        $table->timestamp('closed_at')->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_cutoffs');
    }
};
