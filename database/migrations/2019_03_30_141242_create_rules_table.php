<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Carbon\Carbon;

class CreateRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rules', function (Blueprint $table) {
            $carbon = new Carbon();
            $table->bigIncrements('id');
            $table->integer('user_id')->unsigned();
            $table->timestamps();
            $table->string('title');
            $table->string('desc');
            $table->string('votes_up',256);
            $table->string('votes_down',256);
            $table->timestamp("start_time")->default($carbon->startOfCentury());
            $table->timestamp("end_time")->default($carbon->startOfCentury());
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rules');
    }
}
