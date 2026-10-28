<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Bổ sung các cột còn thiếu khi CSDL được nhập từ bản dump cũ cinema.sql
return new class extends Migration
{
    /**
     * Kiểm tra cột bằng truy vấn thử thay vì Schema::hasColumn,
     * vì bản MariaDB của XAMPP có thể bị lỗi khi đọc information_schema.columns.
     */
    private function columnExists(string $table, string $column): bool
    {
        try {
            DB::select("select `$column` from `$table` limit 1");
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function up()
    {
        if (!$this->columnExists('users', 'verify_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('verify_token', 64)->nullable();
                $table->timestamp('verify_token_expires_at')->nullable();
            });
        }
        if (!$this->columnExists('users', 'reset_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('reset_token', 64)->nullable();
                $table->timestamp('reset_token_expires_at')->nullable();
            });
        }
        if (!$this->columnExists('banners', 'user_id')) {
            Schema::table('banners', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->constrained('users');
            });
        }
    }

    public function down()
    {
        // Không xóa: các cột này là một phần của lược đồ chuẩn ở các migration trước
    }
};
