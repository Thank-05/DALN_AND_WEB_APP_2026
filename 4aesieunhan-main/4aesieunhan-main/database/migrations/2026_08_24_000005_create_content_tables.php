<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Banner, tin tức, sự kiện, thông tin website và góp ý của khách hàng
return new class extends Migration
{
    public function up()
    {
        // CSDL đã nhập từ cinema.sql thì bỏ qua, chỉ tạo khi CSDL còn trống
        if (Schema::hasTable('banners')) {
            return;
        }

        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->text('image');
            $table->boolean('status')->default(false);
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('image');
            $table->text('content');
            $table->boolean('status')->default(true);
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('image');
            $table->text('content');
            $table->text('conditions');
            $table->boolean('status')->default(false);
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
        });

        Schema::create('infos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('logo');
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('facebook')->nullable();
            $table->string('twitter')->nullable();
            $table->string('instagram')->nullable();
            $table->string('youtube')->nullable();
            $table->string('worktime')->nullable();
            $table->string('copyright')->nullable();
            $table->timestamps();
        });

        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('fullName');
            $table->string('email')->nullable();
            $table->string('phone', 10)->nullable();
            $table->text('message');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('feedback');
        Schema::dropIfExists('infos');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('news');
        Schema::dropIfExists('banners');
    }
};
