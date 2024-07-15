<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOriginalNewsFeedItemIdToNewsFeedItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('news_feed_items', function (Blueprint $table) {
            $table->unsignedBigInteger('original_news_feed_item_id')->nullable()->after('longitude');
            $table->foreign('original_news_feed_item_id')->references('id')->on('news_feed_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('news_feed_items', function (Blueprint $table) {
            $table->dropForeign(['original_news_feed_item_id']);
            $table->dropColumn('original_news_feed_item_id');
        });
    }
}
