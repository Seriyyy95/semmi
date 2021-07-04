<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AddApiTokenToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('api_token', 60)->nullable();
        });
        $users = DB::table('users')->select('id')->get();
        foreach($users as $user){
            $token = Str::random(60);
            DB::table('users')->where('id', $user->id)->update(array(
                'api_token' => $token,
            ));
        }
        Schema::table('users', function (Blueprint $table) {
            $table->string('api_token', 60)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
}
