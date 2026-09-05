<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexNames = collect(Schema::getIndexes('push_subscriptions'))->pluck('name');
        $columnNames = collect(Schema::getColumns('push_subscriptions'))->pluck('name');

        if ($indexNames->contains('uq_push_subscriptions_endpoint')) {
            Schema::table('push_subscriptions', function (Blueprint $table) {
                $table->dropUnique('uq_push_subscriptions_endpoint');
            });
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE push_subscriptions MODIFY endpoint VARCHAR(2048) NOT NULL');
        }

        if (! $columnNames->contains('endpoint_hash')) {
            Schema::table('push_subscriptions', function (Blueprint $table) {
                $table->char('endpoint_hash', 64)->nullable();
            });
        }

        $rows = DB::table('push_subscriptions')->select('id', 'endpoint', 'endpoint_hash')->get();

        foreach ($rows as $row) {
            if (is_string($row->endpoint_hash) && $row->endpoint_hash !== '') {
                continue;
            }

            DB::table('push_subscriptions')->where('id', $row->id)->update([
                'endpoint_hash' => hash('sha256', (string) $row->endpoint),
            ]);
        }

        $indexNames = collect(Schema::getIndexes('push_subscriptions'))->pluck('name');

        if (! $indexNames->contains('uq_push_subscriptions_endpoint_hash')) {
            Schema::table('push_subscriptions', function (Blueprint $table) {
                $table->unique('endpoint_hash', 'uq_push_subscriptions_endpoint_hash');
            });
        }
    }

    public function down(): void
    {
        $indexNames = collect(Schema::getIndexes('push_subscriptions'))->pluck('name');

        if ($indexNames->contains('uq_push_subscriptions_endpoint_hash')) {
            Schema::table('push_subscriptions', function (Blueprint $table) {
                $table->dropUnique('uq_push_subscriptions_endpoint_hash');
            });
        }

        $columnNames = collect(Schema::getColumns('push_subscriptions'))->pluck('name');

        if ($columnNames->contains('endpoint_hash')) {
            Schema::table('push_subscriptions', function (Blueprint $table) {
                $table->dropColumn('endpoint_hash');
            });
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE push_subscriptions MODIFY endpoint VARCHAR(500) NOT NULL');
        }

        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->unique('endpoint', 'uq_push_subscriptions_endpoint');
        });
    }
};
