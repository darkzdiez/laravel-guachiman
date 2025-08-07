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
            $tableName = config('guachiman.table_name');
            $table->dropIndex("{$tableName}_subject_id_subject_type_index");
            $table->dropIndex("{$tableName}_causer_id_causer_type_index");

            $table->string('subject_id')->nullable()->change();
            $table->string('causer_id')->nullable()->change();

            $table->index(['subject_id', 'subject_type']);
            $table->index(['causer_id', 'causer_type']);
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
            $table->dropIndex(['subject_id', 'subject_type']);
            $table->dropIndex(['causer_id', 'causer_type']);

            // This assumes original IDs were unsigned big integers.
            // A down migration might involve data loss if string IDs are in use.
            $table->unsignedBigInteger('subject_id')->nullable()->change();
            $table->unsignedBigInteger('causer_id')->nullable()->change();

            $tableName = config('guachiman.table_name');
            $table->index(['subject_id', 'subject_type'], "{$tableName}_subject_id_subject_type_index");
            $table->index(['causer_id', 'causer_type'], "{$tableName}_causer_id_causer_type_index");
        });
    }
}
