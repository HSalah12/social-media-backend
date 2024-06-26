<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatesTable extends Migration
{
    public function up()
    {
        Schema::create('states', function (Blueprint $table) {
            $table->id(); // This line creates an auto-incrementing primary key 'id' column
    $table->string('name');
    $table->string('code');
    $table->unsignedBigInteger('country_id');
    $table->timestamps();
        });

        Schema::table('states', function (Blueprint $table) {
            $table->foreign('country_id')
                  ->references('id')
                  ->on('countries')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('states');
    }
}
