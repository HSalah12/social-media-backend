<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at');
            $table->string('password');
            $table->rememberToken();
            $table->string('profile_picture')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->text('bio')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('website_url')->nullable();
            $table->json('social_media_links')->nullable();
            $table->string('visibility_settings')->nullable();
            $table->string('privacy_settings')->nullable();
            $table->text('hobbies')->nullable();
            $table->text('favorite_books')->nullable();
            $table->text('favorite_movies')->nullable();
            $table->text('favorite_music')->nullable();
            $table->text('languages_spoken')->nullable();
            $table->text('favorite_quotes')->nullable();
            $table->text('education_history')->nullable();
            $table->text('employment_history')->nullable();
            $table->enum('relationship_status', ['single', 'in_a_relationship', 'married', 'divorced', 'widowed'])->default('single');
            $table->string('activity_engagement')->nullable();
            $table->json('notification_preferences')->nullable();
            $table->json('security_settings')->nullable();
            $table->json('achievements')->nullable();
            $table->boolean('badges')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};
