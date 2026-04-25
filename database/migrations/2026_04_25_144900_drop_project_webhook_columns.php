<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('projects', 'slack_webhook') ? 'slack_webhook' : null,
                Schema::hasColumn('projects', 'discord_webhook') ? 'discord_webhook' : null,
                Schema::hasColumn('projects', 'custom_webhook') ? 'custom_webhook' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'slack_webhook')) {
                $table->string('slack_webhook')->nullable()->after('api_token');
            }

            if (! Schema::hasColumn('projects', 'discord_webhook')) {
                $table->string('discord_webhook')->nullable()->after('slack_webhook');
            }

            if (! Schema::hasColumn('projects', 'custom_webhook')) {
                $table->string('custom_webhook')->nullable()->after('discord_webhook');
            }
        });
    }
};
