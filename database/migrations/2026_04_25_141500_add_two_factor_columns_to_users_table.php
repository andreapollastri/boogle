<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('google2fa_secret')->nullable()->after('password');
            $table->timestamp('google2fa_enabled_at')->nullable()->after('google2fa_secret');
            $table->json('google2fa_recovery_codes')->nullable()->after('google2fa_enabled_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'google2fa_secret',
                'google2fa_enabled_at',
                'google2fa_recovery_codes',
            ]);
        });
    }
};
