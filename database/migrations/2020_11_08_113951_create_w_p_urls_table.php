<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWPUrlsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('w_p_urls', function (Blueprint $table) {
            $table->id();
            $table->integer("user_id");
            $table->integer("site_id");
            $table->string("domain");
            $table->string("title");
            $table->string("url");
            $table->datetime("publish_date");
            $table->datetime("last_modified");
            $table->float("price")->nullable();
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
        Schema::dropIfExists('w_p_urls');
    }
}
