<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_group_user', function (Blueprint $table) {
            $table->id();
            $table->uuid('project_group_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->foreign('project_group_id')
                ->references('id')
                ->on('project_groups')
                ->cascadeOnDelete();

            $table->unique(['project_group_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_group_user');
    }
};
