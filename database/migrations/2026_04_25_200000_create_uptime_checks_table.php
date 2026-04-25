<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uptime_checks', function (Blueprint $table) {
            $table->id();
            $table->uuid('project_id');
            $table->boolean('success');
            $table->unsignedInteger('response_time_ms');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->index(['project_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uptime_checks');
    }
};
