<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGuachimanTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(config('laravel-guachiman.database_connection'))->create(config('laravel-guachiman.table_name'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->string('event')->nullable();
            $table->nullableMorphs('causer', 'causer');
            $table->string('causer_name')->nullable();
            $table->json('properties')->nullable();
            $table->string('ref_name')->nullable();
            $table->string('ref')->nullable();
            $table->string('sapi_name')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();
            $table->index('log_name');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection(config('laravel-guachiman.database_connection'))->dropIfExists(config('laravel-guachiman.table_name'));
    }
}
