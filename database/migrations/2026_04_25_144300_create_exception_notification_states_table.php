<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exception_notification_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('project_id');
            $table->string('fingerprint', 64);
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->unique(['user_id', 'project_id', 'fingerprint'], 'ens_user_project_fingerprint_unique');
            $table->index(['project_id', 'fingerprint']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exception_notification_states');
    }
};
