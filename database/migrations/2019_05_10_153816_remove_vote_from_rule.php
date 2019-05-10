<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveVoteFromRule extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rules', function (Blueprint $table) {
            $table->dropColumn("votes_up");
            $table->dropColumn("votes_down");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rule', function (Blueprint $table) {
            //
        });
    }
}
