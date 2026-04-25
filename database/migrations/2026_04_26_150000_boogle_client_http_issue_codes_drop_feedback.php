<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_groups', function (Blueprint $table) {
            $table->string('issue_prefix', 8)->nullable()->after('description');
            $table->unsignedInteger('next_issue_error')->default(1)->after('issue_prefix');
            $table->unsignedInteger('next_issue_outage')->default(1)->after('next_issue_error');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedInteger('next_issue_error')->default(1);
            $table->unsignedInteger('next_issue_outage')->default(1);
        });

        Schema::table('exceptions', function (Blueprint $table) {
            $table->json('http')->nullable()->after('user');
            $table->json('raw_exception')->nullable()->after('http');
            $table->string('issue_prefix', 8)->nullable()->index();
            $table->unsignedInteger('issue_number')->nullable();
            $table->index(['issue_prefix', 'issue_number']);
        });

        Schema::dropIfExists('feedbacks');
    }

    public function down(): void
    {
        Schema::table('exceptions', function (Blueprint $table) {
            $table->dropColumn(['http', 'raw_exception', 'issue_prefix', 'issue_number']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['next_issue_error', 'next_issue_outage']);
        });

        Schema::table('project_groups', function (Blueprint $table) {
            $table->dropColumn(['issue_prefix', 'next_issue_error', 'next_issue_outage']);
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
};
