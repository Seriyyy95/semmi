<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveUserIdColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('google_gsc_sites', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
        Schema::table('google_analytics_sites', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
        Schema::table('gsc_tasks', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
        Schema::table('google_analytics_tasks', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
        Schema::table('logs', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('google_gsc_sites', function (Blueprint $table) {
            $table->integer('user_id');
        });
        Schema::table('google_analytics_sites', function (Blueprint $table) {
            $table->integer('user_id');
        });
        Schema::table('gsc_tasks', function (Blueprint $table) {
            $table->integer('user_id');
        });
        Schema::table('google_analytics_tasks', function (Blueprint $table) {
            $table->integer('user_id');
        });
        Schema::table('logs', function (Blueprint $table) {
            $table->integer('user_id');
        });
    }
}
