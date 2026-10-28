<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Đồ ăn, combo, mã giảm giá, vé và chi tiết vé (ghế, combo, món lẻ)
return new class extends Migration
{
    public function up()
    {
        // CSDL đã nhập từ cinema.sql thì bỏ qua, chỉ tạo khi CSDL còn trống
        if (Schema::hasTable('tickets')) {
            return;
        }

        Schema::create('foods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('image');
            $table->integer('price');
            $table->boolean('status')->default(false);
            $table->timestamps();
        });

        Schema::create('combos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('image');
            $table->integer('price');
            $table->boolean('status')->default(false);
            $table->timestamps();
        });

        Schema::create('combo_details', function (Blueprint $table) {
            $table->foreignId('combo_id')->constrained('combos')->cascadeOnDelete();
            $table->foreignId('food_id')->constrained('foods');
            $table->integer('quantity')->default(0);
            $table->timestamps();
        });

        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->integer('percent');
            $table->integer('quantity');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            // Để trống với đơn chỉ mua đồ ăn tại quầy
            $table->foreignId('schedule_id')->nullable()->constrained('schedules');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->boolean('holdState')->default(false);
            $table->boolean('status')->default(false);
            $table->bigInteger('code')->unique();
            $table->boolean('hasPaid')->default(false);
            $table->boolean('receivedCombo')->default(false);
            $table->boolean('hasDiscount')->default(false);
            $table->bigInteger('totalPrice')->default(0);
            $table->timestamps();
        });

        // Lưu tên, giá tại thời điểm mua để hóa đơn cũ không đổi khi bảng giá thay đổi
        Schema::create('ticketseats', function (Blueprint $table) {
            $table->string('row');
            $table->integer('col');
            $table->integer('price')->default(0);
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->string('seatType');
            $table->timestamps();
        });

        Schema::create('ticketcombos', function (Blueprint $table) {
            $table->string('comboName');
            $table->integer('comboPrice')->default(0);
            $table->string('comboDetails');
            $table->integer('quantity')->default(0);
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('ticketfoods', function (Blueprint $table) {
            $table->string('foodName');
            $table->integer('foodPrice')->default(0);
            $table->integer('quantity')->default(0);
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ticketfoods');
        Schema::dropIfExists('ticketcombos');
        Schema::dropIfExists('ticketseats');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('discounts');
        Schema::dropIfExists('combo_details');
        Schema::dropIfExists('combos');
        Schema::dropIfExists('foods');
    }
};
