<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('名前');
            $table->string('name_kana')->comment('フリガナ');
            $table->string('phone')->nullable()->comment('電話番号');
            $table->string('email')->comment('メールアドレス');
            $table->text('body')->comment('本文');
            $table->boolean('is_read')->default(false)->comment('既読フラグ');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
