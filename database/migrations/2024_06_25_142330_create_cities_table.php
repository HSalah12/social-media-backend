<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCitiesTable extends Migration
{
    public function up()
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('state_id'); // Foreign key column
            $table->string('code');
            $table->unsignedBigInteger('country_id'); // Add this line

            // Define foreign key constraint
            $table->foreign('state_id')
                  ->references('id')
                  ->on('states')
                  ->onDelete('cascade'); // or 'restrict' depending on your needs
          $table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cities');
    }
}
