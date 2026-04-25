<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exceptions', function (Blueprint $table) {
            $table->string('fingerprint', 64)->nullable()->after('exception');
            $table->index(['project_id', 'fingerprint', 'created_at']);
        });

        DB::table('exceptions')
            ->select(['id', 'exception', 'error', 'file', 'line', 'class'])
            ->orderBy('created_at')
            ->each(function (object $exception): void {
                $fingerprint = hash('sha256', implode('|', [
                    $exception->exception ?? '',
                    $exception->error ?? '',
                    $exception->file ?? '',
                    $exception->line ?? '',
                    $exception->class ?? '',
                ]));

                DB::table('exceptions')
                    ->where('id', $exception->id)
                    ->update(['fingerprint' => $fingerprint]);
            });
    }

    public function down(): void
    {
        Schema::table('exceptions', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'fingerprint', 'created_at']);
            $table->dropColumn('fingerprint');
        });
    }
};
