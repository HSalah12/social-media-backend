<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewsFeedItemsTable extends Migration
{
    public function up()
    {
        Schema::create('news_feed_items', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('content');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('news_feed_items');
    }
}