<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exceptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->string('host')->nullable();
            $table->string('method')->nullable();
            $table->text('full_url')->nullable();
            $table->string('exception')->nullable()->index();
            $table->text('error')->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->string('file')->nullable();
            $table->string('class')->nullable();
            $table->string('file_type')->nullable()->default('php');
            $table->string('status')->default('OPEN')->index();
            $table->boolean('mailed')->default(false);
            $table->string('publish_hash', 64)->unique()->nullable();
            $table->timestamp('published_at')->nullable();
            $table->json('executor')->nullable();
            $table->json('storage')->nullable();
            $table->json('additional')->nullable();
            $table->json('user')->nullable();
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'created_at']);
        });

        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->uuid('exception_id');
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->text('feedback');
            $table->timestamps();

            $table->foreign('exception_id')->references('id')->on('exceptions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
        Schema::dropIfExists('exceptions');
    }
};
