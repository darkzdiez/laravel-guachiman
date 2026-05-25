<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('guachiman.database_connection'))->table(config('guachiman.table_name'), function (Blueprint $table) {
            $table->string('channel', 20)->nullable()->after('sapi_name')->index();
        });
    }

    public function down(): void
    {
        Schema::connection(config('guachiman.database_connection'))->table(config('guachiman.table_name'), function (Blueprint $table) {
            $table->dropIndex([config('guachiman.table_name') . '_channel_index']);
            $table->dropColumn('channel');
        });
    }
};
