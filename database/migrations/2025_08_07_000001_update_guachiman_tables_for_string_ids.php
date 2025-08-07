<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateGuachimanTablesForStringIds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(config('guachiman.database_connection'))->table(config('guachiman.table_name'), function (Blueprint $table) {
            $table->dropIndex('subject');
            $table->dropIndex('causer');

            $table->string('subject_id')->nullable()->change();
            $table->string('causer_id')->nullable()->change();

            $table->index(['subject_type', 'subject_id'], 'subject');
            $table->index(['causer_type', 'causer_id'], 'causer');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection(config('guachiman.database_connection'))->table(config('guachiman.table_name'), function (Blueprint $table) {
            $table->dropIndex('subject');
            $table->dropIndex('causer');

            // This assumes original IDs were unsigned big integers.
            // A down migration might involve data loss if string IDs are in use.
            $table->unsignedBigInteger('subject_id')->nullable()->change();
            $table->unsignedBigInteger('causer_id')->nullable()->change();

            $table->index(['subject_id', 'subject_type'], 'subject');
            $table->index(['causer_id', 'causer_type'], 'causer');
        });
    }
}
