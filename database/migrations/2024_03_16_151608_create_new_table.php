<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('emails')) {
            Schema::rename('emails', 'emails_old');
        }

        Schema::create('emails', function (Blueprint $table) {
            $table->increments('id');
            $table->string('label')->nullable();
            $table->json('recipient');
            $table->json('cc')->nullable();
            $table->json('bcc')->nullable();
            $table->string('subject');
            $table->string('view');
            $table->json('variables')->nullable();
            $table->text('body');
            $table->integer('attempts')->default(0);
            $table->boolean('sending')->default(0);
            $table->boolean('failed')->default(0);
            $table->text('error')->nullable();
            $table->json('attachments')->nullable();
            $table->json('from')->nullable();
            $table->nullableMorphs('model');
            $table->json('reply_to')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
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
