<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('social_users');
    }

    public function down(): void
    {
        Schema::create('social_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('provider_id');
            $table->string('token')->nullable();
            $table->string('refresh_token')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'provider_id']);
        });
    }
};
