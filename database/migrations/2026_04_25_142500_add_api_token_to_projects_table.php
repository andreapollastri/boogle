<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('api_token', 80)->nullable()->after('key');
        });

        DB::table('projects')
            ->select('id')
            ->orderBy('id')
            ->each(function (object $project): void {
                DB::table('projects')
                    ->where('id', $project->id)
                    ->update(['api_token' => Str::random(60)]);
            });

        Schema::table('projects', function (Blueprint $table) {
            $table->string('api_token', 80)->nullable(false)->change();
            $table->unique('api_token');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropUnique(['api_token']);
            $table->dropColumn('api_token');
        });
    }
};
