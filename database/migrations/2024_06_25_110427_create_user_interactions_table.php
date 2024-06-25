<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserInteractionsTable extends Migration
{
    public function up()
    {
        Schema::create('user_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('news_feed_item_id')->constrained()->onDelete('cascade');
            $table->enum('interaction_type', ['view', 'like', 'share']);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_interactions');
    }
}
