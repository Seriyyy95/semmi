<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWpSiteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wp_sites', function (Blueprint $table) {
            $table->id();
            $table->integer("user_id");
            $table->integer("site_id");
            $table->integer("ga_site_id")->nullable();
            $table->integer("count")->default(0);
            $table->string("domain");
            $table->float("price")->default(0);
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
        Schema::dropIfExists('wp_sites');
    }
}
