<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exception_status_events', function (Blueprint $table) {
            $table->string('source', 16)->default('panel')->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('exception_status_events', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
