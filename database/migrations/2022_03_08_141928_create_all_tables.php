<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAllTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('avatar')->default('default.png');
            $table->string('email')->unique();
            $table->string('password');
        });

        Schema::create('userfavorites', function (Blueprint $table) {
            $table->id();
            $table->integer('id_user');
            $table->integer('id_post');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->integer('id_user');
            $table->text('content');
            $table->string('slug')->unique();
            $table->dateTime('created_at');
        });

        Schema::create('postphotos', function (Blueprint $table) {
            $table->id();
            $table->integer('id_post');
            $table->string('url');
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
        });

        Schema::create('postcategories', function (Blueprint $table) {
            $table->id();
            $table->integer('id_categorie');
            $table->integer('id_post');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('userfavorites');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('postphotos');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('postcategories');
    }
}
