<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddManuallyColumnToWpUrls extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('w_p_urls', 'manually')) {
            Schema::table('w_p_urls', function (Blueprint $table) {
                $table->integer("manually")->default(0);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('wp_bindings', function (Blueprint $table) {
            //
        });
    }
}
