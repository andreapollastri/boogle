<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'api_token')) {
            return;
        }

        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_api_token_unique');
            });
        } catch (Throwable) {
            // Ignore when the unique index does not exist.
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('api_token');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'api_token')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('api_token', 80)->nullable()->after('password');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('api_token');
        });
    }
};
