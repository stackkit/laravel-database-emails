<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeBinaryToText extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->text('recipient')->change();
            $table->text('cc')->nullable()->change();
            $table->text('bcc')->nullable()->change();
            $table->text('subject')->change();
            $table->text('variables')->nullable()->change();
            $table->text('body')->change();
            $table->text('attachments')->nullable()->change();
            $table->text('from')->nullable()->change();
            $table->text('reply_to')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
