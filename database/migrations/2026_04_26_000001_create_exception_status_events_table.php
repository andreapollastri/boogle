<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exception_status_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('exception_id');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_status', 16);
            $table->string('to_status', 16);
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->foreign('exception_id')->references('id')->on('exceptions')->cascadeOnDelete();
            $table->index(['exception_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exception_status_events');
    }
};
