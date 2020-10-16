<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoogleAnalyticsSiteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('google_analytics_sites', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('profile_id');
            $table->string('profile_name');
            $table->string('domain');
            $table->string('start_date');
            $table->string('end_date');
            $table->string('first_date')->nullable();
            $table->string('last_date')->nullable();
            $table->integer('last_task_id')->nullable();
            $table->integer('parsent')->default(0);
            $table->integer('autoload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('google_analytics_sites');
    }
}
