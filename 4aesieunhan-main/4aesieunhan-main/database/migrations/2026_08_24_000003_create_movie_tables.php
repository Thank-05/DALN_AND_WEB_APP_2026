<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Phim, thể loại, diễn viên, đạo diễn, âm thanh, phụ đề, lịch chiếu và bảng giá
return new class extends Migration
{
    public function up()
    {
        // CSDL đã nhập từ cinema.sql thì bỏ qua, chỉ tạo khi CSDL còn trống
        if (Schema::hasTable('movies')) {
            return;
        }

        Schema::create('rating', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description');
            $table->timestamps();
        });

        Schema::create('movie_genres', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('status')->default(false);
            $table->timestamps();
        });

        Schema::create('casts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('image');
            $table->date('birthday');
            $table->string('national');
            $table->text('content')->nullable();
            $table->timestamps();
        });

        Schema::create('directors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('image');
            $table->date('birthday')->nullable();
            $table->string('national')->nullable();
            $table->text('content')->nullable();
            $table->timestamps();
        });

        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('image');
            $table->integer('showTime')->nullable();
            $table->date('releaseDate');
            $table->date('endDate')->nullable();
            $table->string('national');
            $table->text('description')->nullable();
            $table->text('trailer')->nullable();
            $table->foreignId('rating_id')->constrained('rating');
            $table->boolean('status')->default(false);
            $table->timestamps();
        });

        Schema::create('moviegenres_movies', function (Blueprint $table) {
            $table->foreignId('movie_id')->constrained('movies')->cascadeOnDelete();
            $table->foreignId('movieGenre_id')->constrained('movie_genres')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('casts_movies', function (Blueprint $table) {
            $table->foreignId('movie_id')->constrained('movies')->cascadeOnDelete();
            $table->foreignId('cast_id')->constrained('casts')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('directors_movies', function (Blueprint $table) {
            $table->foreignId('movie_id')->constrained('movies')->cascadeOnDelete();
            $table->foreignId('director_id')->constrained('directors')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('audios', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('subtitles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms');
            $table->foreignId('movie_id')->constrained('movies');
            $table->foreignId('audio_id')->constrained('audios');
            $table->foreignId('subtitle_id')->constrained('subtitles');
            $table->date('date');
            $table->time('startTime');
            $table->time('endTime');
            $table->boolean('early')->default(false);
            $table->boolean('status')->default(false);
            $table->timestamps();
        });

        Schema::create('prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->integer('price')->default(0);
            $table->string('day');
            $table->time('after')->default('08:00:00');
            $table->string('generation');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('prices');
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('subtitles');
        Schema::dropIfExists('audios');
        Schema::dropIfExists('directors_movies');
        Schema::dropIfExists('casts_movies');
        Schema::dropIfExists('moviegenres_movies');
        Schema::dropIfExists('movies');
        Schema::dropIfExists('directors');
        Schema::dropIfExists('casts');
        Schema::dropIfExists('movie_genres');
        Schema::dropIfExists('rating');
    }
};
