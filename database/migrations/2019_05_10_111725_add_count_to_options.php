<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCountToOptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('options', function (Blueprint $table) {
            $table->string("count",256)->default(Crypt::encrypt(0));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('options', function (Blueprint $table) {
            //$table->dropColumn("count");
        });
    }
}
