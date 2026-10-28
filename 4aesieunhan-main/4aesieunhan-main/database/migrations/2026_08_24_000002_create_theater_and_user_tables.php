<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Rạp, loại phòng, loại ghế, phòng, ghế và tài khoản người dùng
return new class extends Migration
{
    public function up()
    {
        // CSDL đã nhập từ cinema.sql thì bỏ qua, chỉ tạo khi CSDL còn trống
        if (Schema::hasTable('theaters')) {
            return;
        }

        Schema::create('theaters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address');
            $table->string('city');
            $table->text('location')->nullable();
            $table->boolean('status')->default(false);
            $table->timestamps();
        });

        Schema::create('roomtypes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('surcharge')->default(0);
            $table->timestamps();
        });

        Schema::create('seattypes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('surcharge')->default(0);
            $table->string('color');
            $table->timestamps();
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('roomType_id')->constrained('roomtypes');
            $table->foreignId('theater_id')->constrained('theaters');
            $table->boolean('status')->default(false);
            $table->timestamps();
        });

        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->string('row');
            $table->integer('col');
            $table->integer('ms')->default(0);
            $table->integer('me')->default(0);
            $table->integer('mb')->default(0);
            $table->foreignId('seatType_id')->constrained('seattypes');
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->string('status')->default('1');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('fullName');
            $table->string('password');
            $table->string('email')->nullable()->unique();
            $table->string('phone', 20)->nullable()->unique();
            $table->boolean('status')->default(true);
            $table->bigInteger('code')->unique();
            $table->bigInteger('point')->default(0);
            $table->foreignId('theater_id')->nullable()->constrained('theaters');
            $table->boolean('email_verified')->default(false);
            // Token kích hoạt email (24 giờ) và đặt lại mật khẩu (60 phút)
            $table->string('verify_token', 64)->nullable();
            $table->timestamp('verify_token_expires_at')->nullable();
            $table->string('reset_token', 64)->nullable();
            $table->timestamp('reset_token_expires_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('seats');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('seattypes');
        Schema::dropIfExists('roomtypes');
        Schema::dropIfExists('theaters');
    }
};
